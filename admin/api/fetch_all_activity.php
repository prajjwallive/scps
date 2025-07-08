<?php
// admin/api/fetch_all_activity.php - Backend API to fetch all activity log entries with filters and pagination

// Set the default timezone
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
    // Log the error
    error_log('DB Error (fetch_all_activity.php): Could not connect to database: ' . mysqli_connect_error());
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
    'message' => 'Error fetching activity log.',
    'activity_log' => [], // Array to hold activity log data for the current page
    'total_records' => 0 // Total number of rows before pagination
];

// Get filter and pagination parameters from the GET request
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT); // Current page number
$itemsPerPage = filter_input(INPUT_GET, 'itemsPerPage', FILTER_VALIDATE_INT); // Items per page
$dateRangeType = filter_input(INPUT_GET, 'dateRange', FILTER_SANITIZE_STRING); // e.g., 'today', 'this_month', 'all_time'
$activityType = filter_input(INPUT_GET, 'activityType', FILTER_SANITIZE_STRING); // e.g., 'login', 'product_added', 'all'
$adminUser = filter_input(INPUT_GET, 'adminUser', FILTER_UNSAFE_RAW); // Admin staff_id or 'all'
// Optional: $searchQuery = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING); // Search term

// Default pagination values
$page = ($page > 0) ? $page : 1;
$itemsPerPage = ($itemsPerPage > 0) ? $itemsPerPage : 10; // Default to 10 items per page
$offset = ($page - 1) * $itemsPerPage;

// --- Build WHERE Clause based on Filters ---
$whereClauses = [];
$bindParams = [];
$bindParamTypes = "";

// Date Range Filter
if ($dateRangeType !== 'all_time') {
    $startDate = null;
    $endDate = null;
    $today = new DateTime('now', new DateTimeZone(date_default_timezone_get())); // Use server's default timezone initially

    try {
        $timezone = new DateTimeZone(date_default_timezone_get()); // Use the set timezone

        switch ($dateRangeType) {
            case 'today':
                $startDate = (clone $today)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
                $endDate = (clone $today)->setTime(23, 59, 59)->format('Y-m-d H:i:s');
                break;
            case 'this_week':
                $startOfWeek = (clone $today)->modify('this week')->setTime(0, 0, 0); // Assuming week starts Monday. Use 'this week Sunday' for Sunday start.
                $startDate = $startOfWeek->format('Y-m-d H:i:s');
                $endDate = (clone $today)->setTime(23, 59, 59)->format('Y-m-d H:i:s'); // End date is today
                break;
            case 'this_month':
                $startDate = (clone $today)->modify('first day of this month')->setTime(0, 0, 0)->format('Y-m-d H:i:s');
                $endDate = (clone $today)->setTime(23, 59, 59)->format('Y-m-d H:i:s'); // End date is today
                break;
            case 'last_month':
                $startOfLastMonth = (clone $today)->modify('first day of last month')->setTime(0, 0, 0);
                $endOfLastMonth = (clone $today)->modify('last day of last month')->setTime(23, 59, 59);
                $startDate = $startOfLastMonth->format('Y-m-d H:i:s');
                $endDate = $endOfLastMonth->format('Y-m-d H:i:s');
                break;
            case 'this_year':
                 $startDate = (clone $today)->modify('first day of january this year')->setTime(0, 0, 0)->format('Y-m-d H:i:s');
                 $endDate = (clone $today)->setTime(23, 59, 59)->format('Y-m-d H:i:s'); // End date is today
                 break;
            // Add cases for custom date range if implemented in frontend
            // case 'custom':
            //     $customStartDate = filter_input(INPUT_GET, 'startDate', FILTER_UNSAFE_RAW);
            //     $customEndDate = filter_input(INPUT_GET, 'endDate', FILTER_UNSAFE_RAW);
            //     if ($customStartDate && $customEndDate) {
            //          $startDate = (new DateTime($customStartDate, $timezone))->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            //          $endDate = (new DateTime($customEndDate, $timezone))->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            //     }
            //     break;
        }

        if ($startDate !== null && $endDate !== null) {
            $whereClauses[] = "al.timestamp BETWEEN ? AND ?"; // Use alias 'al' for activity_log
            $bindParams[] = $startDate;
            $bindParams[] = $endDate;
            $bindParamTypes .= "ss";
             error_log("DEBUG: Activity Log Date Filter: Start=" . $startDate . ", End=" . $endDate);
        } else {
             error_log("DEBUG: Activity Log Date Filter: Invalid date range type or custom dates missing.");
        }

    } catch (Exception $e) {
         error_log('Date Processing Error (fetch_all_activity.php): ' . $e->getMessage());
         // Continue without date filter if there's an error
    }
} else {
     error_log("DEBUG: Activity Log Date Filter: All Time selected.");
}

// Activity Type Filter
if ($activityType !== 'all' && $activityType !== null && $activityType !== '') {
    $whereClauses[] = "al.activity_type = ?";
    $bindParams[] = $activityType;
    $bindParamTypes .= "s";
     error_log("DEBUG: Activity Type Filter: " . $activityType);
} else {
     error_log("DEBUG: Activity Type Filter: All Types selected.");
}

// Admin User Filter
// Assuming 'adminUser' from frontend is the staff_id
if ($adminUser !== 'all' && $adminUser !== null && $adminUser !== '') {
    // Validate adminUser as integer
    $adminUserId = filter_var($adminUser, FILTER_VALIDATE_INT);
    if ($adminUserId !== false && $adminUserId !== null) {
        $whereClauses[] = "al.admin_id = ?";
        $bindParams[] = $adminUserId;
        $bindParamTypes .= "i";
         error_log("DEBUG: Admin User Filter: Admin ID " . $adminUserId);
    } else {
         error_log("DEBUG: Admin User Filter: Invalid Admin User ID received: " . $adminUser);
         // Optionally add an error message to the response or ignore the filter
    }
} else {
     error_log("DEBUG: Admin User Filter: All Admins selected.");
}

// Optional: Search Filter (if implemented)
// if ($searchQuery !== null && $searchQuery !== '') {
//     $whereClauses[] = "(al.description LIKE ? OR s.username LIKE ?)"; // Search in description or admin username
//     $bindParams[] = "%" . $searchQuery . "%";
//     $bindParams[] = "%" . $searchQuery . "%";
//     $bindParamTypes .= "ss";
//      error_log("DEBUG: Search Query: " . $searchQuery);
// }


// Construct the full WHERE clause
$fullWhereClause = '';
if (!empty($whereClauses)) {
    $fullWhereClause = " WHERE " . implode(" AND ", $whereClauses);
}

// --- Query to get Total Records (for pagination) ---
// Count total records matching the filters without LIMIT/OFFSET
$sqlTotalCount = "
    SELECT COUNT(*) AS total_records
    FROM activity_log al
    LEFT JOIN staff s ON al.admin_id = s.staff_id
    " . $fullWhereClause;

$totalRecords = 0;
error_log("DB Info (fetch_all_activity.php): Preparing Total Count query: " . $sqlTotalCount);
if ($stmtCount = mysqli_prepare($link, $sqlTotalCount)) {
    error_log("DB Info (fetch_all_activity.php): Total Count query prepared successfully.");

    // Bind parameters for the count query (same as main query's where clause)
    if (!empty($bindParams)) {
         // Use call_user_func_array for compatibility with older PHP versions if needed,
         // but ...$bindParams is cleaner for PHP 5.6+
         mysqli_stmt_bind_param($stmtCount, $bindParamTypes, ...$bindParams);
         // For older PHP versions:
         // call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmtCount, $bindParamTypes], $bindParams));
    }


    error_log("DB Info (fetch_all_activity.php): Executing Total Count query.");
    if (mysqli_stmt_execute($stmtCount)) {
        error_log("DB Info (fetch_all_activity.php): Total Count query executed successfully.");
        $resultCount = mysqli_stmt_get_result($stmtCount);
        if ($resultCount && $rowCount = mysqli_fetch_assoc($resultCount)) {
            $totalRecords = $rowCount['total_records'];
            error_log("DEBUG: Total Activity Records Count: " . $totalRecords);
        } else {
             error_log('DB Info (fetch_all_activity.php): Total Count fetch assoc returned no rows.');
        }
        if ($resultCount) mysqli_free_result($resultCount);
    } else {
        error_log('DB Error (fetch_all_activity.php): Total Count execute: ' . mysqli_stmt_error($stmtCount));
        // Don't set success to false yet, let the main query run
    }
    mysqli_stmt_close($stmtCount);
} else {
    error_log('DB Error (fetch_all_activity.php): Total Count prepare: ' . mysqli_error($link));
    // Don't set success to false yet
}

// --- Main Query for Activity Log Data ---
// Select activity log entries with admin username, applying filters, ordering, and pagination
$sqlActivityLog = "
    SELECT
        al.activity_id, -- Include activity_id if needed for future features
        al.timestamp,
        al.activity_type,
        al.description,
        s.username AS admin_username -- Get admin's username from staff table
    FROM
        activity_log al
    LEFT JOIN -- Use LEFT JOIN in case an admin user was deleted but activity remains
        staff s ON al.admin_id = s.staff_id
    " . $fullWhereClause . "
    ORDER BY
        al.timestamp DESC -- Order by most recent activity first
    LIMIT ?, ?"; // Add LIMIT and OFFSET for pagination

error_log("DB Info (fetch_all_activity.php): Preparing Activity Log query: " . $sqlActivityLog);

if ($stmt = mysqli_prepare($link, $sqlActivityLog)) {
     error_log("DB Info (fetch_all_activity.php): Activity Log query prepared successfully.");

     // Bind parameters: first the parameters for the WHERE clause, then the LIMIT and OFFSET
     $types = $bindParamTypes . 'ii'; // Add 'ii' for LIMIT and OFFSET (integers)

     // The args array contains the values for all parameters in order.
     $args = $bindParams; // Start with the parameters for the WHERE clause

     // Add values for LIMIT and OFFSET parameters
     $args[] = $offset;
     $args[] = $itemsPerPage;

     // Bind parameters using the unpacking operator (...)
     // Check if $args is empty before unpacking if PHP version is older than 5.6
     if (!empty($args)) {
          mysqli_stmt_bind_param($stmt, $types, ...$args);
          // For older PHP versions:
          // call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $types], $args));
     }


    error_log("DB Info (fetch_all_activity.php): Executing Activity Log query.");
    if (mysqli_stmt_execute($stmt)) {
        error_log("DB Info (fetch_all_activity.php): Activity Log query executed successfully.");
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
             error_log("DB Info (fetch_all_activity.php): Activity Log get_result successful.");
            $activityLogData = [];
            while ($row = mysqli_fetch_assoc($result)) {
                // Ensure numeric values are treated as numbers if any are added later
                // $row['some_numeric_field'] = (float)$row['some_numeric_field'];
                $activityLogData[] = $row;
            }
            error_log("DEBUG: Activity Log Raw Result Count (Page): " . count($activityLogData)); // Log number of rows fetched for the page
            // error_log("DEBUG: Activity Log Raw Data (Page): " . print_r($activityLogData, true)); // Log actual data (can be verbose)

            mysqli_free_result($result);

            $response['success'] = true;
            $response['message'] = 'Activity log data fetched successfully.';
            $response['activity_log'] = $activityLogData;
            $response['total_records'] = $totalRecords; // Add total count to response

        } else {
             error_log('DB Error (fetch_all_activity.php): Activity Log get_result failed: ' . mysqli_stmt_error($stmt));
             $response['message'] = 'Database error getting activity log result: ' . mysqli_stmt_error($stmt);
             $response['success'] = false; // Set success to false on failure
        }
    } else {
        error_log('DB Error (fetch_all_activity.php): Activity Log execute: ' . mysqli_stmt_error($stmt));
         $response['message'] = 'Database error fetching activity log: ' . mysqli_stmt_error($stmt);
         $response['success'] = false; // Set success to false on failure
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_all_activity.php): Activity Log prepare: ' . mysqli_error($link));
     $response['message'] = 'Database error preparing activity log query: ' . mysqli_error($link);
     $response['success'] = false; // Set success to false on failure
}


// Close the database connection (optional, PHP does this at end of script)
// if (isset($link)) { mysqli_close($link); }

// Send the JSON response back to the frontend
error_log("DEBUG: Final Response (fetch_all_activity.php): " . json_encode($response));
echo json_encode($response);

// Note: No closing PHP tag here is intentional.
?>
