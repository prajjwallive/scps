<?php
// api/get_food_items.php - Fetches available food items, with optional exclusions

header('Content-Type: application/json');
session_start(); // Start session if needed for auth, though this public API might not strictly require it for all cases

// Include database connection
require_once '../includes/db_connection.php'; // Assuming api/ is directly under scps1/ and includes/ is under scps1/

$response = ['success' => false, 'message' => 'An unexpected error occurred.', 'food_items' => []];

// Get excluded food IDs from POST body (preferred for larger arrays) or GET parameter
// For simplicity, we'll accept it via POST JSON or GET query parameter.
// Since script.js will send it via POST, we prioritize that.
$data = json_decode(file_get_contents('php://input'), true);
$excluded_food_ids = $data['excluded_food_ids'] ?? [];

// Fallback for GET request if needed (e.g., direct browser test with ?exclude=1,2,3)
if (empty($excluded_food_ids) && isset($_GET['exclude'])) {
    $excluded_food_ids = array_map('intval', explode(',', $_GET['exclude']));
}

// Ensure excluded_food_ids is an array of integers
$excluded_food_ids = array_filter(array_map('intval', (array)$excluded_food_ids));

if (!$link) {
    $response['message'] = 'Database connection failed.';
    error_log("get_food_items.php: Database connection failed: " . mysqli_connect_error());
    echo json_encode($response);
    exit();
}

try {
    $sql = "SELECT food_id, name, price, image_path, category, description 
            FROM food 
            WHERE is_available = 1";
    
    // Add exclusion condition if there are IDs to exclude
    if (!empty($excluded_food_ids)) {
        // Create a comma-separated string of placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($excluded_food_ids), '?'));
        $sql .= " AND food_id NOT IN ($placeholders)";
    }
    
    $sql .= " ORDER BY name"; // Order by name for consistent display

    $stmt = $link->prepare($sql);

    if (!$stmt) {
        $response['message'] = 'Failed to prepare SQL statement.';
        error_log("get_food_items.php: Prepare failed: " . $link->error);
        echo json_encode($response);
        exit();
    }

    // Bind parameters if there are excluded IDs
    if (!empty($excluded_food_ids)) {
        // Create a string of 'i's for each integer parameter
        $types = str_repeat('i', count($excluded_food_ids));
        // Use call_user_func_array to bind parameters dynamically
        $bind_params = array_merge([$types], $excluded_food_ids);
        call_user_func_array([$stmt, 'bind_param'], refValues($bind_params));
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $food_items = [];
    while ($row = $result->fetch_assoc()) {
        $food_items[] = $row;
    }

    $stmt->close();

    $response['success'] = true;
    $response['message'] = 'Food items fetched successfully.';
    $response['food_items'] = $food_items;

} catch (Exception $e) {
    $response['message'] = 'Database query error: ' . $e->getMessage();
    error_log("get_food_items.php: Exception: " . $e->getMessage());
} finally {
    $link->close();
}

echo json_encode($response);

// Helper function for dynamic bind_param (required for call_user_func_array)
function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) // PHP 5.3+
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}
?>
