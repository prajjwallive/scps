<?php
// admin/api/fetch_categories.php - Fetches food categories from the 'food' table

header('Content-Type: application/json');

// Include database connection
require_once '../../includes/db_connection.php'; // Adjust path as necessary from admin/api/

// Check database connection
if (!$link) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$categories = [];
// Select distinct values from the 'category' column in the 'food' table
$sql = "SELECT DISTINCT category FROM food ORDER BY category ASC";
$result = mysqli_query($link, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Ensure the 'category' column exists and is not empty
        if (!empty($row['category'])) {
            // Renaming the column from 'category' to 'name' to match the 'category.name' expectation in sales.js
            $categories[] = ['name' => $row['category']];
        }
    }
    echo json_encode(['success' => true, 'categories' => $categories]);
} else {
    error_log("Error fetching categories: " . mysqli_error($link));
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve categories.']);
}

mysqli_close($link);
?>
