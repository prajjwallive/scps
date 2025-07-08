<?php
// admin/settings.php - Admin Profile and Settings Page

date_default_timezone_set('Asia/Kathmandu'); // Or your server's timezone
session_start();

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$current_admin_id = $_SESSION['admin_id'];
$admin_username_session = $_SESSION['admin_username'] ?? 'Admin'; // Username from session

// Include database connection
require_once '../includes/db_connection.php';
include '../includes/packages.php'; // For Tailwind, Flowbite, etc.

// Initialize variables for form fields and messages
$full_name_db = "";
$username_db = "";
$role_db = "";
// Removed theme_preference_db and items_per_page_db as the interface section is being removed
$update_profile_success_message = "";
$update_profile_error_message = "";
$change_password_success_message = "";
$change_password_error_message = "";
// Removed interface update messages

// --- FETCH CURRENT ADMIN'S DATA ---
// Only fetching profile data now
$sqlFetchAdmin = "SELECT full_name, username, role FROM staff WHERE staff_id = ?";
if ($stmtFetch = mysqli_prepare($link, $sqlFetchAdmin)) {
    mysqli_stmt_bind_param($stmtFetch, "i", $current_admin_id);
    if (mysqli_stmt_execute($stmtFetch)) {
        $result = mysqli_stmt_get_result($stmtFetch);
        if ($admin_data = mysqli_fetch_assoc($result)) {
            $full_name_db = $admin_data['full_name'];
            $username_db = $admin_data['username'];
            $role_db = $admin_data['role'];
        } else {
            $update_profile_error_message = "Could not retrieve your profile information."; // Use generic error location
            error_log("Admin Settings: Failed to fetch admin data for ID: " . $current_admin_id);
        }
    } else {
        $update_profile_error_message = "Error fetching profile: " . mysqli_stmt_error($stmtFetch);
        error_log("DB Execute Error (settings.php - fetch admin): " . mysqli_stmt_error($stmtFetch));
    }
    mysqli_stmt_close($stmtFetch);
} else {
    $update_profile_error_message = "Database error preparing to fetch profile: " . mysqli_error($link);
    error_log("DB Prepare Error (settings.php - fetch admin): " . mysqli_error($link));
}

// --- FORM PROCESSING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- HANDLE PROFILE DETAILS UPDATE ---
    if (isset($_POST['update_profile_details'])) {
        $new_full_name = trim(htmlspecialchars($_POST['full_name']));
        $new_username = trim(htmlspecialchars($_POST['username']));

        if (empty($new_full_name) || empty($new_username)) {
            $update_profile_error_message = "Full name and username cannot be empty.";
        } else {
            // Check if new username is taken by ANOTHER admin (same logic as before)
            $sqlCheckUsername = "SELECT staff_id FROM staff WHERE username = ? AND staff_id != ?";
            if ($stmtCheckUser = mysqli_prepare($link, $sqlCheckUsername)) {
                 mysqli_stmt_bind_param($stmtCheckUser, "si", $new_username, $current_admin_id);
                 // ... (rest of username check and profile update logic is identical to profile.php)
                 mysqli_stmt_execute($stmtCheckUser);
                 mysqli_stmt_store_result($stmtCheckUser);

                 if (mysqli_stmt_num_rows($stmtCheckUser) > 0) {
                     $update_profile_error_message = "Username '{$new_username}' is already taken. Please choose another.";
                 } else {
                     // Proceed with update
                     $sqlUpdateDetails = "UPDATE staff SET full_name = ?, username = ? WHERE staff_id = ?";
                     if ($stmtUpdate = mysqli_prepare($link, $sqlUpdateDetails)) {
                         mysqli_stmt_bind_param($stmtUpdate, "ssi", $new_full_name, $new_username, $current_admin_id);
                         if (mysqli_stmt_execute($stmtUpdate)) {
                             $update_profile_success_message = "Profile details updated successfully!";
                             // Update session username if it was changed
                             if ($username_db !== $new_username) {
                                 $_SESSION['admin_username'] = $new_username;
                                 $admin_username_session = $new_username; // Update local var too
                             }
                             // Update variables for form display
                             $full_name_db = $new_full_name;
                             $username_db = $new_username;

                             // Logging (same as before)
                             $log_desc = "Admin '{$admin_username_session}' (ID: {$current_admin_id}) updated their profile details.";
                             if ($username_db !== $_SESSION['admin_username']) $log_desc .= " Username changed to '{$new_username}'."; // Correct comparison? Maybe compare $new_username with $admin_username_session before update
                             $sqlLog = "INSERT INTO activity_log (timestamp, activity_type, description, admin_id, related_id) VALUES (NOW(), 'profile_update', ?, ?, ?)";
                             if($stmtLog = mysqli_prepare($link, $sqlLog)){ mysqli_stmt_bind_param($stmtLog, "sii", $log_desc, $current_admin_id, $current_admin_id); mysqli_stmt_execute($stmtLog); mysqli_stmt_close($stmtLog); }

                         } else { $update_profile_error_message = "Error updating profile details: " . mysqli_stmt_error($stmtUpdate); }
                         mysqli_stmt_close($stmtUpdate);
                     } else { $update_profile_error_message = "Database error preparing update: " . mysqli_error($link); }
                 }
                 mysqli_stmt_close($stmtCheckUser);
            } else { $update_profile_error_message = "Database error checking username: " . mysqli_error($link); }
        }
    }

    // --- HANDLE PASSWORD CHANGE ---
    elseif (isset($_POST['change_password'])) {
        // ... (Password change logic is identical to profile.php)
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) { $change_password_error_message = "All password fields are required."; }
        elseif ($new_password !== $confirm_new_password) { $change_password_error_message = "New password and confirm password do not match."; }
        elseif (strlen($new_password) < 8) { $change_password_error_message = "New password must be at least 8 characters long."; }
        else {
            // Fetch current password hash to verify
            $sqlFetchPass = "SELECT password_hash FROM staff WHERE staff_id = ?";
            if ($stmtFetchPass = mysqli_prepare($link, $sqlFetchPass)) {
                mysqli_stmt_bind_param($stmtFetchPass, "i", $current_admin_id); mysqli_stmt_execute($stmtFetchPass); $resultPass = mysqli_stmt_get_result($stmtFetchPass); $adminPassData = mysqli_fetch_assoc($resultPass); mysqli_stmt_close($stmtFetchPass);
                if ($adminPassData && password_verify($current_password, $adminPassData['password_hash'])) {
                    // Current password is correct, proceed to update
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $sqlUpdatePass = "UPDATE staff SET password_hash = ? WHERE staff_id = ?";
                    if ($stmtUpdatePass = mysqli_prepare($link, $sqlUpdatePass)) {
                        mysqli_stmt_bind_param($stmtUpdatePass, "si", $new_password_hash, $current_admin_id);
                        if (mysqli_stmt_execute($stmtUpdatePass)) {
                            $change_password_success_message = "Password changed successfully!";
                            // Logging (same as before)
                             $log_desc_pass = "Admin '{$username_db}' (ID: {$current_admin_id}) changed their password.";
                             $sqlLogPass = "INSERT INTO activity_log (timestamp, activity_type, description, admin_id, related_id) VALUES (NOW(), 'password_change_self', ?, ?, ?)";
                             if($stmtLogPass = mysqli_prepare($link, $sqlLogPass)){ mysqli_stmt_bind_param($stmtLogPass, "sii", $log_desc_pass, $current_admin_id, $current_admin_id); mysqli_stmt_execute($stmtLogPass); mysqli_stmt_close($stmtLogPass); }
                        } else { $change_password_error_message = "Error changing password: " . mysqli_stmt_error($stmtUpdatePass); }
                        mysqli_stmt_close($stmtUpdatePass);
                    } else { $change_password_error_message = "Database error preparing password update: " . mysqli_error($link); }
                } else { $change_password_error_message = "Incorrect current password."; }
            } else { $change_password_error_message = "Database error verifying current password: " . mysqli_error($link); }
        }
    }

    // Removed the 'update_interface_settings' handling block
}
// --- END FORM PROCESSING ---

// Include admin header - This should ideally apply the theme preference (requires modification in header/packages)
include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Settings</title>
    <style> /* Keep basic layout styles or replace with Tailwind classes */
        .settings-container { max-width: 800px; margin: 2rem auto; }
        .settings-tabs { border-bottom: 1px solid #d1d5db; margin-bottom: 1.5rem; display: flex; }
        .settings-tabs button { padding: 0.75rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 500; color: #6b7280; border-bottom: 2px solid transparent; }
        .settings-tabs button.active { color: #4f46e5; border-bottom-color: #4f46e5; }
        .settings-content { display: none; }
        .settings-content.active { display: block; }
        .settings-section { background-color: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151; }
        .form-group input[type="text"], .form-group input[type="password"], .form-group input[type="number"], .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus { border-color: #4f46e5; outline: none; box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2); }
        .form-group .readonly-field { background-color: #f3f4f6; cursor: not-allowed; }
        .btn-submit { background-color: #4f46e5; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .btn-submit:hover { background-color: #4338ca; }
        .message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }
        .success { background-color: #d1fae5; color: #065f46; }
        .error { background-color: #fee2e2; color: #991b1b; }
        /* Dark mode styles - These are no longer needed as dark mode is removed */
        /* .dark .settings-tabs { border-bottom-color: #4b5563; }
        .dark .settings-tabs button { color: #9ca3af; }
        .dark .settings-tabs button.active { color: #818cf8; border-bottom-color: #818cf8; }
        .dark .settings-section { background-color: #1f2937; }
        .dark .form-group label { color: #d1d5db; }
        .dark .form-group input, .dark .form-group select { background-color: #374150; border-color: #4b5563; color: #e5e7eb; }
        .dark .form-group .readonly-field { background-color: #4b5563; }
        .dark .btn-submit { background-color: #6366f1; }
        .dark .btn-submit:hover { background-color: #4f46e5; }
        .dark .success { background-color: #064e3b; color: #a7f3d0; }
        .dark .error { background-color: #7f1d1d; color: #fecaca; } */
        /* Switch styles - No longer needed */
        /* .switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #4f46e5; }
        input:checked + .slider:before { transform: translateX(26px); }
        .dark input:checked + .slider { background-color: #6366f1; } */
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="settings-container px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">Settings</h1>

        <div class="settings-tabs">
            <button class="tab-button active" onclick="openTab(event, 'profile')">Profile</button>
            <button class="tab-button" onclick="openTab(event, 'password')">Password</button>
        </div>

        <div id="profile" class="settings-content active">
            <section class="settings-section">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">My Profile Details</h2>
                <?php if (!empty($update_profile_success_message)): ?>
                    <div class="message success"><?php echo $update_profile_success_message; ?></div>
                <?php endif; ?>
                <?php if (!empty($update_profile_error_message)): ?>
                    <div class="message error"><?php echo $update_profile_error_message; ?></div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name_db); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username_db); ?>" required pattern="[a-zA-Z0-9_]+" title="Username can only contain letters, numbers, and underscores.">
                    </div>
                    <div class="form-group">
                        <label for="role_display">Role:</label>
                        <input type="text" id="role_display" name="role_display" value="<?php echo ucfirst(htmlspecialchars($role_db)); ?>" readonly class="readonly-field">
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" name="update_profile_details" class="btn-submit">Update Details</button>
                    </div>
                </form>
            </section>
        </div>

        <!-- <div id="interface" class="settings-content">
            <section class="settings-section">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-6">Interface Settings</h2>
                 <?php if (!empty($update_interface_success_message)): ?>
                    <div class="message success"><?php echo $update_interface_success_message; ?></div>
                <?php endif; ?>
                <?php if (!empty($update_interface_error_message)): ?>
                    <div class="message error"><?php echo $update_interface_error_message; ?></div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group flex items-center justify-between">
                        <label for="theme_preference" class="mb-0">Dark Mode:</label>
                        <label class="switch">
                            <input type="checkbox" id="theme_preference" name="theme_preference" value="dark" <?php echo ($theme_preference_db === 'dark') ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                     <div class="form-group">
                        <label for="items_per_page">Items Per Page (for tables):</label>
                        <input type="number" id="items_per_page" name="items_per_page" value="<?php echo htmlspecialchars($items_per_page_db); ?>" min="5" max="100" required>
                         <small class="text-gray-500 dark:text-gray-400">Number of rows shown per page in lists like Staff, Transactions (5-100).</small>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" name="update_interface_settings" class="btn-submit">Save Interface Settings</button>
                    </div>
                </form>
            </section>
        </div> -->

        <div id="password" class="settings-content">
            <section class="settings-section">
                 <h2 class="text-xl font-semibold text-gray-800 mb-6">Change Password</h2>
                 <?php if (!empty($change_password_success_message)): ?>
                    <div class="message success"><?php echo $change_password_success_message; ?></div>
                 <?php endif; ?>
                 <?php if (!empty($change_password_error_message)): ?>
                    <div class="message error"><?php echo $change_password_error_message; ?></div>
                 <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                     <div class="form-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8">
                        <small class="text-gray-500">Minimum 8 characters.</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_new_password">Confirm New Password:</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" required minlength="8">
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" name="change_password" class="btn-submit">Change Password</button>
                    </div>
                </form>
            </section>
        </div>

    </div>

    <?php // Removed include '../includes/footer.php'; // Standard footer ?>
    <script>
        // Basic Tab functionality
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("settings-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");

            // Optional: Store active tab in localStorage or session storage
             localStorage.setItem('activeSettingsTab', tabName);
        }

        // Removed theme toggle synchronization as the interface section is removed
        // const themeToggle = document.getElementById('theme_preference');
        // if (themeToggle) {
        //     themeToggle.addEventListener('change', function() {
        //         if (this.checked) {
        //             document.documentElement.classList.add('dark');
        //             localStorage.setItem('theme', 'dark');
        //         } else {
        //             document.documentElement.classList.remove('dark');
        //              localStorage.setItem('theme', 'light');
        //         }
        //     });
        // }

        // Restore active tab on page load
         document.addEventListener('DOMContentLoaded', function() {
             const activeTab = localStorage.getItem('activeSettingsTab');
             if (activeTab) {
                 const targetButton = document.querySelector(`.tab-button[onclick*="'${activeTab}'"]`);
                 if(targetButton){
                    targetButton.click();
                 } else {
                    // Default to first tab if nothing stored
                    document.querySelector('.tab-button').click();
                 }
             } else {
                 // Default to first tab if nothing stored
                 document.querySelector('.tab-button').click();
             }
         });

    </script>
    <?php
    if (isset($link) && $link) {
        mysqli_close($link);
    }
    ?>
</body>
</html>
