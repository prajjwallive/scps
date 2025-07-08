<?php
// includes/db_connection.php - Example Database Connection using $link

// Database credentials (REPLACE WITH YOUR ACTUAL CREDENTIALS)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'smart_canteen');

// Attempt to connect to MySQL database
// Use the $link variable name as expected by your API files
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
// Check connection
if ($link === false) {
    // Log the error instead of just dying, so the API can return a JSON error
    error_log("Database Connection Error: Could not connect. " . mysqli_connect_error());
    // Optionally, you could set $link to null explicitly here, though mysqli_connect
    // often returns false on failure, which is also handled.
    // $link = null; // Explicitly set to null on failure
} else {
    // Optional: Set charset to UTF-8
    mysqli_set_charset($link, "utf8mb4");
}

// The connection is now available in the $link variable
// This file should NOT output anything else.

// Note: No closing PHP tag is intentional
