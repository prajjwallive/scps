<?php
// admin/api/fetch_top_selling_items.php - Fetches top selling items data with updated table/column names and fixed date column

// Set the default timezone to match where transactions are recorded (e.g., Nepal Time)
// This is CRITICAL for correct date comparisons.
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
require_once '../../includes/db_connection.php'; // Ensure this path is correct and it creates $link

// --- Check Database Connection ---
// Use $link as defined in your db_connection.php
if ($link === false) {
    // Log the connection error
    error_log('DB Error (fetch_top_selling_items.php): Could not connect to database: ' . mysqli_connect_error());
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
    'message' => 'Error fetching top selling items.',
    'top_items' => [] // Array to hold top selling items data
];

// Get date range and category parameters from the GET request
$startDate = filter_input(INPUT_GET, 'startDate', FILTER_UNSAFE_RAW);
$endDate = filter_input(INPUT_GET, 'endDate', FILTER_UNSAFE_RAW);
$category = filter_input(INPUT_GET, 'category', FILTER_UNSAFE_RAW); // Use UNSAFE_RAW for category as well for consistency then validate


$startDateTime = null;
$endDateTime = null;
$bindParams = [];
$bindParamTypes = "";
$filterClauses = []; // Array to hold individual filter conditions


// Add the mandatory status filter first
$filterClauses[] = "t.status = 'success'";

// Determine date range and add to filter clauses
if ($startDate !== null && $endDate !== null && $startDate !== '' && $endDate !== '') {
    $start_timestamp = strtotime($startDate);
    $end_timestamp = strtotime($endDate);

    if (!$start_timestamp || !$end_timestamp) {
        $response['message'] = 'Invalid date format provided.';
        error_log('Validation Error (fetch_top_selling_items.php): Invalid date format received: Start=' . $startDate . ', End=' . $endDate);
        echo json_encode($response);
        exit();
    }

    // Add time component for inclusive range
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
        error_log('Date Processing Error (fetch_top_selling_items.php): ' . $e->getMessage());
        echo json_encode($response);
        exit();
    }

    // Add date filter to clauses
    $filterClauses[] = "t.transaction_time BETWEEN ? AND ?";
    $bindParams[] = $startDateTime;
    $bindParamTypes .= "s";
    $bindParams[] = $endDateTime;
    $bindParamTypes .= "s";

    error_log("DEBUG: Fetch Top Items Dates (Used in Query) - Start: " . $startDateTime . ", End: " . $endDateTime);

} else {
    error_log("DEBUG: No dates provided for top items. Fetching for All Time.");
}

// Determine category filter and add to filter clauses
// DEBUG: Log the raw category received from the frontend
error_log("DEBUG: fetch_top_selling_items.php - Raw category received: '" . ($category === null ? 'NULL' : $category) . "'");

if ($category !== null && $category !== '' && strtolower($category) !== 'all') {
    $validCategories = ['veg', 'non-veg', 'beverage', 'snack', 'dessert'];
    // Convert to proper case for consistency with database (e.g., 'Snack', 'Non-Veg')
    // We will now bind the lowercase category and use LOWER() in the SQL for case-insensitivity
    $lowerCaseCategory = strtolower($category); 
    
    // DEBUG: Log the lowercase category before validation
    error_log("DEBUG: fetch_top_selling_items.php - Lowercase category for validation: '" . $lowerCaseCategory . "'");

    if (!in_array($lowerCaseCategory, $validCategories)) { // Validate input lowercased
        $response['success'] = false;
        $response['message'] = 'Invalid category provided.';
        error_log('Validation Error (fetch_top_selling_items.php): Invalid category received: ' . $category . ' (Lowercase: ' . $lowerCaseCategory . ')'); // Log this specific error
        echo json_encode($response);
        exit(); // <-- This exit is the concern if the category is invalid
    }

    // Use LOWER() in SQL for case-insensitive comparison
    $filterClauses[] = "LOWER(f.category) = ?";
    $bindParams[] = $lowerCaseCategory; // Bind the lowercase category
    $bindParamTypes .= "s";
    error_log("DEBUG: Category filter applied (lowercase): " . $lowerCaseCategory);
} else {
    error_log("DEBUG: No category filter applied (category is null, empty, or 'all').");
}

// Combine all filter clauses with AND, starting with WHERE if there are clauses
$whereClause = "";
if (!empty($filterClauses)) {
    $whereClause = "WHERE " . implode(" AND ", $filterClauses);
}


// Query to fetch top selling items by revenue
// Join transaction, transaction_item, and food tables
// Group by food item and sum the revenue (quantity * unit_price)
$sqlTopItems = "
    SELECT
        f.food_id,
        f.name,
        f.image_path,
        f.category,
        COALESCE(SUM(ti.quantity * ti.unit_price), 0) AS total_revenue,
        COALESCE(SUM(ti.quantity), 0) AS total_quantity_sold
    FROM
        transaction_item ti
    JOIN
        transaction t ON ti.txn_id = t.txn_id
    JOIN
        food f ON ti.food_id = f.food_id
    " . $whereClause . "
    GROUP BY
        f.food_id, f.name, f.image_path, f.category
    ORDER BY
        total_revenue DESC, total_quantity_sold DESC
    LIMIT 10";


error_log("DB Info (fetch_top_selling_items.php): Preparing Top Items query: " . $sqlTopItems);

if ($stmt = mysqli_prepare($link, $sqlTopItems)) {
    $types = $bindParamTypes; // Use the dynamically built type string

    if (!empty($bindParams)) {
        mysqli_stmt_bind_param($stmt, $types, ...$bindParams);
        // Log the bound parameters for debugging
        error_log("DEBUG: Top Items Bound Parameters: " . implode(', ', $bindParams));
    } else {
        error_log("DEBUG: No parameters bound for Top Items query.");
    }

    error_log("DB Info (fetch_top_selling_items.php): Executing Top Items query.");
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            $topItemsData = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $row['total_revenue'] = (float)$row['total_revenue'];
                $row['total_quantity_sold'] = (int)$row['total_quantity_sold'];
                $topItemsData[] = $row;
            }
            error_log("DEBUG: Top Items Raw Result Count: " . count($topItemsData));

            mysqli_free_result($result);

            $response['success'] = true;
            $response['message'] = 'Top selling items data fetched successfully.';
            $response['top_items'] = $topItemsData;

        } else {
            error_log('DB Error (fetch_top_selling_items.php): Top Items get_result failed: ' . mysqli_stmt_error($stmt));
            $response['message'] = 'Database error getting top items result: ' . mysqli_stmt_error($stmt);
            $response['success'] = false;
        }
    } else {
        error_log('DB Error (fetch_top_selling_items.php): Top Items execute: ' . mysqli_stmt_error($stmt));
        $response['message'] = 'Database error fetching top items: ' . mysqli_stmt_error($stmt);
        $response['success'] = false;
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_top_selling_items.php): Top Items prepare: ' . mysqli_error($link));
    $response['message'] = 'Database error preparing top items query: ' . mysqli_error($link);
    $response['success'] = false;
}


error_log("DEBUG: Final Response (fetch_top_selling_items.php): " . json_encode($response));
echo json_encode($response);

?>
