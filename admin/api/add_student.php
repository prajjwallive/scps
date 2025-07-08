<?php
// api/add_student.php
session_start();
header('Content-Type: application/json');
require_once '../../includes/db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Basic Input Validation
$full_name = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$contact = trim($_POST['contact_number'] ?? '');
$student_email = filter_var(trim($_POST['student_email'] ?? ''), FILTER_VALIDATE_EMAIL);
$parent_email = filter_var(trim($_POST['parent_email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (empty(trim($_POST['parent_email'] ?? ''))) $parent_email = ''; // Allow empty parent email
$nfc_id = trim($_POST['nfc_id'] ?? '');
$pin = trim($_POST['pin'] ?? '');

if (empty($full_name) || empty($username) || empty($contact) || empty($student_email) || empty($nfc_id) || empty($pin)) {
    echo json_encode(['success' => false, 'message' => 'All fields except Parent Email are required.']);
    exit();
}
if (!preg_match('/^\d{4}$/', $pin)) {
    echo json_encode(['success' => false, 'message' => 'PIN must be exactly 4 digits.']);
    exit();
}

// Check for existing NFC ID, username, or email
$stmt = $link->prepare("SELECT (SELECT COUNT(*) FROM nfc_card WHERE nfc_id = ?) as nfc_exists, (SELECT COUNT(*) FROM student WHERE username = ?) as user_exists, (SELECT COUNT(*) FROM student WHERE student_email = ?) as email_exists");
$stmt->bind_param("sss", $nfc_id, $username, $student_email);
$stmt->execute();
$check_result = $stmt->get_result()->fetch_assoc();
if ($check_result['nfc_exists'] > 0) {
    echo json_encode(['success' => false, 'message' => 'This NFC Card ID is already registered.']);
    exit();
}
if ($check_result['user_exists'] > 0) {
    echo json_encode(['success' => false, 'message' => 'This username is already taken.']);
    exit();
}
if ($check_result['email_exists'] > 0) {
    echo json_encode(['success' => false, 'message' => 'This student email is already registered.']);
    exit();
}
$stmt->close();

// Proceed with insertion
$pin_hash = password_hash($pin, PASSWORD_DEFAULT);
$link->begin_transaction();
try {
    // Insert into student table
    $stmt_student = $link->prepare("INSERT INTO student (full_name, contact_number, student_email, parent_email, username, nfc_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_student->bind_param("ssssss", $full_name, $contact, $student_email, $parent_email, $username, $nfc_id);
    $stmt_student->execute();
    $student_id = $link->insert_id;
    $stmt_student->close();

    // Insert into nfc_card table
    $stmt_nfc = $link->prepare("INSERT INTO nfc_card (nfc_id, student_id, pin_hash, current_balance, status) VALUES (?, ?, ?, 0.00, 'Active')");
    $stmt_nfc->bind_param("sis", $nfc_id, $student_id, $pin_hash);
    $stmt_nfc->execute();
    $stmt_nfc->close();

    $link->commit();
    echo json_encode(['success' => true, 'message' => 'Student added successfully!']);

} catch (Exception $e) {
    $link->rollback();
    error_log("Add Student Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}

$link->close();
?>
