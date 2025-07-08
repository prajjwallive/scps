<?php
// admin/delete_product.php - Handles deleting a product from the database via AJAX POST

// Start output buffering to catch any unexpected output (like PHP errors/warnings)
ob_start();

// Start the session
session_start();

// --- REQUIRE ADMIN LOGIN ---
// This script should only be accessible to logged-in admins
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    // Clear any buffered output before sending JSON
    ob_clean(); 
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection
// Path: From admin/ UP to root (../) THEN into includes/
require_once '../includes/db_connection.php'; // Make sure this path is correct and $link variable is created

// Initialize response array
$response = ['success' => false, 'message' => 'An error occurred.'];

// Check if the request method is POST and if food_id is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['food_id'])) {

    // --- Retrieve and Validate Input Data ---
    // Get the product ID and validate it as an integer
    $food_id = filter_input(INPUT_POST, 'food_id', FILTER_VALIDATE_INT);

    // Check if the food_id is valid
    if ($food_id === false || $food_id === null) {
        $response['message'] = 'Invalid product ID received.';
        error_log('admin/delete_product.php failed: Invalid food_id: ' . ($_POST['food_id'] ?? 'not set')); // Log the invalid input
    } else {
        // Validation passed, proceed to delete from database

        // --- Delete Product from Database ---
        // Using prepared statement to prevent SQL injection
        // Make sure the table name 'food' and column name 'food_id' match your database exactly
        $sql_delete = "DELETE FROM food WHERE food_id = ?";

        if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
            // Bind parameter (integer: i)
            mysqli_stmt_bind_param($stmt_delete, "i", $food_id);

            // Execute the prepared statement
            if (mysqli_stmt_execute($stmt_delete)) {
                // Check if any row was affected (deleted)
                if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                    // Product deleted successfully
                    $response['success'] = true;
                    $response['message'] = 'Product deleted successfully!';
                } else {
                    // No row found with that ID (product didn't exist)
                    $response['message'] = 'Product not found or already deleted.';
                    error_log('admin/delete_product.php failed: Product ID ' . $food_id . ' not found for deletion.');
                }

            } else {
                // Database execution error
                $response['message'] = 'Database error deleting product: ' . mysqli_stmt_error($stmt_delete); // Include MySQL error for debugging
                error_log('DB Error (admin/delete_product.php): execute delete: ' . mysqli_stmt_error($stmt_delete));
            }

            mysqli_stmt_close($stmt_delete); // Close statement

        } else {
            // Database preparation error
            $response['message'] = 'Database error preparing product delete: ' . mysqli_error($link); // Include MySQL error for debugging
            error_log('DB Error (admin/delete_product.php): prepare delete: ' . mysqli_error($link));
        }
    }

} else {
    // If the request method is not POST or food_id is not provided
    $response['message'] = 'Invalid request method or missing food_id.';
    error_log('admin/delete_product.php received non-POST or missing food_id.');
}

// Close the database connection
if (isset($link)) {
    mysqli_close($link);
}

// Clear any buffered output before sending the final JSON response
ob_clean(); 
// Set the response header to indicate JSON content
header('Content-Type: application/json');
// Send the JSON response
echo json_encode($response);

// Note: No closing PHP tag here is intentional to prevent accidental whitespace
