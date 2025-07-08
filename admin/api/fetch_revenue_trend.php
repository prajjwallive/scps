<?php
// admin/api/fetch_revenue_trend.php - Fetches revenue and items sold data for trend chart

// Set the default timezone to match where transactions are recorded (e.g., Nepal Time)
// This is CRITICAL for correct date comparisons and grouping by period.
// Choose a timezone identifier from https://www.php.net/manual/en/timezones.php
// 'Asia/Kathmandu' is used here as an example for Nepal Time (UTC+5:45)
date_default_timezone_set('Asia/Kathmandu'); // <<< Set your correct timezone here


// Start the session
session_start();

// --- REQUIRE ADMIN LOGIN ---
// This script should only be accessible to logged-in admins
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    // Send a JSON error response if not logged in
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit(); // Stop script execution
}
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection
// Path: From admin/api/ UP two levels (../../) THEN into includes/
require_once '../../includes/db_connection.php'; // NOTE THE UPDATED PATH

// --- Check Database Connection ---
if ($link === false) {
    // Log the connection error
    error_log('DB Error (fetch_revenue_trend.php): Could not connect to database: ' . mysqli_connect_error());
    // Set response for frontend
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit(); // Stop script execution
}
// --- End Check Database Connection ---


// Set the response header to indicate JSON content
header('Content-Type: application/json');

$response = [
    'success' => false, // Default to false, set to true if data is fetched
    'message' => 'Error fetching revenue trend data.',
    'trend' => [] // Array to hold trend data
];

// Get date range and category parameters from the GET request
$startDate = filter_input(INPUT_GET, 'startDate', FILTER_UNSAFE_RAW);
$endDate = filter_input(INPUT_GET, 'endDate', FILTER_UNSAFE_RAW);
$granularity = filter_input(INPUT_GET, 'granularity', FILTER_SANITIZE_STRING); // daily, weekly, monthly
$category = filter_input(INPUT_GET, 'category', FILTER_UNSAFE_RAW); // Get category parameter


// Default granularity if not provided or invalid
if (!in_array($granularity, ['daily', 'weekly', 'monthly'])) {
    $granularity = 'daily';
}

$startDateTime = null;
$endDateTime = null;
$bindParams = [];
$bindParamTypes = "";
$filterClauses = ["t.transaction_time IS NOT NULL", "t.status = 'success'"]; // Mandatory filters
$joinClauses = [];


// Date range filtering
if ($startDate !== null && $endDate !== null && $startDate !== '' && $endDate !== '') {
    $start_timestamp = strtotime($startDate);
    $end_timestamp = strtotime($endDate);

    if (!$start_timestamp || !$end_timestamp) {
        $response['message'] = 'Invalid date format provided.';
        error_log('Validation Error (fetch_revenue_trend.php): Invalid date format received: Start=' . $startDate . ', End=' . $endDate);
        echo json_encode($response);
        exit();
    }

    try {
        $timezone = new DateTimeZone(date_default_timezone_get());
        $start_dt_obj = new DateTime($startDate, $timezone);
        $end_dt_obj = new DateTime($endDate, $timezone);
        $start_dt_obj->setTime(0, 0, 0);
        $end_dt_obj->setTime(23, 59, 59);
        $startDateTime = $start_dt_obj->format('Y-m-d H:i:s');
        $endDateTime = $end_dt_obj->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        $response['message'] = 'Error processing date range: ' . $e->getMessage();
        error_log('Date Processing Error (fetch_revenue_trend.php): ' . $e->getMessage());
        echo json_encode($response);
        exit();
    }

    $filterClauses[] = "t.transaction_time BETWEEN ? AND ?";
    $bindParams[] = $startDateTime;
    $bindParamTypes .= "s";
    $bindParams[] = $endDateTime;
    $bindParamTypes .= "s";

    error_log("DEBUG: Fetch Revenue Trend Dates (Used in Query) - Start: " . $startDateTime . ", End: " . $endDateTime);

} else {
    error_log("DEBUG: No dates provided for trend. Fetching for All Time.");
}

// Category filtering
if ($category && strtolower($category) !== 'all') {
    $validCategories = ['veg', 'non-veg', 'beverage', 'snack', 'dessert'];
    $formattedCategory = ucfirst(strtolower($category));
    if (!in_array(strtolower($category), $validCategories)) {
        $response['success'] = false;
        $response['message'] = 'Invalid category provided.';
        echo json_encode($response);
        exit();
    }

    // Add joins for category filtering
    $joinClauses[] = "JOIN transaction_item ti ON t.txn_id = ti.txn_id";
    $joinClauses[] = "JOIN food f ON ti.food_id = f.food_id";
    
    // Add category filter clause
    $filterClauses[] = "f.category = ?";
    $bindParams[] = $formattedCategory;
    $bindParamTypes .= "s";
    error_log("DEBUG: Category filter applied: " . $formattedCategory);
} else {
    // If no category, we still need to join transaction_item for SUM(ti.quantity)
    $joinClauses[] = "JOIN transaction_item ti ON t.txn_id = ti.txn_id";
    error_log("DEBUG: No specific category filter applied. Only joining transaction_item.");
}


// Determine SQL format string and grouping based on granularity
$dateFormat = '';
$groupBy = '';
switch ($granularity) {
    case 'daily':
        $dateFormat = '%Y-%m-%d'; //YYYY-MM-DD
        $groupBy = "DATE(t.transaction_time)";
        break;
    case 'weekly':
        $dateFormat = '%Y-W%u'; //YYYY-Www (Week number)
        $groupBy = "YEAR(t.transaction_time), WEEK(t.transaction_time, 1)"; // Group by year and week number (week starts on Sunday)
        break;
    case 'monthly':
        $dateFormat = '%Y-%m'; //YYYY-MM
        $groupBy = "DATE_FORMAT(t.transaction_time, '%Y-%m')"; // Group by year and month
        break;
}

// Combine all filter clauses
$whereClause = "";
if (!empty($filterClauses)) {
    $whereClause = " WHERE " . implode(" AND ", $filterClauses);
}

// Combine all join clauses
$joinString = implode(" ", $joinClauses);


// Query for Revenue Trend and Items Sold Trend
$sqlRevenueTrend = "
    SELECT
        DATE_FORMAT(t.transaction_time, ?) AS period,
        COALESCE(SUM(t.total_amount), 0) AS revenue,
        COALESCE(SUM(ti.quantity), 0) AS items_sold
    FROM
        transaction t
    " . $joinString . "
    " . $whereClause . "
    GROUP BY
        period
    ORDER BY
        period ASC";

error_log("DB Info (fetch_revenue_trend.php): Preparing Revenue Trend query: " . $sqlRevenueTrend);

if ($stmt = mysqli_prepare($link, $sqlRevenueTrend)) {
    $types = 's' . $bindParamTypes; // Start with 's' for the date format string, then add date and category parameter types
    $args = [$dateFormat]; // Start with the date format string as the first argument

    if (!empty($bindParams)) {
        $args = array_merge($args, $bindParams);
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$args);

    error_log("DB Info (fetch_revenue_trend.php): Executing Revenue Trend query.");
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            $trendData = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $row['revenue'] = (float)$row['revenue'];
                $row['items_sold'] = (int)$row['items_sold'];
                $trendData[] = $row;
            }
            error_log("DEBUG: Revenue Trend Raw Result Count: " . count($trendData));

            mysqli_free_result($result);

            $response['success'] = true;
            $response['message'] = 'Revenue trend data fetched successfully.';
            $response['trend'] = $trendData;

        } else {
            error_log('DB Error (fetch_revenue_trend.php): Revenue Trend get_result failed: ' . mysqli_stmt_error($stmt));
            $response['message'] = 'Database error getting revenue trend result: ' . mysqli_stmt_error($stmt);
        }
    } else {
        error_log('DB Error (fetch_revenue_trend.php): Revenue Trend execute: ' . mysqli_stmt_error($stmt));
        $response['message'] = 'Database error fetching revenue trend: ' . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_revenue_trend.php): Revenue Trend prepare: ' . mysqli_error($link));
    $response['message'] = 'Database error preparing revenue trend query: ' . mysqli_error($link);
}


error_log("DEBUG: Final Response (fetch_revenue_trend.php): " . json_encode($response));
echo json_encode($response);

?>
