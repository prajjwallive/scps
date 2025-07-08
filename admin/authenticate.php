<?php
// admin/authenticate.php - Handles admin login form submission with Activity Logging

// Start the session. NO output before this line.
session_start();

// Include database connection
require_once '../includes/db_connection.php'; // Path goes UP one directory to root, then into includes

// Handle POST request from admin login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize user input
    $username = mysqli_real_escape_string($link, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // --- Basic Input Validation ---
    if (empty($username) || empty($password)) {
        $_SESSION['admin_login_error'] = 'Please enter both username and password.';
        header('Location: login.php'); // Redirect back to admin login (in same folder)
        exit();
    }
    // --- End Input Validation ---


    // --- Find the admin user by username ---
    $sql_find_admin = "SELECT staff_id, username, password_hash, role, is_active FROM staff WHERE username = ?";

    if ($stmt_find_admin = mysqli_prepare($link, $sql_find_admin)) {
        mysqli_stmt_bind_param($stmt_find_admin, "s", $username);

        if (mysqli_stmt_execute($stmt_find_admin)) {
            $result_find_admin = mysqli_stmt_get_result($stmt_find_admin);

            if (mysqli_num_rows($result_find_admin) === 1) {
                $admin_data = mysqli_fetch_assoc($result_find_admin);

                // Verify the password
                if (password_verify($password, $admin_data['password_hash'])) {
                    // Password matches - Admin is authenticated!

                    // Check if admin is active
                    if ($admin_data['is_active'] == 0) {
                        $_SESSION['admin_login_error'] = 'Your account is currently inactive. Please contact support.';
                        error_log('Admin Login Failed: Inactive account for username: ' . $username);
                        header('Location: login.php');
                        exit();
                    }

                    // Set session variables
                    $_SESSION['admin_id'] = $admin_data['staff_id'];
                    $_SESSION['admin_username'] = $admin_data['username'];
                    $_SESSION['admin_role'] = $admin_data['role'];

                    // --- START ADDED CODE BLOCK ---
                    // Line 57: Update last_login timestamp for the logged-in admin
                    $admin_id_to_update = $admin_data['staff_id']; // Use the ID fetched from the database
                    $sql_update_last_login = "UPDATE staff SET last_login = NOW() WHERE staff_id = ?";
                    if ($stmt_update = mysqli_prepare($link, $sql_update_last_login)) {
                        mysqli_stmt_bind_param($stmt_update, "i", $admin_id_to_update);
                        if (mysqli_stmt_execute($stmt_update)) {
                            // Successfully updated last_login
                            // You can add a log here if needed:
                            // error_log("Last login updated for admin_id: " . $admin_id_to_update);
                        } else {
                            // Log error if update fails (important for debugging)
                            error_log("Error updating last_login for admin ID {$admin_id_to_update}: " . mysqli_stmt_error($stmt_update));
                        }
                        mysqli_stmt_close($stmt_update);
                    } else {
                        error_log("Error preparing last_login update statement: " . mysqli_error($link));
                    }
                    // --- END ADDED CODE BLOCK ---

                    // Redirect to the admin dashboard (in the same admin folder)
                    header('Location: dashboard.php');
                    exit();
                } else {
                    // Password does not match
                    $_SESSION['admin_login_error'] = 'Invalid credentials.';
                     error_log('Admin Login Failed: Password mismatch for username: ' . $username);
                }

            } else {
                // No admin user found or multiple found
                $_SESSION['admin_login_error'] = 'Invalid credentials.';
                 error_log('Admin Login Failed: User not found or multiple found for username: ' . $username);
            }
             mysqli_stmt_close($stmt_find_admin);

        } else {
            $_SESSION['admin_login_error'] = 'Database error during login.';
            error_log('DB Error: execute admin fetch: ' . mysqli_stmt_error($stmt_find_admin));
        }
    } else {
         $_SESSION['admin_login_error'] = 'Database error during login.';
         error_log('DB Error: prepare admin fetch: ' . mysqli_error($link));
    }

} else {
    // If the request method is not POST
    $_SESSION['admin_login_error'] = 'Invalid request method.';
     error_log('admin_authenticate.php received non-POST request.');
}

mysqli_close($link);

// Redirect back to the admin login page in case of any failure
header('Location: login.php'); // Redirect back to admin login (in same folder)
exit();

// Note: No closing PHP tag is intentional to prevent accidental whitespace issues.