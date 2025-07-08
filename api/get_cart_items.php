<?php
// scps1/api/get_cart_items.php - Retrieves current cart items from session

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

echo json_encode(['success' => true, 'cart' => array_values($_SESSION['cart'])]);
?>
