<?php
// admin/api/fetch_admin_users.php - Backend API to fetch a list of admin users for filters

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
    error_log('DB Error (fetch_admin_users.php): Could not connect to database: ' . mysqli_connect_error());
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
    'message' => 'Error fetching admin users.',
    'admin_users' => [] // Array to hold admin user data
];

// --- Fetch admin users from the database ---
// Assuming your 'staff' table has 'staff_id', 'username', 'role', and 'is_active' columns.
// Adjust the WHERE clause if you have a different way to identify admin users (e.g., a specific 'role').
$sql = "SELECT staff_id, username FROM staff WHERE is_active = 1 ORDER BY username ASC"; // Fetch active staff, order by username

error_log("DB Info (fetch_admin_users.php): Preparing query: " . $sql);

if ($stmt = mysqli_prepare($link, $sql)) {
     error_log("DB Info (fetch_admin_users.php): Query prepared successfully.");

    // No parameters to bind for this query
    // mysqli_stmt_bind_param($stmt, ...);

    error_log("DB Info (fetch_admin_users.php): Executing query.");
    if (mysqli_stmt_execute($stmt)) {
        error_log("DB Info (fetch_admin_users.php): Query executed successfully.");
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
             error_log("DB Info (fetch_admin_users.php): get_result successful.");
            $adminUsers = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $adminUsers[] = $row;
            }
            error_log("DEBUG: Admin Users Raw Result Count: " . count($adminUsers)); // Log number of rows fetched
            // error_log("DEBUG: Admin Users Raw Data: " . print_r($adminUsers, true)); // Log actual data (can be verbose)


            mysqli_free_result($result);

            $response['success'] = true;
            $response['message'] = 'Admin users fetched successfully.';
            $response['admin_users'] = $adminUsers;

        } else {
             error_log('DB Error (fetch_admin_users.php): get_result failed: ' . mysqli_stmt_error($stmt));
             $response['message'] = 'Database error getting admin users result: ' . mysqli_stmt_error($stmt);
             $response['success'] = false; // Set success to false on failure
        }
    } else {
        error_log('DB Error (fetch_admin_users.php): execute: ' . mysqli_stmt_error($stmt));
         $response['message'] = 'Database error fetching admin users: ' . mysqli_stmt_error($stmt);
         $response['success'] = false; // Set success to false on failure
    }
    mysqli_stmt_close($stmt);
} else {
    error_log('DB Error (fetch_admin_users.php): prepare: ' . mysqli_error($link));
     $response['message'] = 'Database error preparing admin users query: ' . mysqli_error($link);
     $response['success'] = false; // Set success to false on failure
}


// Close the database connection (optional, PHP does this at end of script)
// if (isset($link)) { mysqli_close($link); }

// Send the JSON response back to the frontend
error_log("DEBUG: Final Response (fetch_admin_users.php): " . json_encode($response));
echo json_encode($response);

// Note: No closing PHP tag here is intentional.
?>
