<?php
// admin/api/fetch_customer_growth.php - Fetches data for customer growth analysis with pagination

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
    error_log('DB Error (fetch_customer_growth.php): Could not connect to database: ' . mysqli_connect_error());
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
    'message' => 'Error fetching customer growth data.',
    'growth_data' => [], // Array to hold customer growth data for the current page
    'total_records' => 0 // Total number of rows before pagination
];

// Get filter and pagination parameters from the GET request
$startDate = filter_input(INPUT_GET, 'startDate', FILTER_UNSAFE_RAW);
$endDate = filter_input(INPUT_GET, 'endDate', FILTER_UNSAFE_RAW);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT); // Current page number
$itemsPerPage = filter_input(INPUT_GET, 'itemsPerPage', FILTER_VALIDATE_INT); // Items per page

// Default pagination values
$page = ($page > 0) ? $page : 1;
$itemsPerPage = ($itemsPerPage > 0) ? $itemsPerPage : 10; // Default to 10 items per page
$offset = ($page - 1) * $itemsPerPage;


$startDateTime = null;
$endDateTime = null;
$bindParams = [];
$bindParamTypes = "";
$dateWhereClause = ""; // WHERE clause for filtering transactions within the selected period

// Determine date range and WHERE clause based on provided dates
if ($startDate !== null && $endDate !== null && $startDate !== '' && $endDate !== '') {
     // Validate date format
    $start_timestamp = strtotime($startDate);
    $end_timestamp = strtotime($endDate);

    if (!$start_timestamp || !$end_timestamp) {
         $response['message'] = 'Invalid date format provided.';
         error_log('Validation Error (fetch_customer_growth.php): Invalid date format received: Start=' . $startDate . ', End=' . $endDate);
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
         error_log('Date Processing Error (fetch_customer_growth.php): ' . $e->getMessage());
         echo json_encode($response);
         exit();
    }

    // Date filter applies to the transaction time
    $dateWhereClause = "WHERE t.transaction_time BETWEEN ? AND ?";
    $bindParams[] = $startDateTime;
    $bindParamTypes .= "s";
    $bindParams[] = $endDateTime;
    $bindParamTypes .= "s";


    error_log("DEBUG: Fetch Customer Growth Dates (Used in Query) - Start: " . $startDateTime . ", End: " . $endDateTime);

} else {
     // If no dates, fetch for all time
     error_log("DEBUG: No dates provided for customer growth. Fetching for All Time.");
     // No where clause needed
}

// Determine SQL format string and grouping based on granularity (defaulting to daily)
$dateFormat = '%Y-%m-%d'; // Group by Day (YYYY-MM-DD)
$groupBy = "DATE(t.transaction_time)";

// You can add logic here to change $dateFormat and $groupBy based on a 'granularity' GET parameter
// if you want to allow grouping by week or month from the frontend.
// Example:
// $granularityParam = filter_input(INPUT_GET, 'granularity', FILTER_SANITIZE_STRING);
// switch ($granularityParam) {
//     case 'weekly':
//         $dateFormat = '%Y-W%u'; //YYYY-Www
//         $groupBy = "YEAR(t.transaction_time), WEEK(t.transaction_time, 1)";
//         break;
//     case 'monthly':
//         $dateFormat = '%Y-%m'; //YYYY-MM
//         $groupBy = "DATE_FORMAT(t.transaction_time, '%Y-%m')";
//         break;
//     default: // daily
//         $dateFormat = '%Y-%m-%d';
//         $groupBy = "DATE(t.transaction_time)";
//         break;
// }


// --- Query to get Total Records (for pagination) ---
// This counts the number of unique periods within the selected date range that have transactions.
$sqlTotalCount = "
    SELECT COUNT(DISTINCT DATE_FORMAT(transaction_time, ?)) AS total_periods
    FROM transaction t
    " . $dateWhereClause;

$totalRecords = 0;
error_log("DB Info (fetch_customer_growth.php): Preparing Total Count query: " . $sqlTotalCount);
if ($stmtCount = mysqli_prepare($link, $sqlTotalCount)) {
    error_log("DB Info (fetch_customer_growth.php): Total Count query prepared successfully.");

    // Bind the date format string and date parameters (if date clause is used)
    $typesCount = 's' . $bindParamTypes; // Start with 's' for the date format string
    $argsCount = [$dateFormat]; // Start with the date format string

    // Add the date parameters if they exist
    if (!empty($bindParams)) {
        $argsCount = array_merge($argsCount, $bindParams);
    }

    // Bind parameters using the unpacking operator (...)
    mysqli_stmt_bind_param($stmtCount, $typesCount, ...$argsCount);

    error_log("DB Info (fetch_customer_growth.php): Executing Total Count query.");
    if (mysqli_stmt_execute($stmtCount)) {
        error_log("DB Info (fetch_customer_growth.php): Total Count query executed successfully.");
        $resultCount = mysqli_stmt_get_result($stmtCount);
        if ($resultCount && $rowCount = mysqli_fetch_assoc($resultCount)) {
            $totalRecords = $rowCount['total_periods'];
            error_log("DEBUG: Total Records Count: " . $totalRecords);
        } else {
             error_log('DB Info (fetch_customer_growth.php): Total Count fetch assoc returned no rows.');
        }
        if ($resultCount) mysqli_free_result($resultCount);
    } else {
        error_log('DB Error (fetch_customer_growth.php): Total Count execute: ' . mysqli_stmt_error($stmtCount));
        // Don't set success to false yet, let the main query run
    }
    mysqli_stmt_close($stmtCount);
} else {
    error_log('DB Error (fetch_customer_growth.php): Total Count prepare: ' . mysqli_error($link));
    // Don't set success to false yet
}


// --- Main Query for Customer Growth Data (with New/Repeat/Cumulative) ---
// This query is more complex. It involves subqueries or window functions
// to find the first transaction date for each student.

// Method 1: Using a subquery to find the first transaction date for each student
// Then joining with the transactions table to categorize and group by period.
// This can be less performant on very large datasets.

$sqlCustomerGrowth = "
    SELECT
        period_data.period,
        COALESCE(SUM(CASE WHEN first_txn.first_transaction_date >= period_start_time AND first_txn.first_transaction_date <= period_end_time THEN 1 ELSE 0 END), 0) AS new_customers,
        COALESCE(SUM(CASE WHEN first_txn.first_transaction_date < period_start_time THEN 1 ELSE 0 END), 0) AS repeat_customers,
        (SELECT COUNT(DISTINCT student_id) FROM transaction WHERE transaction_time <= period_end_time) AS total_customers_cumulative
    FROM (
        -- Generate a series of periods within the selected date range
        -- This is a simplified way; a more robust method might involve a calendar table
        SELECT
            DATE_FORMAT(t.transaction_time, ?) AS period,
            MIN(t.transaction_time) AS period_start_time, -- Start time of the period (first transaction)
            MAX(t.transaction_time) AS period_end_time -- End time of the period (last transaction)
        FROM
            transaction t
        " . $dateWhereClause . " -- Apply date filter to transactions
        GROUP BY
            period
        ORDER BY
            period ASC
    ) AS period_data
    LEFT JOIN transaction t ON DATE_FORMAT(t.transaction_time, period_data.period) = period_data.period -- Join back to transactions in the period
    LEFT JOIN (
        -- Subquery to find the first transaction date for each student
        SELECT
            student_id,
            MIN(transaction_time) AS first_transaction_date
        FROM
            transaction
        GROUP BY
            student_id
    ) AS first_txn ON t.student_id = first_txn.student_id
    GROUP BY
        period_data.period
    ORDER BY
        period_data.period ASC
    LIMIT ?, ?"; // Add LIMIT and OFFSET for pagination


error_log("DB Info (fetch_customer_growth.php): Preparing Customer Growth query: " . $sqlCustomerGrowth);

if ($stmt = mysqli_prepare($link, $sqlCustomerGrowth)) {
     error_log("DB Info (fetch_customer_growth.php): Customer Growth query prepared successfully.");

     // Bind parameters: Date format string, then date parameters (if used), then LIMIT/OFFSET
     $types = 's' . $bindParamTypes . 'ii'; // 's' for date format, then date types, 'ii' for LIMIT and OFFSET (integers)
     $args = [$dateFormat]; // Start with date format string

     // Add date parameters if they exist
     if (!empty($bindParams)) {
         $args = array_merge($args, $bindParams);
     }

     // Add LIMIT and OFFSET parameters
     $args[] = $offset;
     $args[] = $itemsPerPage;

     // Bind parameters using the unpacking operator (...)
     mysqli_stmt_bind_param($stmt, $types, ...$args);

    error_log("DB Info (fetch_customer_growth.php): Executing Customer Growth query.");
    if (mysqli_stmt_execute($stmt)) {
        error_log("DB Info (fetch_customer_growth.php): Customer Growth query executed successfully.");
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
             error_log("DB Info (fetch_customer_growth.php): Customer Growth get_result successful.");
            $growthData = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $growthData[] = $row;
            }
            error_log("DEBUG: Customer Growth Raw Result Count (Page): " . count($growthData)); // Log number of rows fetched for the page
            // error_log("DEBUG: Customer Growth Raw Data (Page): " . print_r($growthData, true)); // Log actual data (can be verbose)

            mysqli_free_result($result);

            $response['success'] = true;
            $response['message'] = 'Customer growth data fetched successfully.';
            $response['growth_data'] = $growthData;
            $response['total_records'] = $totalRecords; // Add total count to response

        } else {
             error_log('DB Error (fetch_customer_growth.php): Customer Growth get_result failed: ' . mysqli_stmt_error($stmt));
             $response['message'] = 'Database error getting customer growth result: ' . mysqli_stmt_error($stmt);
             $response['success'] = false; // Set success to false on failure
        }
    } else {
        error_log('DB Error (fetch_customer_growth.php): Customer Growth execute: ' . mysqli_stmt_error($stmt));
         $response['message'] = 'Database error fetching customer growth: ' . mysqli_stmt_error($stmt);
         $response['success'] = false; // Set success to false on failure
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_customer_growth.php): Customer Growth prepare: ' . mysqli_error($link));
     $response['message'] = 'Database error preparing customer growth query: ' . mysqli_error($link);
     $response['success'] = false; // Set success to false on failure
}


// Close the database connection (optional, PHP does this at end of script)
// if (isset($link)) { mysqli_close($link); }

// Send the JSON response back to the frontend
error_log("DEBUG: Final Response (fetch_customer_growth.php): " . json_encode($response));
echo json_encode($response);

// Note: No closing PHP tag here is intentional.
?>