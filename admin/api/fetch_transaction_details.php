<?php
// admin/api/fetch_transaction_details.php - Backend API for fetching a single transaction's details

// Set content type to JSON
header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check for admin login (basic security)
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Include database connection
require_once '../../includes/db_connection.php'; // Adjust path as necessary

// Check if database connection is successful
if ($link === false) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Get transaction ID from GET request
$txn_id = isset($_GET['txn_id']) ? intval($_GET['txn_id']) : 0;

if ($txn_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID provided.']);
    mysqli_close($link);
    exit();
}

$transaction = null;
$items = [];

try {
    // Fetch transaction details
    $sql = "SELECT
                t.txn_id,
                t.nfc_id,
                t.total_amount,
                t.transaction_time,
                t.status,
                s.full_name AS student_name,
                s.student_id
            FROM `transaction` t
            LEFT JOIN `student` s ON t.student_id = s.student_id
            WHERE t.txn_id = ?";

    $stmt = $link->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Failed to prepare transaction details statement: ' . $link->error);
    }

    $stmt->bind_param('i', $txn_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();

    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
        mysqli_close($link);
        exit();
    }

    // Fetch transaction items
    $itemsSql = "SELECT
                    ti.quantity,
                    ti.unit_price,
                    f.name AS food_name,
                    f.image_path -- Added image_path
                 FROM `transaction_item` ti
                 JOIN `food` f ON ti.food_id = f.food_id
                 WHERE ti.txn_id = ?";

    $itemsStmt = $link->prepare($itemsSql);
    if ($itemsStmt === false) {
        // Log this error but still return transaction details
        error_log('Failed to prepare transaction items statement: ' . $link->error);
        $items = []; // Ensure items array is empty
    } else {
        $itemsStmt->bind_param('i', $txn_id);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        while ($row = $itemsResult->fetch_assoc()) {
            $items[] = $row;
        }
        $itemsStmt->close();
    }

    $transaction['items'] = $items;

    echo json_encode([
        'success' => true,
        'transaction' => $transaction
    ]);

} catch (Exception $e) {
    error_log('Error in fetch_transaction_details.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching transaction details.']);
} finally {
    if ($link) {
        mysqli_close($link);
    }
    exit(); // Ensure no further output
}
?>
