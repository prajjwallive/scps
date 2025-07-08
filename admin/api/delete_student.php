<?php
// api/delete_student.php
session_start();
header('Content-Type: application/json');
require_once '../../includes/db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$student_id = $input['student_id'] ?? null;

if (empty($student_id)) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required.']);
    exit();
}

// The database is set up with ON DELETE CASCADE,
// so deleting the student from the `student` table
// should automatically remove their related records
// in `nfc_card` and `transaction`.
$stmt = $link->prepare("DELETE FROM student WHERE student_id = ?");
$stmt->bind_param("i", $student_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found or already deleted.']);
    }
} else {
    error_log("Delete Student Error: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'A database error occurred during deletion.']);
}

$stmt->close();
$link->close();
?>
