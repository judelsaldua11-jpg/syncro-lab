<?php
// pages/checkout-handler.php - Process Order with Branch Splitting

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

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
if (empty($cartItems)) {
    header('Location: cart.php?error=Your cart is empty.');
    exit;
}

// Get form data
$shippingAddress = trim($_POST['shipping_address'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? '');
$terms = isset($_POST['terms']);

// Validate
if (empty($shippingAddress) || empty($paymentMethod) || !$terms) {
    header('Location: checkout.php?error=Please fill in all fields and agree to terms.');
    exit;
}

// Validate branch quantities
$branchQuantities = $_POST['branch_quantity'] ?? [];
$productNeeded = $_POST['product_needed'] ?? [];

// Build array of allocations: product_id => branch_id => quantity
$allocations = [];
foreach ($branchQuantities as $productId => $branchQty) {
    $productId = (int)$productId;
    $needed = (int)($productNeeded[$productId] ?? 0);
    $totalAllocated = 0;
    foreach ($branchQty as $branchId => $qty) {
        $qty = (int)$qty;
        if ($qty > 0) {
            $allocations[$productId][(int)$branchId] = $qty;
            $totalAllocated += $qty;
        }
    }
    if ($totalAllocated != $needed) {
        // Find product name for error message
        $productName = '';
        foreach ($cartItems as $item) {
            if ($item['product_id'] == $productId) {
                $productName = $item['product_name'];
                break;
            }
        }
        header("Location: checkout.php?error=Quantity mismatch for $productName. Please allocate exactly $needed units.");
        exit;
    }
}

// Verify stock availability per branch
foreach ($allocations as $productId => $branchAlloc) {
    foreach ($branchAlloc as $branchId => $qty) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE product_id = ? AND branch_id = ? AND status = 'in_stock'");
        $stmt->execute([$productId, $branchId]);
        $available = (int)$stmt->fetchColumn();
        if ($qty > $available) {
            header("Location: checkout.php?error=Not enough stock for product ID $productId at branch $branchId.");
            exit;
        }
    }
}

// Begin transaction
try {
    $pdo->beginTransaction();

    // Create order (without branch_id, since it's multi-branch)
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, total_amount, status, payment_method, shipping_address, notes)
        VALUES (?, ?, 'pending', ?, ?, ?)
    ");
    $totalAmount = getCartTotal($pdo, $cartId);
    $notes = "Multi-branch order. Items allocated to branches.";
    $stmt->execute([$userId, $totalAmount, $paymentMethod, $shippingAddress, $notes]);
    $orderId = $pdo->lastInsertId();

    // Reserve inventory and create order_items
    $orderItemStmt = $pdo->prepare("
        INSERT INTO order_items (order_id, inventory_id, price_at_sale, quantity)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($allocations as $productId => $branchAlloc) {
        foreach ($branchAlloc as $branchId => $qty) {
            // Get specific inventory IDs for this branch and product, limited to qty
            $stmt = $pdo->prepare("
                SELECT id FROM inventory 
                WHERE product_id = ? AND branch_id = ? AND status = 'in_stock'
                LIMIT ?
            ");
            $stmt->execute([$productId, $branchId, $qty]);
            $inventoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($inventoryIds) < $qty) {
                throw new Exception("Not enough inventory for product $productId at branch $branchId.");
            }

            // Reserve (mark as reserved) and create order_items
            foreach ($inventoryIds as $invId) {
                // Reserve
                $stmt = $pdo->prepare("UPDATE inventory SET status = 'reserved', reserved_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?");
                $stmt->execute([$invId]);

                // Get price at sale (current product price)
                $priceStmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                $priceStmt->execute([$productId]);
                $price = $priceStmt->fetchColumn();

                // Create order_item
                $orderItemStmt->execute([$orderId, $invId, $price, 1]);
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