<?php
// scps1/api/logout.php - Handles student logout

session_start();
header('Content-Type: application/json');

// Clear specific session data related to student login
if (isset($_SESSION['current_student_info'])) {
    unset($_SESSION['current_student_info']);
}

// Optionally, destroy the entire session if no other session data needs to persist
// session_destroy(); 

echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
?>
