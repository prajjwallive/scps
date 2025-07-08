<?php
// api/manage_time_menu.php - Handles adding/removing items from time-based menus

header('Content-Type: application/json');
session_start(); // Start session to access admin_logged_in

// Ensure admin is logged in (using admin_id for consistency with login.php)
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

// Path to db_connection.php:
// manage_time_menu.php is in C:\xampp\htdocs\scps1\admin\api\
// db_connection.php is in C:\xampp\htdocs\scps1\includes\
// So, from admin/api/, go up two levels (../../) to scps1/, then into includes/.
require_once '../../includes/db_connection.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Check if JSON decoding was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("manage_time_menu.php: JSON decode error - " . json_last_error_msg());
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit();
}

$action = $data['action'] ?? '';
$menu_type = $data['menu_type'] ?? '';
$food_id = $data['food_id'] ?? null; // Use null to differentiate from 0 or empty string

// Validate required parameters
if (empty($action) || empty($menu_type) || $food_id === null) {
    error_log("manage_time_menu.php: Missing required parameters. Action: {$action}, Menu Type: {$menu_type}, Food ID: " . var_export($food_id, true));
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit();
}

// Validate menu type
if (!in_array($menu_type, ['Breakfast', 'Lunch', 'Dinner'])) {
    error_log("manage_time_menu.php: Invalid menu type provided: {$menu_type}");
    echo json_encode(['success' => false, 'message' => 'Invalid menu type.']);
    exit();
}

// Ensure food_id is an integer
$food_id = (int)$food_id;
if ($food_id <= 0) {
    error_log("manage_time_menu.php: Invalid food_id provided: {$food_id}");
    echo json_encode(['success' => false, 'message' => 'Invalid food item selected.']);
    exit();
}

if ($action === 'add') {
    // Check if the item already exists in the menu to avoid duplicates
    $check_sql = "SELECT id FROM time_based_menu WHERE menu_type = ? AND food_id = ?";
    $check_stmt = $link->prepare($check_sql);
    if (!$check_stmt) {
        error_log("manage_time_menu.php (add): Prepare failed: " . $link->error);
        echo json_encode(['success' => false, 'message' => 'Database error (check prepare).']);
        exit();
    }
    $check_stmt->bind_param("si", $menu_type, $food_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Item already exists in this menu.']);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();

    // Insert new item
    $sql = "INSERT INTO time_based_menu (menu_type, food_id) VALUES (?, ?)";
    $stmt = $link->prepare($sql);
    if (!$stmt) {
        error_log("manage_time_menu.php (add): Prepare failed: " . $link->error);
        echo json_encode(['success' => false, 'message' => 'Database error (insert prepare).']);
        exit();
    }
    $stmt->bind_param("si", $menu_type, $food_id);
    if ($stmt->execute()) {
        // Fetch the newly added item's details to return to the frontend
        $fetch_sql = "SELECT food_id, name, image_path FROM food WHERE food_id = ?";
        $fetch_stmt = $link->prepare($fetch_sql);
        if (!$fetch_stmt) {
            error_log("manage_time_menu.php (add): Prepare failed (fetch item): " . $link->error);
            echo json_encode(['success' => false, 'message' => 'Database error (fetch item prepare).']);
            exit();
        }
        $fetch_stmt->bind_param("i", $food_id);
        $fetch_stmt->execute();
        $item_result = $fetch_stmt->get_result()->fetch_assoc();
        $fetch_stmt->close();

        echo json_encode(['success' => true, 'item' => $item_result]);
    } else {
        error_log("manage_time_menu.php (add): Execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to add item. Database error.']);
    }
    $stmt->close();

} elseif ($action === 'remove') {
    $sql = "DELETE FROM time_based_menu WHERE menu_type = ? AND food_id = ?";
    $stmt = $link->prepare($sql);
    if (!$stmt) {
        error_log("manage_time_menu.php (remove): Prepare failed: " . $link->error);
        echo json_encode(['success' => false, 'message' => 'Database error (delete prepare).']);
        exit();
    }
    $stmt->bind_param("si", $menu_type, $food_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) { // Check if any rows were actually deleted
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found in this menu.']);
        }
    } else {
        error_log("manage_time_menu.php (remove): Execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to remove item. Database error.']);
    }
    $stmt->close();
} else {
    error_log("manage_time_menu.php: Invalid action specified: {$action}");
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

$link->close(); // Close database connection
