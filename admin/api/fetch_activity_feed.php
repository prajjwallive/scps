<?php
// admin/api/fetch_activity_feed.php - Backend API to fetch recent activity feed for the dashboard with updated table/column names

// Set the default timezone to match your database/server time
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

// --- Check Database Connection ---
// Use $link as defined in your db_connection.php
if ($link === false) {
    // Log the connection error
    error_log('DB Error (fetch_activity_feed.php): Could not connect to database: ' . mysqli_connect_error());
    // Set response for frontend
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit(); // Stop script execution
}
// --- End Check Database Connection ---


// Set the response header to indicate JSON content
header('Content-Type: application/json');

$response = ["success" => false, "message" => "An error occurred.", "activity_feed" => []];

// Define the number of recent activities to fetch
$limit = 5; // You can adjust this number to match your dashboard layout

// SQL Query to fetch recent activity feed entries
// Assumes an 'activity_log' table exists with 'timestamp', 'activity_type', 'description', 'admin_id', 'user_id', 'related_id'
// Order by timestamp DESC

$sql = "SELECT
            activity_id,
            timestamp, -- Use timestamp as per common log table practice
            activity_type,
            description,
            admin_id, -- Include if you want to show which admin did it
            user_id,  -- Include if related to a user action
            related_id -- Include if related to a specific record (e.g., transaction_id)
        FROM
            activity_log -- Use activity_log table
        ORDER BY
            timestamp DESC -- Order by most recent first
        LIMIT ?"; // Limit the number of results


// Prepare the SQL statement using $link
if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind the limit parameter (integer)
    mysqli_stmt_bind_param($stmt, "i", $limit);

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        // Get the result using $stmt
        $result = mysqli_stmt_get_result($stmt);

        // Fetch all recent activity entries into an array
        $activityFeed = [];
        if ($result) {
             while ($row = mysqli_fetch_assoc($result)) {
                 // Format the date/time for display
                 $timestamp = strtotime($row['timestamp']);
                 // Use the same date format as seen in the screenshot (e.g., 2 hours ago, Yesterday)
                 // Implementing 'time ago' requires more complex logic. For simplicity,
                 // we'll format it to a standard date/time string for now.
                 $formattedDateTime = date('j M Y, h:i A', $timestamp); // e.g., 9 May 2025, 10:15 AM

                 // You could implement a 'time ago' function here if preferred in PHP
                 // $timeAgo = time_elapsed_string($row['timestamp']); // Requires a helper function


                $activityFeed[] = [
                    "activity_id" => $row['activity_id'],
                    "timestamp" => $row['timestamp'], // Keep original timestamp
                    // "time_ago" => $timeAgo, // If you implement time_elapsed_string
                    "formatted_timestamp" => $formattedDateTime, // Formatted timestamp for display
                    "activity_type" => $row['activity_type'],
                    "description" => $row['description'],
                    "admin_id" => $row['admin_id'], // May be null
                    "user_id" => $row['user_id'],   // May be null
                    "related_id" => $row['related_id'] // May be null
                ];
            }
            mysqli_free_result($result);
        }

        // Check if any entries were fetched
        if (!empty($activityFeed)) {
            $response["success"] = true;
            $response["message"] = "Activity feed fetched successfully.";
            $response["activity_feed"] = $activityFeed;
            http_response_code(200); // OK
        } else {
            // Query executed but returned no rows
             $response["success"] = true; // Still success, just no data
             $response["message"] = "No recent activity found.";
             $response["activity_feed"] = []; // Return empty array
             http_response_code(200); // OK
        }

    } else {
        // Statement execution failed
        http_response_code(500); // Internal Server Error
        $response["message"] = "Error executing query: " . mysqli_stmt_error($stmt);
        // Log the error for debugging on the server side
        error_log("Fetch Activity Feed API Error: " . mysqli_stmt_error($stmt));
         $response["success"] = false; // Set success to false on failure
    }

    // Close the statement
    mysqli_stmt_close($stmt);
} else {
    // Statement preparation failed
    http_response_code(500); // Internal Server Error
    $response["message"] = "Error preparing query: " . mysqli_error($link);
     // Log the error for debugging on the server side
    error_log("Fetch Activity Feed API Error: " . mysqli_error($link));
     $response["success"] = false; // Set success to false on failure
}

// Close the database connection (optional, PHP does this at end of script)
// if (isset($link)) { mysqli_close($link); } // Use $link

// Return JSON response
echo json_encode($response);

// Note: No closing PHP tag here is intentional.
?>
