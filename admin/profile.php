<?php
// admin/profile.php - Admin Profile Display Page (View Only)

date_default_timezone_set('Asia/Kathmandu');
session_start();

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$current_admin_id = $_SESSION['admin_id'];

// Include database connection & packages
require_once '../includes/db_connection.php';
include '../includes/packages.php';

// Initialize variables
$full_name_db = "N/A";
$username_db = "N/A";
$role_db = "N/A";
$last_login_db = "N/A";
$created_at_db = "N/A";
$error_message = "";

// --- FETCH CURRENT ADMIN'S DATA ---
$sqlFetchAdmin = "SELECT full_name, username, role, last_login, created_at FROM staff WHERE staff_id = ?";
if ($stmtFetch = mysqli_prepare($link, $sqlFetchAdmin)) {
    mysqli_stmt_bind_param($stmtFetch, "i", $current_admin_id);
    if (mysqli_stmt_execute($stmtFetch)) {
        $result = mysqli_stmt_get_result($stmtFetch);
        if ($admin_data = mysqli_fetch_assoc($result)) {
            $full_name_db = htmlspecialchars($admin_data['full_name']);
            $username_db = htmlspecialchars($admin_data['username']);
            $role_db = htmlspecialchars(ucfirst(str_replace('_', ' ', $admin_data['role']))); // Format role nicely
            // Format dates (optional, adjust format as needed)
            $last_login_db = $admin_data['last_login'] ? date("d M Y, h:i A", strtotime($admin_data['last_login'])) : 'Never';
            $created_at_db = $admin_data['created_at'] ? date("d M Y", strtotime($admin_data['created_at'])) : 'N/A';
        } else {
            $error_message = "Could not retrieve your profile information.";
        }
    } else {
        $error_message = "Error fetching profile: " . mysqli_stmt_error($stmtFetch);
        error_log("DB Execute Error (profile.php - view): " . mysqli_stmt_error($stmtFetch));
    }
    mysqli_stmt_close($stmtFetch);
} else {
    $error_message = "Database error preparing to fetch profile: " . mysqli_error($link);
    error_log("DB Prepare Error (profile.php - view): " . mysqli_error($link));
}

// Include Header
include '../includes/admin_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
</head>
<body class="bg-gray-100 font-sans">

    <div class="max-w-md mx-auto mt-8 p-6 bg-white rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">My Profile</h1>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php else: ?>
            <div class="mb-4 pb-4 border-b border-gray-200">
                <label class="block font-semibold text-gray-700 mb-1">Full Name:</label>
                <span class="text-gray-900 text-lg"><?php echo $full_name_db; ?></span>
            </div>
            <div class="mb-4 pb-4 border-b border-gray-200">
                <label class="block font-semibold text-gray-700 mb-1">Username:</label>
                <span class="text-gray-900 text-lg"><?php echo $username_db; ?></span>
            </div>
            <div class="mb-4 pb-4 border-b border-gray-200">
                <label class="block font-semibold text-gray-700 mb-1">Role:</label>
                <span class="text-gray-900 text-lg"><?php echo $role_db; ?></span>
            </div>
            <div class="mb-4 pb-4 border-b border-gray-200">
                <label class="block font-semibold text-gray-700 mb-1">Account Created:</label>
                <span class="text-gray-900 text-lg"><?php echo $created_at_db; ?></span>
            </div>
            <div class="mb-4 pb-4 border-b border-gray-200">
                <label class="block font-semibold text-gray-700 mb-1">Last Login:</label>
                <span class="text-gray-900 text-lg"><?php echo $last_login_db; ?></span>
            </div>
            <div class="mt-6 text-center">
                <a href="settings.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-200">Edit Profile & Settings</a>
            </div>
        <?php endif; ?>
    </div>
    <?php
    // Close DB connection
    if (isset($link) && $link) {
        mysqli_close($link);
    }
    ?>
</body>
</html>
