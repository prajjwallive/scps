<?php
// api/manage_menu_time_settings.php - Handles saving time ranges for menus

header('Content-Type: application/json');
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

// Include database connection
require_once '../../includes/db_connection.php'; // From admin/api/ to scps1/includes/

$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("manage_menu_time_settings.php: JSON decode error - " . json_last_error_msg());
    echo json_encode(['success' => false, 'message' => 'Invalid data format.']);
    exit();
}

$response = ['success' => false, 'message' => 'Failed to save settings.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $settings_to_save = [];

    // Validate and collect data for each menu type
    foreach (['breakfast', 'lunch', 'dinner'] as $type_lower) {
        $menu_type_uc = ucfirst($type_lower);
        
        // Expected time format from frontend is "HH:MM"
        $start_time_str = $data[$type_lower . '_start'] ?? null;
        $end_time_str = $data[$type_lower . '_end'] ?? null;

        // Parse start time
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $start_time_str, $matches_start)) {
            $start_hour = (int)$matches_start[1];
            $start_minute = (int)$matches_start[2];
        } else {
            $errors[] = "Invalid start time format for {$menu_type_uc} menu. Use HH:MM.";
            continue;
        }

        // Parse end time
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $end_time_str, $matches_end)) {
            $end_hour = (int)$matches_end[1];
            $end_minute = (int)$matches_end[2];
        } else {
            $errors[] = "Invalid end time format for {$menu_type_uc} menu. Use HH:MM.";
            continue;
        }

        // Validate logic: Start time must be strictly before end time
        // E.g., 8:00 to 8:00 is not allowed, must be 8:00 to 8:01 or more
        if ($start_hour > $end_hour || ($start_hour === $end_hour && $start_minute >= $end_minute)) {
            $errors[] = "Start time must be before end time for {$menu_type_uc} menu.";
        } else {
            $settings_to_save[$menu_type_uc] = [
                'start_hour' => $start_hour, 
                'start_minute' => $start_minute,
                'end_hour' => $end_hour,
                'end_minute' => $end_minute
            ];
        }
    }

    if (!empty($errors)) {
        $response['message'] = implode(' ', $errors);
        echo json_encode($response);
        exit();
    }

    $link->begin_transaction();
    try {
        // Use INSERT ... ON DUPLICATE KEY UPDATE for atomic UPSERT
        // Added start_minute and end_minute columns
        $sql_upsert = "INSERT INTO menu_time_settings (menu_type, start_hour, start_minute, end_hour, end_minute) 
                       VALUES (?, ?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE 
                       start_hour = VALUES(start_hour), 
                       start_minute = VALUES(start_minute),
                       end_hour = VALUES(end_hour), 
                       end_minute = VALUES(end_minute)";
        
        $stmt_upsert = $link->prepare($sql_upsert);
        if (!$stmt_upsert) {
            throw new Exception("Prepare UPSERT failed: " . $link->error);
        }

        foreach ($settings_to_save as $menu_type => $hours) {
            $start_h = $hours['start_hour'];
            $start_m = $hours['start_minute'];
            $end_h = $hours['end_hour'];
            $end_m = $hours['end_minute'];
            // Updated bind_param types to include two more integers
            $stmt_upsert->bind_param("siiii", $menu_type, $start_h, $start_m, $end_h, $end_m);
            if (!$stmt_upsert->execute()) {
                throw new Exception("Execute UPSERT failed for {$menu_type}: " . $stmt_upsert->error);
            }
        }
        $stmt_upsert->close();

        $link->commit();
        $response['success'] = true;
        $response['message'] = 'Time settings saved successfully.';
    } catch (Exception $e) {
        $link->rollback();
        $response['message'] = 'Database transaction failed: ' . $e->getMessage();
        error_log("manage_menu_time_settings.php: Transaction failed: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$link->close();
echo json_encode($response);
?>
