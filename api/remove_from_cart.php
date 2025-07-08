<?php
// scps1/api/remove_from_cart.php - Removes an item from the session cart

session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$food_id = $input['food_id'] ?? null;

if (empty($food_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid food ID.']);
    exit();
}

if (!isset($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit();
}

$initial_cart_count = count($_SESSION['cart']);
$_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function($item) use ($food_id) {
    return $item['food_id'] != $food_id;
}));

if (count($_SESSION['cart']) < $initial_cart_count) {
    echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart.']);
}
?>
