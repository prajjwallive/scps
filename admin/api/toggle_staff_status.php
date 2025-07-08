<?php
// admin/api/toggle_staff_status.php - Backend API to toggle staff active status

date_default_timezone_set('Asia/Kathmandu'); // Set your default timezone
session_start();

header('Content-Type: application/json');

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

$current_admin_id = $_SESSION['admin_id'];
$current_admin_role = $_SESSION['admin_role'] ?? 'N/A'; // Get logged-in admin's role

// --- END REQUIRE ADMIN LOGIN ---

require_once '../../includes/db_connection.php';

if ($link === false) {
    error_log('DB Error (toggle_staff_status.php): Could not connect to database: ' . mysqli_connect_error());
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $staff_id_to_toggle = filter_var($input['staff_id'] ?? null, FILTER_VALIDATE_INT);
    $action = $input['action'] ?? ''; // 'activate' or 'deactivate'

    if (!$staff_id_to_toggle || !in_array($action, ['activate', 'deactivate'])) {
        $response['message'] = 'Invalid staff ID or action provided.';
        echo json_encode($response);
        exit();
    }

    // --- Fetch target staff's details to check permissions ---
    $sqlFetchTargetStaff = "SELECT staff_id, role FROM staff WHERE staff_id = ?";
    if ($stmtFetchTarget = mysqli_prepare($link, $sqlFetchTargetStaff)) {
        mysqli_stmt_bind_param($stmtFetchTarget, "i", $staff_id_to_toggle);
        mysqli_stmt_execute($stmtFetchTarget);
        $resultTarget = mysqli_stmt_get_result($stmtFetchTarget);
        $target_staff = mysqli_fetch_assoc($resultTarget);
        mysqli_stmt_close($stmtFetchTarget);

        if (!$target_staff) {
            $response['message'] = 'Target staff member not found.';
            echo json_encode($response);
            exit();
        }

        $target_staff_role = $target_staff['role'];
        $target_staff_id = $target_staff['staff_id'];

        // --- Permission Logic (Backend Enforcement) ---
        // 1. Prevent modification of self via this API
        if ($current_admin_id == $target_staff_id) {
            $response['message'] = 'You cannot change your own status via this interface. Please use your profile settings if available.';
            echo json_encode($response);
            exit();
        }

        // 2. Super Administrator can do anything
        if ($current_admin_role === 'super_administrator') {
            // Allowed to proceed
        }
        // 3. Regular Administrator cannot modify other Administrators or Super Administrators
        else if ($current_admin_role === 'administrator') {
            if ($target_staff_role === 'administrator' || $target_staff_role === 'super_administrator') {
                $response['message'] = 'Permission denied: You do not have sufficient privileges to modify a staff member with this role.';
                echo json_encode($response);
                exit();
            }
            // Add more conditions here if you have other roles like 'data_entry' that 'administrator' can manage
        }
        // 4. Any other unexpected role for logged-in admin (should ideally not happen)
        else {
            $response['message'] = 'Permission denied: Your role does not allow this action.';
            echo json_encode($response);
            exit();
        }

        // --- Proceed with status update if allowed ---
        $new_status = ($action === 'activate') ? 1 : 0;

        $sql = "UPDATE staff SET is_active = ? WHERE staff_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $new_status, $staff_id_to_toggle);
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $response['success'] = true;
                    $response['message'] = "Staff member status updated to " . ($new_status ? 'Active' : 'Inactive') . " successfully.";

                    // Log activity (optional but good practice)
                    $description = "Changed status of staff ID " . $staff_id_to_toggle . " (" . $target_staff['username'] . ") to " . ($new_status ? 'Active' : 'Inactive') . ".";
                    $activity_type = "STAFF_STATUS_CHANGE";
                    $admin_id_log = $current_admin_id; // The admin who performed the action
                    $related_id_log = $staff_id_to_toggle; // The staff member whose status was changed

                    $sql_log = "INSERT INTO activity_log (timestamp, activity_type, description, admin_id, related_id) VALUES (NOW(), ?, ?, ?, ?)";
                    if ($stmt_log = mysqli_prepare($link, $sql_log)) {
                        mysqli_stmt_bind_param($stmt_log, "ssii", $activity_type, $description, $admin_id_log, $related_id_log);
                        mysqli_stmt_execute($stmt_log);
                        mysqli_stmt_close($stmt_log);
                    } else {
                        error_log('DB Error (toggle_staff_status.php): Activity log prepare failed: ' . mysqli_error($link));
                    }

                } else {
                    $response['message'] = "Staff member status could not be changed (perhaps already in desired state or ID not found).";
                }
            } else {
                $response['message'] = 'Database error: Could not execute status update query. ' . mysqli_stmt_error($stmt);
                error_log('DB Error (toggle_staff_status.php): Status update execute failed: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database error: Could not prepare status update query. ' . mysqli_error($link);
            error_log('DB Error (toggle_staff_status.php): Status update prepare failed: ' . mysqli_error($link));
        }
    } else {
        $response['message'] = 'Database error: Could not prepare query to fetch target staff details. ' . mysqli_error($link);
        error_log('DB Error (toggle_staff_status.php): Fetch target staff prepare failed: ' . mysqli_error($link));
    }
}

mysqli_close($link);
echo json_encode($response);
?>
