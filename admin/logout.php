<?php
// admin/logout.php - Handles logging out the admin (in admin folder)

// Start the session. NO output before this line.
session_start();

// Unset specific admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);
// Keep other session variables (like student cart) if they should persist after admin logout

// If you want to destroy the ENTIRE session (including student cart), use:
// $_SESSION = array(); // Clear the $_SESSION array
// if (ini_get("session.use_cookies")) {
//     $params = session_get_cookie_params();
//     setcookie(session_name(), '', time() - 42000,
//         $params["path"], $params["domain"],
//         $params["secure"], $params["httponly"]
//     );
// }
// session_destroy();


// Redirect to the admin login page (in same folder)
header('Location: login.php');
exit();

// Note: No closing PHP tag is intentional