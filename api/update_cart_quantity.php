<?php
// scps1/api/update_cart_quantity.php - Updates the quantity of an item in the session cart

session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$food_id = $input['food_id'] ?? null;
$action = $input['action'] ?? null; // 'increase' or 'decrease'

if (empty($food_id) || !in_array($action, ['increase', 'decrease'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

if (!isset($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit();
}

$item_found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['food_id'] == $food_id) {
        if ($action === 'increase') {
            $item['quantity']++;
        } elseif ($action === 'decrease') {
            $item['quantity']--;
            if ($item['quantity'] <= 0) {
                // If quantity drops to 0 or less, mark for removal
                $item['remove'] = true;
            }
        }
        $item_found = true;
        break;
    }
}
unset($item); // Break the reference

if ($item_found) {
    // Filter out items marked for removal
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function($item) {
        return !isset($item['remove']);
    }));
    echo json_encode(['success' => true, 'message' => 'Cart quantity updated.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart.']);
}
?>
