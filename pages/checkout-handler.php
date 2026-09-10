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

    // Determine primary branch_id from allocations (if single branch or first selected branch)
    $primaryBranchId = null;
    $uniqueBranchIds = [];
    foreach ($allocations as $prodAlloc) {
        foreach ($prodAlloc as $bId => $q) {
            if ($q > 0) {
                $uniqueBranchIds[$bId] = true;
            }
        }
    }
    $branchKeys = array_keys($uniqueBranchIds);
    if (count($branchKeys) === 1) {
        $primaryBranchId = $branchKeys[0];
    } elseif (count($branchKeys) > 1) {
        // Multi-branch order: Use first allocated branch as primary fulfillment reference
        $primaryBranchId = $branchKeys[0];
    }

    // Create order with primary branch reference (or NULL if multi-branch)
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, branch_id, total_amount, status, payment_method, shipping_address, notes)
        VALUES (?, ?, ?, 'pending', ?, ?, ?)
    ");
    $totalAmount = getCartTotal($pdo, $cartId);
    $notes = count($branchKeys) > 1 ? "Multi-branch order. Items allocated across multiple branches." : "Standard order.";
    $stmt->execute([$userId, $primaryBranchId, $totalAmount, $paymentMethod, $shippingAddress, $notes]);
    $orderId = $pdo->lastInsertId();

    // Reserve inventory and create order_items
    $orderItemStmt = $pdo->prepare("
        INSERT INTO order_items (order_id, inventory_id, price_at_sale, quantity)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($allocations as $productId => $branchAlloc) {
        // Fetch current product price once per product
        $priceStmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $priceStmt->execute([$productId]);
        $price = $priceStmt->fetchColumn() ?: 0;

        foreach ($branchAlloc as $branchId => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) continue;

            // Get specific inventory IDs for this branch and product, using explicit integer bind for LIMIT
            $stmt = $pdo->prepare("
                SELECT id FROM inventory 
                WHERE product_id = :product_id AND branch_id = :branch_id AND status = 'in_stock'
                LIMIT :limit_qty
            ");
            $stmt->bindValue(':product_id', (int)$productId, PDO::PARAM_INT);
            $stmt->bindValue(':branch_id', (int)$branchId, PDO::PARAM_INT);
            $stmt->bindValue(':limit_qty', $qty, PDO::PARAM_INT);
            $stmt->execute();
            $inventoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($inventoryIds) < $qty) {
                throw new Exception("Inventory stock changed during checkout for one or more items. Please review your branch selections.");
            }

            // Reserve (mark as reserved) and create order_items
            $reserveStmt = $pdo->prepare("UPDATE inventory SET status = 'reserved', reserved_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?");
            foreach ($inventoryIds as $invId) {
                $reserveStmt->execute([$invId]);
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

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Check for foreign key or integrity violations and provide user-friendly explanation
    if ($e->getCode() == '23000') {
        $userError = "We were unable to complete your checkout due to an invalid branch or inventory assignment. Please check your branch allocations and try again.";
    } else {
        $userError = "A database error occurred while processing your order. Please try again or contact customer support.";
    }
    header('Location: checkout.php?error=' . urlencode($userError));
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $rawMsg = $e->getMessage();
    // Translate technical messages into clean user-friendly messages
    if (stripos($rawMsg, 'Integrity constraint') !== false || stripos($rawMsg, 'foreign key') !== false) {
        $userError = "We encountered an issue linking your order to the selected branch. Please reselect your branch quantities and try again.";
    } else {
        $userError = $rawMsg;
    }
    header('Location: checkout.php?error=' . urlencode($userError));
    exit;
}