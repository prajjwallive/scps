<?php
// api/fetch_student_details.php
session_start();
header('Content-Type: application/json');
require_once '../../includes/db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$nfc_id = trim($_GET['nfc_id'] ?? '');

if (empty($nfc_id)) {
    echo json_encode(['success' => false, 'message' => 'NFC ID is required.']);
    exit();
}

$stmt = $link->prepare("SELECT student_id, full_name, contact_number, student_email, parent_email, username FROM student WHERE nfc_id = ?");
$stmt->bind_param("s", $nfc_id);
$stmt->execute();
$result = $stmt->get_result();

if ($student = $result->fetch_assoc()) {
    echo json_encode(['success' => true] + $student);
} else {
    echo json_encode(['success' => false, 'message' => 'No student found with that NFC ID.']);
}

$stmt->close();
$link->close();
?>
