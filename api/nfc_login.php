<?php
// scps1/api/nfc_login.php - Handles NFC ID only login

session_start();
header('Content-Type: application/json');

require_once '../includes/db_connection.php';

if (!$link) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$nfcId = $input['nfc_id'] ?? '';

if (empty($nfcId)) {
    echo json_encode(['success' => false, 'message' => 'Please provide NFC ID.']);
    exit();
}

// 1. Verify NFC Card and Get Student ID, Balance, and Name
// IMPORTANT: Password hash is NOT fetched or verified here, as per user request for NFC ID ONLY login.
// Security for transactions relies on the subsequent password prompt during payment.
$stmt = $link->prepare("SELECT s.student_id, s.full_name, nc.current_balance, nc.status FROM nfc_card nc JOIN student s ON nc.student_id = s.student_id WHERE nc.nfc_id = ?");
if (!$stmt) {
    error_log("NFC Login Query Prepare Failed: " . $link->error);
    echo json_encode(['success' => false, 'message' => 'System error during login.']);
    exit();
}
$stmt->bind_param("s", $nfcId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid NFC ID or card not registered.']);
    $stmt->close();
    exit();
}

$nfc_data = $result->fetch_assoc();
$student_id = $nfc_data['student_id'];
$current_balance = $nfc_data['current_balance'];
$student_name = $nfc_data['full_name'];
$card_status = $nfc_data['status'];
$stmt->close();

// Check card status
if ($card_status !== 'Active') {
    echo json_encode(['success' => false, 'message' => 'Your NFC card is ' . $card_status . '. Cannot login.']);
    exit();
}

// Login successful: Store student info in session
$_SESSION['current_student_info'] = [
    'student_id' => $student_id,
    'nfc_id' => $nfcId, // Store NFC ID in session for payment process
    'student_name' => $student_name,
    'student_balance' => $current_balance
];

echo json_encode([
    'success' => true,
    'message' => 'Login successful!',
    'student_name' => $student_name,
    'student_balance' => $current_balance
]);

mysqli_close($link);
?>
