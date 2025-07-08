<?php
// admin/api/fetch_recent_orders.php - Backend API to fetch recent orders for the dashboard with updated table/column names

// Set the default timezone to match where transactions are recorded (e.g., Nepal Time)
// This is CRITICAL for correct date/time comparisons and ordering.
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

// --- Check Database Connection Variable ---
// Explicitly check if $link is available and is a valid mysqli connection object
if (!isset($link) || $link === false) {
    // Log the error
    $error_message = "Database connection variable (\$link) not available or connection failed in fetch_recent_orders.php";
    error_log($error_message);
    // Set response for frontend
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection not established.']);
    exit(); // Stop script execution
}
// --- End Check Database Connection Variable ---


// Set the response header to indicate JSON content
header('Content-Type: application/json');

$response = ["success" => false, "message" => "An error occurred.", "recent_orders" => []];

// Define the number of recent orders to fetch
$limit = 5; // You can adjust this number

// SQL Query to fetch recent orders
// Use transaction table with txn_id, transaction_time, total_amount, status, student_id
// Join transaction_item on txn_id to count items
// Filter by status = 'success' as per dashboard.php
// Order by transaction_time DESC (Using transaction_time as seen in your dashboard.php)

$sql = "SELECT
            t.txn_id, -- Use txn_id as per dashboard.php
            t.transaction_time, -- Use transaction_time as per dashboard.php
            t.total_amount, -- Assuming total_amount is stored in the transaction table
            COUNT(ti.item_id) AS total_items_count -- Count item_id from transaction_item (assuming item_id exists)
            -- If item_id doesn't exist, use COUNT(ti.txn_id) or COUNT(ti.quantity)
        FROM
            transaction t -- Use transaction table as per dashboard.php
        LEFT JOIN
            transaction_item ti ON t.txn_id = ti.txn_id -- Join on txn_id as per dashboard.php
        WHERE
            t.status = 'success' -- Filter for successful transactions
        GROUP BY
            t.txn_id, t.transaction_time, t.total_amount -- Group by transaction details
        ORDER BY
            t.transaction_time DESC -- Order by most recent first
        LIMIT ?"; // Limit the number of results


// Prepare the SQL statement using $link
// Error is happening on the line below (around line 67)
if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind the limit parameter (integer)
    mysqli_stmt_bind_param($stmt, "i", $limit);

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        // Get the result using $stmt
        $result = mysqli_stmt_get_result($stmt);

        // Fetch all recent orders into an array
        $recentOrders = [];
        if ($result) {
             while ($row = mysqli_fetch_assoc($result)) {
                 // Format the date/time for display
                 $timestamp = strtotime($row['transaction_time']); // Use transaction_time
                 // Use the same date format as seen in the screenshot (e.g., 9 May 2025, 08:10 AM)
                 $formattedDate = date('j M Y, h:i A', $timestamp);

                $recentOrders[] = [
                    "transaction_id" => $row['txn_id'], // Use txn_id
                    "transaction_date" => $row['transaction_time'], // Use transaction_time for original date
                    "formatted_date" => $formattedDate, // Formatted date for display
                    "total_amount" => (float)$row['total_amount'], // Ensure it's a float
                    "total_items_count" => (int)$row['total_items_count'] // Ensure it's an integer
                ];
            }
            mysqli_free_result($result);
        }


        // Check if any orders were fetched
        if (!empty($recentOrders)) {
            $response["success"] = true;
            $response["message"] = "Recent orders fetched successfully.";
            $response["recent_orders"] = $recentOrders;
            http_response_code(200); // OK
        } else {
            // Query executed but returned no rows
             $response["success"] = true; // Still success, just no data
             $response["message"] = "No recent orders found.";
             $response["recent_orders"] = []; // Return empty array
             http_response_code(200); // OK
        }

    } else {
        // Statement execution failed
        http_response_code(500); // Internal Server Error
        $response["message"] = "Error executing query: " . mysqli_stmt_error($stmt);
        // Log the error for debugging on the server side
        error_log("Fetch Recent Orders API Error: " . mysqli_stmt_error($stmt));
         $response["success"] = false; // Set success to false on failure
    }

    // Close the statement
    mysqli_stmt_close($stmt);
} else {
    // Statement preparation failed
    http_response_code(500); // Internal Server Error
    $response["message"] = "Error preparing query: " . mysqli_error($link);
     // Log the error for debugging on the server side
    error_log("Fetch Recent Orders API Error: " . mysqli_error($link));
     $response["success"] = false; // Set success to false on failure
}

// Close the database connection (optional, PHP does this at end of script)
// if (isset($link)) { mysqli_close($link); } // Use $link

// Return JSON response
echo json_encode($response);

// Note: No closing PHP tag here is intentional.
?>
