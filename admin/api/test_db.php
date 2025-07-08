<?php
// admin/api/test_db.php - Simple script to test database connection

// Path to your db_connection.php
require_once '../../includes/db_connection.php'; // Ensure this path is correct

header('Content-Type: application/json');

if (isset($link) && $link !== false) {
    // Connection successful
    echo json_encode(['success' => true, 'message' => 'Database connection successful!', 'server_info' => mysqli_get_server_info($link)]);
    mysqli_close($link); // Close the connection
} else {
    // Connection failed
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Check db_connection.php and credentials.']);
}

// Note: No closing PHP tag is intentional
?>
