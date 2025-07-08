<?php
// scps1/api/clear_cart.php - Clears the session cart

session_start();
header('Content-Type: application/json');

if (isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // Empty the cart array
    echo json_encode(['success' => true, 'message' => 'Cart cleared successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Cart was already empty.']);
}
?>
