<?php
// admin/api/fetch_sales_summary.php - Fetches sales summary data (Total Revenue, Items Sold, Transactions, Customers)

// Set the default timezone to match where transactions are recorded (e.g., Nepal Time)
// This is CRITICAL for correct date comparisons with database timestamps.
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
    error_log('DB Error (fetch_sales_summary.php): Could not connect to database: ' . mysqli_connect_error());
    // Set response for frontend
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit(); // Stop script execution
}
error_log('DB Info (fetch_sales_summary.php): Database connection successful.'); // Log successful connection
// --- End Check Database Connection ---


// Set the response header to indicate JSON content
header('Content-Type: application/json');

// Initialize response with default values
$response = [
    'success' => true, // Assume success initially, set to false if any error occurs
    'message' => 'Sales summary fetched successfully.', // Default success message
    'summary' => [
        'total_revenue' => 0,
        'total_items_sold' => 0,
        'total_transactions' => 0,
        'total_customers' => 0
    ]
];

// Variables to hold fetched data
$totalRevenue = 0;
$totalItemsSold = 0;
$totalTransactions = 0;
$totalCustomers = 0;


// Get date range parameters from the GET request
$startDate = filter_input(INPUT_GET, 'startDate', FILTER_UNSAFE_RAW);
$endDate = filter_input(INPUT_GET, 'endDate', FILTER_UNSAFE_RAW);
$category = filter_input(INPUT_GET, 'category', FILTER_UNSAFE_RAW); // Get category parameter


// --- Debug Log for Raw Input Dates and Category ---
error_log("DEBUG: Raw Input - Start: " . ($startDate ?? 'NULL') . ", End: " . ($endDate ?? 'NULL') . ", Category: " . ($category ?? 'NULL'));
// --- End Debug Log ---

$startDateTime = null;
$endDateTime = null;
$bindParams = []; // Array to hold parameters for binding
$bindParamTypes = ""; // String to hold types for binding
$filterClauses = []; // Array to hold individual filter conditions
$joinClauses = []; // Array to hold join clauses

// Add mandatory status filter
$filterClauses[] = "t.status = 'success'";

// Date range filtering
if ($startDate !== null && $endDate !== null && $startDate !== '' && $endDate !== '') {
    $start_timestamp = strtotime($startDate);
    $end_timestamp = strtotime($endDate);

    if (!$start_timestamp || !$end_timestamp) {
        $response['success'] = false;
        $response['message'] = 'Invalid date format provided.';
        error_log('Validation Error (fetch_sales_summary.php): Invalid date format received: Start=' . $startDate . ', End=' . $endDate);
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
        $response['success'] = false;
        $response['message'] = 'Error processing date range: ' . $e->getMessage();
        error_log('Date Processing Error (fetch_sales_summary.php): ' . $e->getMessage());
        echo json_encode($response);
        exit();
    }

    $filterClauses[] = "t.transaction_time BETWEEN ? AND ?";
    $bindParams[] = $startDateTime;
    $bindParamTypes .= "s";
    $bindParams[] = $endDateTime;
    $bindParamTypes .= "s";

    error_log("DEBUG: Sales Summary Dates (Used in Query) - Start: " . $startDateTime . ", End: " . $endDateTime);
} else {
    error_log("DEBUG: No dates provided. Fetching sales summary for All Time.");
}

// Category filtering
if ($category && strtolower($category) !== 'all') {
    $validCategories = ['veg', 'non-veg', 'beverage', 'snack', 'dessert'];
    // Convert to proper case for consistency with database (e.g., 'Snack', 'Non-Veg')
    $formattedCategory = ucfirst(strtolower($category)); 
    if (!in_array(strtolower($category), $validCategories)) { // Validate input lowercased
        $response['success'] = false;
        $response['message'] = 'Invalid category provided.';
        echo json_encode($response);
        exit();
    }

    // Add join for transaction_item and food tables
    $joinClauses[] = "JOIN transaction_item ti ON t.txn_id = ti.txn_id";
    $joinClauses[] = "JOIN food f ON ti.food_id = f.food_id";
    
    // Add category filter clause
    $filterClauses[] = "f.category = ?";
    $bindParams[] = $formattedCategory; // Bind the formatted category
    $bindParamTypes .= "s";
    error_log("DEBUG: Category filter applied: " . $formattedCategory);
} else {
    error_log("DEBUG: No specific category filter applied.");
}

// Combine all filter clauses
$whereClause = "";
if (!empty($filterClauses)) {
    $whereClause = " WHERE " . implode(" AND ", $filterClauses);
}

// Combine all join clauses
$joinString = implode(" ", $joinClauses);


// --- Fetch Sales Summary Data ---

// Query for Total Revenue
// For revenue, we need to consider total_amount from transaction table.
// If category is filtered, we need to join through transaction_item and food.
$sqlTotalRevenue = "
    SELECT COALESCE(SUM(t.total_amount), 0) AS total_revenue
    FROM transaction t
    " . $joinString . "
    " . $whereClause;

error_log("DB Info (fetch_sales_summary.php): Preparing Total Revenue query: " . $sqlTotalRevenue);
if ($stmt = mysqli_prepare($link, $sqlTotalRevenue)) {
    if (!empty($bindParams)) {
        mysqli_stmt_bind_param($stmt, $bindParamTypes, ...$bindParams);
    }
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $totalRevenue = $row['total_revenue'];
        }
        if ($result) mysqli_free_result($result);
    } else {
        error_log('DB Error (fetch_sales_summary.php): Total Revenue execute: ' . mysqli_stmt_error($stmt));
        $response['success'] = false;
        $response['message'] = 'Database error fetching total revenue: ' . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_sales_summary.php): Total Revenue prepare: ' . mysqli_error($link));
    $response['success'] = false;
    $response['message'] = 'Database error preparing total revenue query: ' . mysqli_error($link);
}


// Query for Total Items Sold
// This always needs to join with transaction_item and food to sum quantities and filter by category.
$sqlTotalItemsSold = "
    SELECT COALESCE(SUM(ti.quantity), 0) AS total_items_sold
    FROM transaction t
    JOIN transaction_item ti ON t.txn_id = ti.txn_id
    " . (empty($joinClauses) ? "" : "JOIN food f ON ti.food_id = f.food_id") . "
    " . $whereClause;

// Special handling for bindParams and bindParamTypes for items_sold if category is not selected for other queries.
// If category is not selected, the $joinClauses array would be empty.
// We only want to add the food join IF the category is selected.
// However, to sum items sold, we always need 'ti'.
$itemsSoldBindParams = [];
$itemsSoldBindParamTypes = "";
$itemsSoldFilterClauses = ["t.status = 'success'"];

if ($startDate !== null && $endDate !== null && $startDate !== '' && $endDate !== '') {
    $itemsSoldFilterClauses[] = "t.transaction_time BETWEEN ? AND ?";
    $itemsSoldBindParams[] = $startDateTime;
    $itemsSoldBindParamTypes .= "s";
    $itemsSoldBindParams[] = $endDateTime;
    $itemsSoldBindParamTypes .= "s";
}
if ($category && strtolower($category) !== 'all') {
    $itemsSoldFilterClauses[] = "f.category = ?";
    $itemsSoldBindParams[] = $formattedCategory;
    $itemsSoldBindParamTypes .= "s";
}

$itemsSoldWhereClause = " WHERE " . implode(" AND ", $itemsSoldFilterClauses);
$itemsSoldJoinFood = ($category && strtolower($category) !== 'all') ? " JOIN food f ON ti.food_id = f.food_id" : "";


$sqlTotalItemsSold = "
    SELECT COALESCE(SUM(ti.quantity), 0) AS total_items_sold
    FROM transaction t
    JOIN transaction_item ti ON t.txn_id = t.txn_id
    " . $itemsSoldJoinFood . "
    " . $itemsSoldWhereClause;


error_log("DB Info (fetch_sales_summary.php): Preparing Total Items Sold query: " . $sqlTotalItemsSold);
if ($stmt = mysqli_prepare($link, $sqlTotalItemsSold)) {
    if (!empty($itemsSoldBindParams)) {
        mysqli_stmt_bind_param($stmt, $itemsSoldBindParamTypes, ...$itemsSoldBindParams);
    }
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $totalItemsSold = $row['total_items_sold'];
        }
        if ($result) mysqli_free_result($result);
    } else {
        error_log('DB Error (fetch_sales_summary.php): Total Items Sold execute: ' . mysqli_stmt_error($stmt));
        $response['success'] = false;
        $response['message'] = 'Database error fetching total items sold: ' . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_sales_summary.php): Total Items Sold prepare: ' . mysqli_error($link));
    $response['success'] = false;
    $response['message'] = 'Database error preparing total items sold query.';
}


// Query for Total Transactions
// This query implicitly needs to consider joins if category is filtered.
$sqlTotalTransactions = "
    SELECT COALESCE(COUNT(DISTINCT t.txn_id), 0) AS total_transactions
    FROM transaction t
    " . $joinString . "
    " . $whereClause;

error_log("DB Info (fetch_sales_summary.php): Preparing Total Transactions query: " . $sqlTotalTransactions);
if ($stmt = mysqli_prepare($link, $sqlTotalTransactions)) {
    if (!empty($bindParams)) {
        mysqli_stmt_bind_param($stmt, $bindParamTypes, ...$bindParams);
    }
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $totalTransactions = $row['total_transactions'];
        }
        if ($result) mysqli_free_result($result);
    } else {
        error_log('DB Error (fetch_sales_summary.php): Total Transactions execute: ' . mysqli_stmt_error($stmt));
        $response['success'] = false;
        $response['message'] = 'Database error fetching total transactions: ' . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_sales_summary.php): Total Transactions prepare: ' . mysqli_error($link));
    $response['success'] = false;
    $response['message'] = 'Database error preparing total transactions query.';
}

// Query for Total Customers (Unique Students who made a transaction)
// This also needs to consider joins if category is filtered.
$sqlTotalCustomers = "
    SELECT COALESCE(COUNT(DISTINCT t.student_id), 0) AS total_customers
    FROM transaction t
    " . $joinString . "
    " . $whereClause;

error_log("DB Info (fetch_sales_summary.php): Preparing Total Customers query: " . $sqlTotalCustomers);
if ($stmt = mysqli_prepare($link, $sqlTotalCustomers)) {
    if (!empty($bindParams)) {
        mysqli_stmt_bind_param($stmt, $bindParamTypes, ...$bindParams);
    }
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $totalCustomers = $row['total_customers'];
        }
        if ($result) mysqli_free_result($result);
    } else {
        error_log('DB Error (fetch_sales_summary.php): Total Customers execute: ' . mysqli_stmt_error($stmt));
        $response['success'] = false;
        $response['message'] = 'Database error getting total customers result: ' . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_sales_summary.php): Total Customers prepare: ' . mysqli_error($link));
    $response['success'] = false;
    $response['message'] = 'Database error preparing total customers query.';
}


// --- Final Response ---
if ($response['success'] === true) {
    $response['summary'] = [
        'total_revenue' => $totalRevenue,
        'total_items_sold' => $totalItemsSold,
        'total_transactions' => $totalTransactions,
        'total_customers' => $totalCustomers
    ];
    $response['message'] = 'Sales summary fetched successfully.';
} else {
    if (!isset($response['message']) || $response['message'] === 'Error fetching sales summary.') {
        $response['message'] = 'An error occurred while fetching sales data.';
    }
}

error_log("DEBUG: Final Response (fetch_sales_summary.php): " . json_encode($response));
echo json_encode($response);

?>
