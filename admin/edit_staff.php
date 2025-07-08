<?php
// admin/edit_staff.php - Page to edit an existing staff member

date_default_timezone_set('Asia/Kathmandu'); // Or your server's timezone
session_start();

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$current_admin_id = $_SESSION['admin_id'];
$admin_username_logger = $_SESSION['admin_username'] ?? 'Admin';
// --- END REQUIRE ADMIN LOGIN ---

require_once '../includes/db_connection.php';
include '../includes/packages.php'; // For Tailwind, Flowbite, etc.

// Initialize variables
$staff_id_to_edit = null;
$full_name = $username = $role = "";
$is_active = 1; // Default to active
$success_message = $error_message = "";

// Define available roles (same as in add_staff.php)
$available_roles = ['administrator', 'manager', 'staff_member', 'canteen_operator'];

// --- Check for Staff ID in URL ---
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $staff_id_to_edit = $_GET['id'];
} else {
    // If no ID or invalid ID, redirect or show error
    $_SESSION['error_message_staff_page'] = "Invalid or missing Staff ID for editing.";
    header('Location: staff.php');
    exit();
}

// --- FETCH STAFF DATA FOR EDITING ---
if ($staff_id_to_edit && $_SERVER["REQUEST_METHOD"] != "POST") { // Only fetch if not a POST request already or if ID is valid
    $sqlFetch = "SELECT full_name, username, role, is_active FROM staff WHERE staff_id = ?";
    if ($stmtFetch = mysqli_prepare($link, $sqlFetch)) {
        mysqli_stmt_bind_param($stmtFetch, "i", $staff_id_to_edit);
        if (mysqli_stmt_execute($stmtFetch)) {
            $result = mysqli_stmt_get_result($stmtFetch);
            if ($staff_data = mysqli_fetch_assoc($result)) {
                $full_name = $staff_data['full_name'];
                $username = $staff_data['username'];
                $role = $staff_data['role'];
                $is_active = $staff_data['is_active'];
            } else {
                $_SESSION['error_message_staff_page'] = "Staff member not found.";
                header('Location: staff.php');
                exit();
            }
        } else {
            $error_message = "Error fetching staff details: " . mysqli_stmt_error($stmtFetch);
            error_log("DB Execute Error (edit_staff.php - fetch): " . mysqli_stmt_error($stmtFetch));
        }
        mysqli_stmt_close($stmtFetch);
    } else {
        $error_message = "Database error: Could not prepare fetch statement. " . mysqli_error($link);
        error_log("DB Prepare Error (edit_staff.php - fetch): " . mysqli_error($link));
    }
}

// --- FORM PROCESSING FOR UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['staff_id'])) {
    $staff_id_to_edit_post = filter_var($_POST['staff_id'], FILTER_VALIDATE_INT); // Get staff_id from hidden field

    if ($staff_id_to_edit_post != $staff_id_to_edit) {
        // ID mismatch, should not happen if form is not tampered with
        $error_message = "Staff ID mismatch. Update failed.";
    } else {
        // Sanitize and retrieve form data
        $full_name = trim(htmlspecialchars($_POST['full_name']));
        $username_new = trim(htmlspecialchars($_POST['username'])); // new username from form
        $role = trim(htmlspecialchars($_POST['role']));
        $is_active_form = isset($_POST['is_active']) ? 1 : 0;
        $password = $_POST['password']; // Optional new password

        // --- Basic Validation ---
        if (empty($full_name) || empty($username_new) || empty($role)) {
            $error_message = "Full Name, Username, and Role are required.";
        } elseif (!in_array($role, $available_roles)) {
            $error_message = "Invalid role selected.";
        } elseif ($staff_id_to_edit == $current_admin_id && $is_active_form == 0) {
            $error_message = "You cannot deactivate your own account.";
            $is_active = 1; // Revert to active if self-deactivation attempt
        } else {
             $is_active = $is_active_form; // Update is_active if not self-deactivation
            // Check if username is being changed AND if the new username already exists for another user
            $sqlCheckUser = "SELECT staff_id FROM staff WHERE username = ? AND staff_id != ?";
            if ($stmtCheck = mysqli_prepare($link, $sqlCheckUser)) {
                mysqli_stmt_bind_param($stmtCheck, "si", $username_new, $staff_id_to_edit);
                mysqli_stmt_execute($stmtCheck);
                mysqli_stmt_store_result($stmtCheck);

                if (mysqli_stmt_num_rows($stmtCheck) > 0) {
                    $error_message = "The new username '{$username_new}' is already taken by another user. Please choose a different one.";
                    $username = $_POST['original_username']; // Keep original username in form if new one fails
                } else {
                    // Username is fine, proceed to update
                    $username = $username_new; // Update username to the new one
                    $update_fields = [];
                    $bind_types = "";
                    $bind_params = [];

                    $update_fields[] = "full_name = ?";
                    $bind_types .= "s";
                    $bind_params[] = $full_name;

                    $update_fields[] = "username = ?";
                    $bind_types .= "s";
                    $bind_params[] = $username;

                    $update_fields[] = "role = ?";
                    $bind_types .= "s";
                    $bind_params[] = $role;

                    $update_fields[] = "is_active = ?";
                    $bind_types .= "i";
                    $bind_params[] = $is_active;

                    if (!empty($password)) {
                        if (strlen($password) < 8) {
                            $error_message = "New password must be at least 8 characters long.";
                        } else {
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $update_fields[] = "password_hash = ?";
                            $bind_types .= "s";
                            $bind_params[] = $password_hash;
                        }
                    }

                    if (empty($error_message)) { // Proceed only if no password length error
                        $sqlDoUpdate = "UPDATE staff SET " . implode(", ", $update_fields) . " WHERE staff_id = ?";
                        $bind_types .= "i";
                        $bind_params[] = $staff_id_to_edit;

                        if ($stmtUpdate = mysqli_prepare($link, $sqlDoUpdate)) {
                            mysqli_stmt_bind_param($stmtUpdate, $bind_types, ...$bind_params);

                            if (mysqli_stmt_execute($stmtUpdate)) {
                                $success_message = "Staff member details updated successfully!";

                                // --- Log activity ---
                                $log_description = "Admin '{$admin_username_logger}' updated details for staff '{$username}' (ID: {$staff_id_to_edit}).";
                                if (!empty($password)) $log_description .= " Password updated.";
                                $sqlLog = "INSERT INTO activity_log (timestamp, activity_type, description, admin_id, related_id) VALUES (NOW(), 'staff_edit', ?, ?, ?)";
                                if ($stmtLog = mysqli_prepare($link, $sqlLog)) {
                                    mysqli_stmt_bind_param($stmtLog, "sii", $log_description, $current_admin_id, $staff_id_to_edit);
                                    mysqli_stmt_execute($stmtLog);
                                    mysqli_stmt_close($stmtLog);
                                }
                                // --- End log activity ---
                            } else {
                                $error_message = "Error updating staff member: " . mysqli_stmt_error($stmtUpdate);
                                error_log("DB Execute Error (edit_staff.php - update): " . mysqli_stmt_error($stmtUpdate));
                            }
                            mysqli_stmt_close($stmtUpdate);
                        } else {
                            $error_message = "Database error: Could not prepare update statement. " . mysqli_error($link);
                            error_log("DB Prepare Error (edit_staff.php - update): " . mysqli_error($link));
                        }
                    }
                }
                mysqli_stmt_close($stmtCheck);
            } else {
                $error_message = "Database error: Could not prepare username check. " . mysqli_error($link);
                error_log("DB Prepare Error (edit_staff.php - check user): " . mysqli_error($link));
            }
        }
    }
}
// --- END FORM PROCESSING ---

include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff Member</title>
    <style>
        .form-container { max-width: 600px; margin: 2rem auto; padding: 2rem; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151; }
        .form-group input[type="text"], .form-group input[type="password"], .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; }
        .form-group input[type="checkbox"] { width: auto; margin-right: 0.5rem; }
        .form-group input:focus, .form-group select:focus { border-color: #4f46e5; outline: none; box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2); }
        .btn-submit { background-color: #4f46e5; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .btn-submit:hover { background-color: #4338ca; }
        .message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }
        .success { background-color: #d1fae5; color: #065f46; }
        .error { background-color: #fee2e2; color: #991b1b; }
        .dark .form-container { background-color: #1f2937; }
        .dark .form-group label { color: #d1d5db; }
        .dark .form-group input, .dark .form-group select { background-color: #374150; border-color: #4b5563; color: #e5e7eb; }
        .dark .btn-submit { background-color: #6366f1; }
        .dark .btn-submit:hover { background-color: #4f46e5; }
        .dark .success { background-color: #064e3b; color: #a7f3d0; }
        .dark .error { background-color: #7f1d1d; color: #fecaca; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 font-sans">

    <main class="form-container">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">Edit Staff Member</h1>

        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($staff_id_to_edit && empty($error_message) && !($error_message == "Staff member not found.")): // Show form only if staff exists and no major initial error ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $staff_id_to_edit; ?>" method="post">
            <input type="hidden" name="staff_id" value="<?php echo $staff_id_to_edit; ?>">
            <input type="hidden" name="original_username" value="<?php echo htmlspecialchars($username); ?>"> <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
            </div>

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required pattern="[a-zA-Z0-9_]+" title="Username can only contain letters, numbers, and underscores.">
            </div>

            <div class="form-group">
                <label for="password">New Password (optional):</label>
                <input type="password" id="password" name="password" minlength="8">
                <small class="text-gray-500 dark:text-gray-400">Leave blank to keep current password. Minimum 8 characters if changing.</small>
            </div>

            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="">-- Select Role --</option>
                    <?php foreach ($available_roles as $role_option): ?>
                        <option value="<?php echo htmlspecialchars($role_option); ?>" <?php echo ($role == $role_option) ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($role_option))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="is_active">
                    <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($is_active == 1) ? 'checked' : ''; ?>
                           <?php if ($staff_id_to_edit == $current_admin_id) echo 'onclick="return false;"'; // Prevent unchecking if it's the current admin's own account ?>
                    >
                    Active
                </label>
                <?php if ($staff_id_to_edit == $current_admin_id): ?>
                    <small class="block text-gray-500 dark:text-gray-400">Your own account cannot be deactivated from here.</small>
                <?php endif; ?>
            </div>

            <div class="form-group text-center">
                <button type="submit" class="btn-submit">Update Staff</button>
                <a href="staff.php" class="inline-block ml-4 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Cancel</a>
            </div>
        </form>
        <?php elseif(!empty($error_message) && $error_message == "Staff member not found."): ?>
             <p class="text-center text-red-600 dark:text-red-400">Staff member could not be found.</p>
             <p class="text-center mt-4"><a href="staff.php" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Return to Staff List</a></p>
        <?php endif; ?>
    </main>

    <?php
    if (isset($link) && $link) {
        mysqli_close($link);
    }
    ?>
</body>
</html>