<?php
// admin/api/fetch_staff.php - Backend API to fetch staff members with pagination

// Set your default timezone
date_default_timezone_set('Asia/Kathmandu'); // Replace with your actual timezone if different

session_start();

// --- REQUIRE ADMIN LOGIN ---
// Ensure only logged-in admins can access this
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection
// Path: From admin/api/ UP two levels (../../) THEN into includes/
require_once '../../includes/db_connection.php';

// --- Check Database Connection ---
// $link should be established in db_connection.php
if ($link === false) {
    error_log('DB Error (fetch_staff.php): Could not connect to database: ' . mysqli_connect_error());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please check server logs.']);
    exit();
}
// --- End Check Database Connection ---

// Set the response header to indicate JSON content
header('Content-Type: application/json');

// Initialize the response array
$response = [
    'success' => false,
    'message' => 'An error occurred while fetching staff members.',
    'staff' => [],
    'total_records' => 0
];

// Get pagination parameters from the GET request (sanitize and validate)
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$itemsPerPage = filter_input(INPUT_GET, 'itemsPerPage', FILTER_VALIDATE_INT, ['options' => ['default' => 10, 'min_range' => 1]]);
$offset = ($page - 1) * $itemsPerPage;

// Optional: Search parameter (add to WHERE clause if used in frontend)
// $searchTerm = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING));

// --- Build WHERE Clause (primarily for search, if implemented later) ---
$whereClauses = [];
$bindParams = [];   // For prepared statement values
$bindParamTypes = ""; // For prepared statement types (e.g., "s" for string, "i" for integer)

/* Example Search Implementation (if you add a search bar on staff.php)
if (!empty($searchTerm)) {
    $searchTermWildcard = "%" . $searchTerm . "%";
    // Search in full_name, username, or role. Adjust fields as needed.
    $whereClauses[] = "(s.full_name LIKE ? OR s.username LIKE ? OR s.role LIKE ?)";
    $bindParams[] = $searchTermWildcard;
    $bindParams[] = $searchTermWildcard;
    $bindParams[] = $searchTermWildcard;
    $bindParamTypes .= "sss"; // Three string parameters
    error_log("DEBUG (fetch_staff.php): Search Term Used: " . $searchTerm);
}
*/

$fullWhereClause = '';
if (!empty($whereClauses)) {
    $fullWhereClause = " WHERE " . implode(" AND ", $whereClauses);
}

// --- Query to get Total Records (for pagination) ---
// This query counts all records matching the filters (if any), without pagination limits.
$sqlTotalCount = "SELECT COUNT(*) AS total_records FROM staff s" . $fullWhereClause;
$totalRecords = 0;

error_log("DB Info (fetch_staff.php): Preparing Total Count query: " . $sqlTotalCount);

if ($stmtCount = mysqli_prepare($link, $sqlTotalCount)) {
    if (!empty($bindParams)) { // Bind search parameters if they exist
        mysqli_stmt_bind_param($stmtCount, $bindParamTypes, ...$bindParams);
    }

    if (mysqli_stmt_execute($stmtCount)) {
        $resultCount = mysqli_stmt_get_result($stmtCount);
        if ($resultCount && $rowCount = mysqli_fetch_assoc($resultCount)) {
            $totalRecords = (int)$rowCount['total_records'];
        } else {
            error_log('DB Info (fetch_staff.php): Total Count fetch_assoc returned no rows or failed.');
        }
        if ($resultCount) mysqli_free_result($resultCount);
    } else {
        error_log('DB Error (fetch_staff.php): Total Count execute failed: ' . mysqli_stmt_error($stmtCount));
    }
    mysqli_stmt_close($stmtCount);
} else {
    error_log('DB Error (fetch_staff.php): Total Count prepare failed: ' . mysqli_error($link));
}
$response['total_records'] = $totalRecords; // Add to response regardless of main query success

// --- Main Query for Staff Data (with pagination) ---
// Select desired fields from the staff table.
$sqlStaff = "
    SELECT
        s.staff_id,
        s.full_name,
        s.username,
        s.role,
        s.is_active,
        s.last_login,
        s.created_at
    FROM
        staff s  -- 's' is an alias for the staff table
    " . $fullWhereClause . "
    ORDER BY
        s.full_name ASC  -- Or s.staff_id ASC, s.created_at DESC, etc.
    LIMIT ?, ?"; // Placeholders for OFFSET and itemsPerPage

error_log("DB Info (fetch_staff.php): Preparing Staff List query: " . $sqlStaff);

if ($stmt = mysqli_prepare($link, $sqlStaff)) {
    // Combine bind types and params for WHERE clause (if any) with those for LIMIT
    $currentBindParamTypes = $bindParamTypes . 'ii'; // Add 'ii' for LIMIT's offset and itemsPerPage (integers)
    
    // Create a new array for all bind parameters in the correct order
    $allBindParams = $bindParams; // Start with search/filter parameters
    $allBindParams[] = $offset;        // Add offset for LIMIT
    $allBindParams[] = $itemsPerPage;  // Add itemsPerPage for LIMIT

    // Bind parameters only if there are types defined (i.e., if there are parameters to bind)
    // For a query with only LIMIT, types would be 'ii' and params would be $offset, $itemsPerPage
    mysqli_stmt_bind_param($stmt, $currentBindParamTypes, ...$allBindParams);
    
    error_log("DB Info (fetch_staff.php): Executing Staff List query. Offset: $offset, Limit: $itemsPerPage");

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $staffList = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Ensure 'is_active' is an integer for consistent handling in JS
                $row['is_active'] = (int)$row['is_active'];
                // Dates are usually fine as strings, JS can parse them.
                // If specific formatting is needed before sending to JS, do it here.
                $staffList[] = $row;
            }
            mysqli_free_result($result);

            $response['success'] = true;
            $response['message'] = 'Staff members fetched successfully.';
            $response['staff'] = $staffList;
            // total_records is already set above
        } else {
            // This else might not be reached if get_result itself fails, caught by mysqli_stmt_error
            error_log('DB Error (fetch_staff.php): Staff List get_result returned false/null.');
            $response['message'] = 'Database error: Could not retrieve staff list result.';
        }
    } else {
        error_log('DB Error (fetch_staff.php): Staff List execute failed: ' . mysqli_stmt_error($stmt));
        $response['message'] = 'Database error: Could not execute staff list query. Details: ' . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_staff.php): Staff List prepare failed: ' . mysqli_error($link));
    $response['message'] = 'Database error: Could not prepare staff list query. Details: ' . mysqli_error($link);
}

// Close the database connection (optional as PHP does this at script end, but good practice)
if (isset($link) && $link) { // Check if $link is a valid resource
    mysqli_close($link);
}

// Send the JSON response back to the frontend
error_log("DEBUG (fetch_staff.php): Final Response: " . json_encode($response));
echo json_encode($response);

?>