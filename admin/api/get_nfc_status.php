<?php
// api/get_nfc_status.php
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

$stmt = $link->prepare(
    "SELECT s.student_id, s.full_name, s.username, s.contact_number, s.student_email, s.parent_email, nc.current_balance 
     FROM nfc_card nc 
     JOIN student s ON nc.student_id = s.student_id 
     WHERE nc.nfc_id = ?"
);
$stmt->bind_param("s", $nfc_id);
$stmt->execute();
$result = $stmt->get_result();

if ($data = $result->fetch_assoc()) {
    // Card Found
    echo json_encode(['success' => true, 'found' => true, 'data' => $data]);
} else {
    // Card Not Found
    echo json_encode(['success' => true, 'found' => false, 'data' => null]);
}

$stmt->close();
$link->close();
?>
