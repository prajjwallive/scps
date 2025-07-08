<?php
// admin/add_staff.php - Page to add a new staff member

date_default_timezone_set('Asia/Kathmandu'); // Or your server's timezone
session_start();

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php'); // Redirect to login if not authenticated
    exit();
}
$admin_id_logger = $_SESSION['admin_id'];
$admin_username_logger = $_SESSION['admin_username'] ?? 'Admin'; // Ensure admin_username is set at login
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection (path is from /admin/ to /includes/)
require_once '../includes/db_connection.php';
include '../includes/packages.php'; // For Tailwind, Flowbite, etc. - if needed for styling this page

// Initialize variables for form fields and messages
$full_name = $username = $password = $role = "";
$success_message = $error_message = "";

// Define available roles (you can also fetch these from a dedicated roles table if you have one)
$available_roles = ['administrator', 'manager', 'staff_member', 'canteen_operator']; // Example roles

// --- FORM PROCESSING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $full_name = trim(htmlspecialchars($_POST['full_name']));
    $username = trim(htmlspecialchars($_POST['username']));
    $password = $_POST['password']; // Will be hashed, not directly displayed or stored as plain text
    $role = trim(htmlspecialchars($_POST['role']));

    // --- Basic Validation ---
    if (empty($full_name) || empty($username) || empty($password) || empty($role)) {
        $error_message = "All fields are required.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!in_array($role, $available_roles)) {
        $error_message = "Invalid role selected.";
    } else {
        // Check if username already exists
        $sqlCheckUser = "SELECT staff_id FROM staff WHERE username = ?";
        if ($stmtCheck = mysqli_prepare($link, $sqlCheckUser)) {
            mysqli_stmt_bind_param($stmtCheck, "s", $username);
            mysqli_stmt_execute($stmtCheck);
            mysqli_stmt_store_result($stmtCheck);

            if (mysqli_stmt_num_rows($stmtCheck) > 0) {
                $error_message = "Username already exists. Please choose a different one.";
            } else {
                // Username is unique, proceed to insert
                // Hash the password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Prepare an insert statement
                $sqlInsert = "INSERT INTO staff (full_name, username, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())";

                if ($stmtInsert = mysqli_prepare($link, $sqlInsert)) {
                    mysqli_stmt_bind_param($stmtInsert, "ssss", $full_name, $username, $password_hash, $role);

                    if (mysqli_stmt_execute($stmtInsert)) {
                        $new_staff_id = mysqli_insert_id($link); // Get the ID of the newly inserted staff
                        $success_message = "New staff member added successfully! Staff ID: " . $new_staff_id;

                        // --- Log activity ---
                        $log_description = "Admin '{$admin_username_logger}' added new staff member '{$username}' (ID: {$new_staff_id}) with role '{$role}'.";
                        $sqlLog = "INSERT INTO activity_log (timestamp, activity_type, description, admin_id, related_id) VALUES (NOW(), 'staff_add', ?, ?, ?)";
                        if ($stmtLog = mysqli_prepare($link, $sqlLog)) {
                            mysqli_stmt_bind_param($stmtLog, "sii", $log_description, $admin_id_logger, $new_staff_id);
                            if (!mysqli_stmt_execute($stmtLog)) {
                                error_log("Failed to log activity (add_staff.php): " . mysqli_stmt_error($stmtLog));
                            }
                            mysqli_stmt_close($stmtLog);
                        } else {
                            error_log("Failed to prepare activity log statement (add_staff.php): " . mysqli_error($link));
                        }
                        // --- End log activity ---

                        // Clear form fields after successful submission
                        $full_name = $username = $password = $role = "";
                    } else {
                        $error_message = "Error adding staff member: " . mysqli_stmt_error($stmtInsert);
                        error_log("DB Execute Error (add_staff.php - insert): " . mysqli_stmt_error($stmtInsert));
                    }
                    mysqli_stmt_close($stmtInsert);
                } else {
                    $error_message = "Database error: Could not prepare insert statement. Details: " . mysqli_error($link);
                    error_log("DB Prepare Error (add_staff.php - insert): " . mysqli_error($link));
                }
            }
            mysqli_stmt_close($stmtCheck);
        } else {
            $error_message = "Database error: Could not prepare username check. Details: " . mysqli_error($link);
            error_log("DB Prepare Error (add_staff.php - check user): " . mysqli_error($link));
        }
    }
}
// --- END FORM PROCESSING ---

// Include admin header (path is from /admin/ to /includes/)
include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Staff</title>
    <style>
        /* Basic styling for form elements - adjust with Tailwind or your CSS framework */
        .form-container { max-width: 600px; margin: 2rem auto; padding: 2rem; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; }
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
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">Add New Staff Member</h1>

        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
            </div>

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required pattern="[a-zA-Z0-9_]+" title="Username can only contain letters, numbers, and underscores.">
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required minlength="8">
                <small class="text-gray-500 dark:text-gray-400">Minimum 8 characters.</small>
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

            <div class="form-group text-center">
                <button type="submit" class="btn-submit">Add Staff</button>
                 <a href="staff.php" class="inline-block ml-4 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Cancel</a>
            </div>
        </form>
    </main>
    <?php
    if (isset($link) && $link) {
        mysqli_close($link);
    }
    ?>
</body>
</html>