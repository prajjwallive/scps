<?php
// scps1/api/add_to_cart.php - Adds an item to the session cart with image support

session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$food_id = $input['food_id'] ?? null;
$food_name = $input['food_name'] ?? null;
$price = $input['price'] ?? null;
$quantity = $input['quantity'] ?? 1; // Default to 1 if not provided
$image_path = $input['image_path'] ?? ''; // Get image path (empty string if not provided)

if (empty($food_id) || empty($food_name) || !isset($price)) {
    echo json_encode(['success' => false, 'message' => 'Invalid item data.']);
    exit();
}

// Ensure cart exists in session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$item_added = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['food_id'] == $food_id) {
        $item['quantity'] += $quantity;
        $item_added = true;
        break;
    }
}
unset($item); // Break the reference with the last element

if (!$item_added) {
    $_SESSION['cart'][] = [
        'food_id' => $food_id,
        'food_name' => $food_name,
        'price' => $price,
        'quantity' => $quantity,
        'image_path' => $image_path // Store the image path
    ];
}

echo json_encode(['success' => true, 'message' => 'Item added to cart.']);
?>