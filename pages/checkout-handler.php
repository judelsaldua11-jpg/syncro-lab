<?php
// pages/checkout-handler.php - Process Checkout

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to checkout.');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

$pdo = getConnection();
$userId = $_SESSION['user_id'];
$cartId = getOrCreateCart($pdo, $userId);

// Get cart items
$cartItems = getCartItems($pdo, $cartId);
$cartTotal = getCartTotal($pdo, $cartId);

// Check if cart is empty
if (empty($cartItems)) {
    header('Location: cart.php?error=Your cart is empty.');
    exit;
}

// Get form data
$shippingAddress = trim($_POST['shipping_address'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? '');
$branchId = (int)($_POST['branch_id'] ?? 0);
$terms = isset($_POST['terms']);

// Validate
if (empty($shippingAddress)) {
    header('Location: checkout.php?error=Please enter a shipping address.');
    exit;
}

if (empty($paymentMethod)) {
    header('Location: checkout.php?error=Please select a payment method.');
    exit;
}

if (!$terms) {
    header('Location: checkout.php?error=You must agree to the terms and conditions.');
    exit;
}

// If branch not selected, try to find nearest branch
if ($branchId <= 0) {
    // Get user's location (using IP or default)
    $userLat = 9.3167; // Default to Dumaguete
    $userLng = 123.3167;
    
    $productIds = array_column($cartItems, 'product_id');
    $nearestBranch = getNearestBranchWithStock($pdo, $productIds, $userLat, $userLng);
    
    if ($nearestBranch) {
        $branchId = $nearestBranch['branch_id'];
    } else {
        header('Location: checkout.php?error=No branch available with all items in stock. Please try a different order.');
        exit;
    }
}

// Verify branch has all items in stock
$productIds = array_column($cartItems, 'product_id');
$placeholders = implode(',', array_fill(0, count($productIds), '?'));

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT product_id) AS count
    FROM inventory
    WHERE branch_id = ? 
      AND product_id IN ($placeholders)
      AND status = 'in_stock'
");
$params = array_merge([$branchId], $productIds);
$stmt->execute($params);
$result = $stmt->fetch();

if ($result['count'] != count($productIds)) {
    header('Location: checkout.php?error=Selected branch does not have all items in stock. Please choose another branch.');
    exit;
}

// Begin transaction
try {
    $pdo->beginTransaction();
    
    // Reserve inventory items
    $reservedCount = 0;
    foreach ($cartItems as $item) {
        $reserved = reserveInventory($pdo, $item['product_id'], $branchId, $item['quantity']);
        if ($reserved < $item['quantity']) {
            throw new Exception("Not enough stock for {$item['product_name']}.");
        }
        $reservedCount += $reserved;
    }
    
    // Create order
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, branch_id, total_amount, status, payment_method, shipping_address, notes)
        VALUES (?, ?, ?, 'pending', ?, ?, ?)
    ");
    $notes = "Order placed via website. Items reserved.";
    $stmt->execute([$userId, $branchId, $cartTotal, $paymentMethod, $shippingAddress, $notes]);
    $orderId = $pdo->lastInsertId();
    
    // Get reserved inventory IDs for this order
    $stmt = $pdo->prepare("
        SELECT id, product_id 
        FROM inventory 
        WHERE branch_id = ? 
          AND product_id IN ($placeholders)
          AND status = 'reserved'
          AND reserved_until > NOW()
        LIMIT ?
    ");
    $stmt->execute(array_merge([$branchId], $productIds, [$reservedCount]));
    $reservedItems = $stmt->fetchAll();
    
    // Create order items
    $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, inventory_id, price_at_sale, quantity)
        VALUES (?, ?, ?, ?)
    ");
    
    $reservedIndex = 0;
    foreach ($cartItems as $item) {
        for ($i = 0; $i < $item['quantity']; $i++) {
            if (isset($reservedItems[$reservedIndex])) {
                $stmt->execute([
                    $orderId,
                    $reservedItems[$reservedIndex]['id'],
                    $item['price'],
                    1
                ]);
                $reservedIndex++;
            }
        }
    }
    
    // Clear cart
    clearCart($pdo, $cartId);
    
    $pdo->commit();
    
    // Redirect to success page
    header("Location: order-success.php?id=" . $orderId);
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: checkout.php?error=' . urlencode($e->getMessage()));
    exit;
}