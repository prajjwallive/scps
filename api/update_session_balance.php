<?php
// scps1/api/update_session_balance.php - Updates the student's balance in the session

session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$new_balance = $input['new_balance'] ?? null;

if (isset($_SESSION['current_student_info']) && $new_balance !== null) {
    $_SESSION['current_student_info']['student_balance'] = $new_balance;
    echo json_encode(['success' => true, 'message' => 'Session balance updated.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update session balance.']);
}
?>
