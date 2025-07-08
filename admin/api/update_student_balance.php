<?php
// admin/api/update_student_balance.php (Simplified)

date_default_timezone_set('Asia/Kathmandu'); // Your timezone
session_start();

// Set content type to JSON immediately
// IMPORTANT: No output (echo, print, whitespace, PHP errors/warnings) before this line if possible.
// For debugging the JSON error, you might temporarily comment this out and check raw output.
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An error occurred.'];

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    $response['message'] = 'Unauthorized access. Please log in.';
    echo json_encode($response);
    exit();
}
$admin_id_logger = $_SESSION['admin_id'];
$admin_username_logger = $_SESSION['admin_username'] ?? 'Admin';

require_once '../../includes/db_connection.php'; // Correct path from admin/api/

if ($link === false) {
    error_log("DB Error (update_student_balance.php): Connection failed.");
    // Do not echo here directly if header('Content-Type: application/json') is already sent.
    // The $response array will be encoded at the end.
    $response['message'] = 'Database connection error.';
    echo json_encode($response); // Ensure this is the only echo for errors like this
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

$nfc_id = trim(htmlspecialchars($_POST['nfc_id'] ?? ''));
$amount_str = trim(htmlspecialchars($_POST['amount'] ?? ''));

if (empty($nfc_id) || !is_numeric($amount_str)) { // Amount can be positive or negative
    $response['message'] = 'NFC ID and a numeric amount are required.';
    echo json_encode($response);
    exit();
}

$amount_change = floatval($amount_str); // This is the amount to add or subtract

mysqli_begin_transaction($link);

try {
    // Check if NFC card exists and get current balance and student_id
    $sql_get_card = "SELECT student_id, current_balance FROM nfc_card WHERE nfc_id = ?";
    $current_balance = 0;
    $student_id_for_log = null;

    if ($stmt_get = mysqli_prepare($link, $sql_get_card)) {
        mysqli_stmt_bind_param($stmt_get, "s", $nfc_id);
        mysqli_stmt_execute($stmt_get);
        $result_get = mysqli_stmt_get_result($stmt_get);
        if ($card_data = mysqli_fetch_assoc($result_get)) {
            $current_balance = (float)$card_data['current_balance'];
            $student_id_for_log = (int)$card_data['student_id'];
        } else {
            mysqli_stmt_close($stmt_get); // Close statement before throwing
            throw new Exception("NFC Card ID '{$nfc_id}' not found.");
        }
        mysqli_stmt_close($stmt_get);
    } else {
        throw new Exception("DB error preparing to fetch card details: " . mysqli_error($link));
    }

    $new_balance = $current_balance + $amount_change;

    if ($new_balance < 0) {
        // Optional: You might allow it to go negative or have specific rules.
        // For now, let's prevent it if the operation leads to < 0.
        // If $amount_change itself is negative, this check is important.
        throw new Exception("Operation results in a negative balance (NPR " . number_format($new_balance, 2) . "). Not allowed.");
    }

    // Update balance
    $sql_update = "UPDATE nfc_card SET current_balance = ? WHERE nfc_id = ?";
    if ($stmt_update = mysqli_prepare($link, $sql_update)) {
        mysqli_stmt_bind_param($stmt_update, "ds", $new_balance, $nfc_id);
        mysqli_stmt_execute($stmt_update);

        if (mysqli_stmt_affected_rows($stmt_update) >= 0) { // >= 0 because balance might not change if amount_change is 0
            mysqli_commit($link); // Commit successful update

            // Log activity AFTER commit
            $action_taken = $amount_change >= 0 ? "added" : "deducted";
            $abs_amount = abs($amount_change);
            $log_description = "Admin '{$admin_username_logger}' {$action_taken} NPR {$abs_amount} for NFC ID '{$nfc_id}' (Student ID: {$student_id_for_log}). New balance: NPR " . number_format($new_balance, 2) . ".";
            $activity_type = "balance_update";
            
            $sql_log = "INSERT INTO activity_log (timestamp, activity_type, description, admin_id, related_id) VALUES (NOW(), ?, ?, ?, ?)";
            if ($stmt_log = mysqli_prepare($link, $sql_log)) {
                mysqli_stmt_bind_param($stmt_log, "ssii", $activity_type, $log_description, $admin_id_logger, $student_id_for_log);
                if (!mysqli_stmt_execute($stmt_log)) {
                     error_log("Failed to log balance update activity (update_student_balance.php): " . mysqli_stmt_error($stmt_log));
                }
                mysqli_stmt_close($stmt_log);
            } else {
                error_log("Failed to prepare activity log statement (update_student_balance.php): " . mysqli_error($link));
            }

            $response['success'] = true;
            $response['message'] = "Balance updated successfully. New balance: NPR " . number_format($new_balance, 2);
            $response['new_balance'] = number_format($new_balance, 2);
        } else {
            // This condition (mysqli_stmt_affected_rows < 0) usually means an error in execution.
            // If 0 rows affected but no error, it means the value was already the same, or nfc_id didn't match.
            // The fetch before update should catch non-existent nfc_id.
            throw new Exception("Failed to update balance. No rows affected or error during update.");
        }
        mysqli_stmt_close($stmt_update);
    } else {
        throw new Exception("DB error preparing balance update: " . mysqli_error($link));
    }

} catch (Exception $e) {
    mysqli_rollback($link);
    $response['message'] = "Operation failed: " . $e->getMessage();
    // Log the detailed error for admin/developer view
    error_log("Balance Update Error in API (update_student_balance.php): " . $e->getMessage() . " - Input NFC: {$nfc_id}, Amount: {$amount_str}");
}

if (isset($link) && $link && mysqli_ping($link)) { // Check if connection is still valid before closing
    mysqli_close($link);
}

// This should be the ONLY echo statement in the entire script's successful execution path,
// or for controlled error responses.
echo json_encode($response);
exit(); // Ensure no further output
?>