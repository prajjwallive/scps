<?php
// api/update_student_details.php
session_start();
header('Content-Type: application/json');
require_once '../../includes/db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Basic Input Validation
$student_id = trim($_POST['student_id'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$contact = trim($_POST['contact_number'] ?? '');
$student_email = filter_var(trim($_POST['student_email'] ?? ''), FILTER_VALIDATE_EMAIL);
$parent_email = filter_var(trim($_POST['parent_email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (empty(trim($_POST['parent_email'] ?? ''))) $parent_email = ''; // Allow empty
$pin = trim($_POST['pin'] ?? '');

if (empty($student_id) || empty($full_name) || empty($username) || empty($contact) || empty($student_email)) {
    echo json_encode(['success' => false, 'message' => 'All fields except PIN are required.']);
    exit();
}

// Update student table
$stmt = $link->prepare("UPDATE student SET full_name = ?, contact_number = ?, student_email = ?, parent_email = ?, username = ? WHERE student_id = ?");
$stmt->bind_param("sssssi", $full_name, $contact, $student_email, $parent_email, $username, $student_id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to update student details. The username or email might already be taken by another student.']);
    exit();
}
$stmt->close();

// Optionally update PIN
if (!empty($pin)) {
    if (!preg_match('/^\d{4}$/', $pin)) {
        echo json_encode(['success' => false, 'message' => 'If setting a new PIN, it must be exactly 4 digits. Student details were updated, but PIN was not.']);
        exit();
    }
    $pin_hash = password_hash($pin, PASSWORD_DEFAULT);
    $stmt_pin = $link->prepare("UPDATE nfc_card SET pin_hash = ? WHERE student_id = ?");
    $stmt_pin->bind_param("si", $pin_hash, $student_id);
    $stmt_pin->execute();
    $stmt_pin->close();
}

echo json_encode(['success' => true, 'message' => 'Student information updated successfully!']);
$link->close();
?>
