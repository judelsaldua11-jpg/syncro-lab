<?php
// pages/checkout-handler.php - Process Order with Branch Splitting
// Auto-applies 10% member discount for active members.

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

$pdo    = getConnection();
$userId = $_SESSION['user_id'];
$cartId = getOrCreateCart($pdo, $userId);

$cartItems = getCartItems($pdo, $cartId);
if (empty($cartItems)) {
    header('Location: cart.php?error=Your cart is empty.');
    exit;
}

/* ── Form data ─────────────────────────────────────────── */
$shippingAddress = trim($_POST['shipping_address'] ?? '');
$paymentMethod   = trim($_POST['payment_method'] ?? '');
$walletNumber    = trim($_POST['wallet_number'] ?? '');
$cardName        = trim($_POST['card_name'] ?? '');
$cardNumber      = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
$cardExpiry      = trim($_POST['card_expiry'] ?? '');
$cardCvv         = trim($_POST['card_cvv'] ?? '');
$terms           = isset($_POST['terms']);

if (empty($shippingAddress) || empty($paymentMethod) || !$terms) {
    header('Location: checkout.php?error=Please fill in all fields and agree to terms.');
    exit;
}

$validMethods = ['cash_on_delivery', 'credit_card', 'gcash', 'paymaya'];
if (!in_array($paymentMethod, $validMethods)) {
    header('Location: checkout.php?error=Please select a valid payment method.');
    exit;
}

/* ── Payment reference / details ──────────────────────── */
$paymentReference = null;
$paymentDetails   = null;

if ($paymentMethod === 'gcash' || $paymentMethod === 'paymaya') {
    if (!preg_match('/^09\d{9}$/', $walletNumber)) {
        header('Location: checkout.php?error=Please enter a valid 11-digit mobile number for your e-wallet.');
        exit;
    }
    $maskedWallet     = substr($walletNumber, 0, 3) . str_repeat('*', 5) . substr($walletNumber, -3);
    $paymentDetails   = $maskedWallet;
    $paymentReference = strtoupper(substr($paymentMethod, 0, 1)) . date('YmdHis') . rand(100, 999);

} elseif ($paymentMethod === 'credit_card') {
    if (empty($cardName))                         { header('Location: checkout.php?error=Please enter the cardholder name.'); exit; }
    if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) { header('Location: checkout.php?error=Please enter a valid card number.'); exit; }
    if (!preg_match('/^\d{2}\/\d{2}$/', $cardExpiry)) { header('Location: checkout.php?error=Please enter a valid card expiry (MM/YY).'); exit; }
    if (strlen($cardCvv) < 3)                     { header('Location: checkout.php?error=Please enter a valid CVV.'); exit; }

    $cardLast4        = substr($cardNumber, -4);
    $paymentDetails   = strtoupper($cardName) . ' •••• ' . $cardLast4;
    $paymentReference = 'CC' . date('YmdHis') . rand(100, 999);
}

/* ── Branch allocation validation ─────────────────────── */
$branchQuantities = $_POST['branch_quantity'] ?? [];
$productNeeded    = $_POST['product_needed'] ?? [];
$allocations      = [];

foreach ($branchQuantities as $productId => $branchQty) {
    $productId = (int)$productId;
    $needed    = (int)($productNeeded[$productId] ?? 0);
    $total     = 0;

    foreach ($branchQty as $branchId => $qty) {
        $qty = (int)$qty;
        if ($qty > 0) {
            $allocations[$productId][(int)$branchId] = $qty;
            $total += $qty;
        }
    }
    if ($total != $needed) {
        $name = '';
        foreach ($cartItems as $item) {
            if ($item['product_id'] == $productId) { $name = $item['product_name']; break; }
        }
        header("Location: checkout.php?error=Quantity mismatch for $name. Please allocate exactly $needed units.");
        exit;
    }
}

foreach ($allocations as $productId => $branchAlloc) {
    foreach ($branchAlloc as $branchId => $qty) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE product_id = ? AND branch_id = ? AND status = 'in_stock'");
        $stmt->execute([$productId, $branchId]);
        if ($qty > (int)$stmt->fetchColumn()) {
            header("Location: checkout.php?error=Not enough stock for product ID $productId at branch $branchId.");
            exit;
        }
    }
}

/* ═══════════════════════════════════════════════════════════
   AUTO-APPLY 10% MEMBER DISCOUNT
   ═══════════════════════════════════════════════════════════ */
$subtotal       = (float)getCartTotal($pdo, $cartId);
$discountAmount = 0.00;
$isMember       = false;

$stmt = $pdo->prepare("SELECT membership_expiry FROM users WHERE id = ?");
$stmt->execute([$userId]);
$memberExpiry = $stmt->fetchColumn();

if (!empty($memberExpiry) && strtotime($memberExpiry) > time()) {
    $isMember = true;
    $discountAmount = round($subtotal * 0.10, 2);
}

$totalAmount = round($subtotal - $discountAmount, 2);

/* ── Transaction ──────────────────────────────────────── */
try {
    $pdo->beginTransaction();

    // Determine primary branch
    $uniqueBranchIds = [];
    foreach ($allocations as $prodAlloc) {
        foreach ($prodAlloc as $bId => $q) {
            if ($q > 0) $uniqueBranchIds[$bId] = true;
        }
    }
    $branchKeys      = array_keys($uniqueBranchIds);
    $primaryBranchId = $branchKeys[0] ?? null;

    $notes = count($branchKeys) > 1 ? "Multi-branch order. Items allocated across multiple branches." : "Standard order.";
    if ($isMember && $discountAmount > 0) {
        $notes .= "\n[Member Discount] 10% applied — ₱" . number_format($discountAmount, 2) . " off subtotal ₱" . number_format($subtotal, 2);
    }

    $stmt = $pdo->prepare("
        INSERT INTO orders
            (user_id, branch_id, total_amount, subtotal, discount_amount, status,
             payment_method, payment_reference, payment_details, shipping_address, notes)
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId, $primaryBranchId, $totalAmount, $subtotal, $discountAmount,
        $paymentMethod, $paymentReference, $paymentDetails, $shippingAddress, $notes,
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // Reserve inventory + order items
    $orderItemStmt = $pdo->prepare("INSERT INTO order_items (order_id, inventory_id, price_at_sale, quantity) VALUES (?, ?, ?, ?)");

    foreach ($allocations as $productId => $branchAlloc) {
        $priceStmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $priceStmt->execute([$productId]);
        $price = (float)($priceStmt->fetchColumn() ?: 0);

        foreach ($branchAlloc as $branchId => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) continue;

            $stmt = $pdo->prepare("
                SELECT id FROM inventory
                WHERE product_id = :pid AND branch_id = :bid AND status = 'in_stock'
                LIMIT :qty
            ");
            $stmt->bindValue(':pid', (int)$productId, PDO::PARAM_INT);
            $stmt->bindValue(':bid', (int)$branchId, PDO::PARAM_INT);
            $stmt->bindValue(':qty', $qty, PDO::PARAM_INT);
            $stmt->execute();
            $inventoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($inventoryIds) < $qty) {
                throw new Exception("Inventory stock changed during checkout. Please review your branch selections.");
            }

            $reserveStmt = $pdo->prepare("UPDATE inventory SET status = 'reserved', reserved_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?");
            foreach ($inventoryIds as $invId) {
                $reserveStmt->execute([$invId]);
                $orderItemStmt->execute([$orderId, $invId, $price, 1]);
            }

            logInventoryActivity(
                $pdo,
                null,
                $productId,
                $branchId,
                'reserve',
                'in_stock',
                'reserved',
                $qty,
                "Reserved {$qty} item(s) for order #{$orderId}."
            );
        }
    }

    // Log the discount claim if applied
    if ($isMember && $discountAmount > 0) {
        $pdo->prepare("
            INSERT INTO membership_claims
                (user_id, benefit_type, branch_id, processed_by, related_order_id, notes)
            VALUES (?, 'discount_10', ?, NULL, ?, ?)
        ")->execute([
            $userId,
            $primaryBranchId,
            $orderId,
            'Auto-applied 10% member discount at checkout',
        ]);
    }

    clearCart($pdo, $cartId);
    $pdo->commit();

    header("Location: order-success.php?id=" . $orderId);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $userError = $e->getCode() == '23000'
        ? "We were unable to complete your checkout due to an invalid branch or inventory assignment."
        : "A database error occurred while processing your order. Please try again.";
    header('Location: checkout.php?error=' . urlencode($userError));
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $raw = $e->getMessage();
    $userError = (stripos($raw, 'Integrity constraint') !== false || stripos($raw, 'foreign key') !== false)
        ? "We encountered an issue linking your order to the selected branch. Please reselect your branch quantities."
        : $raw;
    header('Location: checkout.php?error=' . urlencode($userError));
    exit;
}