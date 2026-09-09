<?php
// pages/reorder.php - Reorder functionality (AJAX)

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in.']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$userId = $_SESSION['user_id'];

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

$pdo = getConnection();

// Verify order belongs to this user
$stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || $order['user_id'] != $userId) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT i.product_id, oi.quantity
    FROM order_items oi
    JOIN inventory i ON oi.inventory_id = i.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'No items in this order.']);
    exit;
}

// Get or create cart
$cartId = getOrCreateCart($pdo, $userId);

$addedCount = 0;
foreach ($items as $item) {
    // Check stock availability
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE product_id = ? AND status = 'in_stock'");
    $stmt->execute([$item['product_id']]);
    $stock = (int)$stmt->fetchColumn();
    
    if ($stock > 0) {
        $quantity = min($item['quantity'], $stock);
        addToCart($pdo, $cartId, $item['product_id'], $quantity);
        $addedCount++;
    }
}

if ($addedCount > 0) {
    echo json_encode(['success' => true, 'message' => "$addedCount items added to cart."]);
} else {
    echo json_encode(['success' => false, 'message' => 'No items available to reorder.']);
}