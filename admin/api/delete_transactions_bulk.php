<?php
// admin/api/delete_transactions_bulk.php - Handles bulk deletion of transactions

session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An error occurred.'];

// --- REQUIRE ADMIN LOGIN AND ROLE CHECK ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    $response['message'] = 'Unauthorized access. Please log in.';
    echo json_encode($response);
    exit();
}

// Check if the logged-in admin has permission to delete transactions
// Only 'super_administrator' and 'administrator' roles are allowed to delete
$admin_role = $_SESSION['admin_role'] ?? 'N/A';
if ($admin_role !== 'super_administrator' && $admin_role !== 'administrator') {
    $response['message'] = 'Permission denied. Only administrators can delete transactions.';
    echo json_encode($response);
    exit();
}
// --- END REQUIRE ADMIN LOGIN AND ROLE CHECK ---

require_once '../../includes/db_connection.php';

if ($link === false) {
    error_log('DB Error (delete_transactions_bulk.php): Could not connect to database: ' . mysqli_connect_error());
    $response['message'] = 'Database connection failed. Please check server logs.';
    echo json_encode($response);
    exit();
}

// Get raw POST data (JSON)
$input = json_decode(file_get_contents('php://input'), true);
$transaction_ids = $input['transaction_ids'] ?? [];

// Validate input: Ensure it's an array and contains integers
if (!is_array($transaction_ids) || empty($transaction_ids)) {
    $response['message'] = 'Invalid or empty list of transaction IDs provided.';
    mysqli_close($link);
    echo json_encode($response);
    exit();
}

// Sanitize IDs: Convert all to integers and filter out non-positive values
$sanitized_ids = array_filter(array_map('intval', $transaction_ids), function($id) {
    return $id > 0;
});

if (empty($sanitized_ids)) {
    $response['message'] = 'No valid transaction IDs provided for deletion.';
    mysqli_close($link);
    echo json_encode($response);
    exit();
}

// Create a comma-separated string of placeholders for the IN clause
$placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
// Create a string of 'i's for binding parameters (one 'i' for each integer ID)
$types = str_repeat('i', count($sanitized_ids));

$sql = "DELETE FROM `transaction` WHERE txn_id IN ($placeholders)";

if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind parameters dynamically
    mysqli_stmt_bind_param($stmt, $types, ...$sanitized_ids);

    if (mysqli_stmt_execute($stmt)) {
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        if ($affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = "Successfully deleted {$affected_rows} transaction(s).";
        } else {
            $response['message'] = 'No transactions found or deleted.';
        }
    } else {
        $response['message'] = 'Failed to execute deletion: ' . mysqli_stmt_error($stmt);
        error_log('DB Error (delete_transactions_bulk.php): ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Database error preparing deletion query: ' . mysqli_error($link);
    error_log('DB Error (delete_transactions_bulk.php): ' . mysqli_error($link));
}

mysqli_close($link);
echo json_encode($response);
?>
