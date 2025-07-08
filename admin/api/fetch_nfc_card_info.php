<?php
// admin/api/fetch_nfc_card_info.php

date_default_timezone_set('Asia/Kathmandu');
session_start();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An error occurred.'];

if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

require_once '../../includes/db_connection.php';

if ($link === false) {
    $response['message'] = 'Database connection error.';
    echo json_encode($response);
    exit();
}

$nfc_id_input = trim(htmlspecialchars($_GET['nfc_id'] ?? ''));

if (empty($nfc_id_input)) {
    $response['message'] = 'NFC ID is required.';
    echo json_encode($response);
    exit();
}

try {
    $sql = "SELECT s.full_name, nc.current_balance
            FROM nfc_card nc
            JOIN student s ON nc.student_id = s.student_id
            WHERE nc.nfc_id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $nfc_id_input);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($data = mysqli_fetch_assoc($result)) {
            $response['success'] = true;
            $response['message'] = 'Card details fetched successfully.';
            $response['full_name'] = $data['full_name'];
            $response['current_balance'] = number_format((float)$data['current_balance'], 2);
        } else {
            $response['message'] = 'NFC Card ID not found or not linked to a student.';
        }
        mysqli_stmt_close($stmt);
    } else {
        throw new Exception("Database error preparing statement: " . mysqli_error($link));
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in fetch_nfc_card_info.php: " . $e->getMessage());
}

if (isset($link) && mysqli_ping($link)) {
    mysqli_close($link);
}

echo json_encode($response);
exit();
?>