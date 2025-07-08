<?php
// admin/login.php - Admin Login Form

// Start the session. NO output before this line.
session_start();

// Include database connection (needed if check if admin already logged in)
require_once '../includes/db_connection.php'; // Path goes UP one directory to root, then into includes

// --- Check if admin is already logged in ---
// If admin_id is already set in the session
if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
    // Redirect them to the admin dashboard (in the same admin folder)
    header('Location: dashboard.php');
    exit(); // Stop script execution
}
// --- End Check if already logged in ---

// Initialize variables for potential error messages
$error_message = '';

// Check for and retrieve error message from session (set by authenticate.php)
if (isset($_SESSION['admin_login_error'])) {
    $error_message = $_SESSION['admin_login_error'];
    // Clear the error message from the session
    unset($_SESSION['admin_login_error']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <?php include '../includes/packages.php'; // Path goes UP one directory to root, then into includes ?>
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f3f4f6; }
        .login-container { width: 100%; max-width: 400px; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="login-container bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-6">Admin Login</h2>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline"><?= htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="authenticate.php" method="POST">
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username:</label>
                <input type="text" id="username" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password:</label>
                <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                Login
            </button>
        </form>
    </div>

</body>
</html>
<?php // Note: No closing PHP tag is intentional ?>