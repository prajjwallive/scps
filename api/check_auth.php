<?php
// scps1/api/check_auth.php - Checks if a student is currently logged in

session_start();
header('Content-Type: application/json');

if (isset($_SESSION['current_student_info']) && !empty($_SESSION['current_student_info']['student_id'])) {
    echo json_encode([
        'is_logged_in' => true,
        'student_name' => $_SESSION['current_student_info']['student_name'],
        'student_balance' => $_SESSION['current_student_info']['student_balance']
    ]);
} else {
    echo json_encode(['is_logged_in' => false]);
}
?>
