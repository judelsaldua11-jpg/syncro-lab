<?php
// pages/cart.php - Shopping Cart

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

$pdo = getConnection();
$userId = $_SESSION['user_id'] ?? null;
$cartId = getOrCreateCart($pdo, $userId);

// Handle Add to Cart
if (isset($_GET['action']) && $_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($productId > 0 && $quantity > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE product_id = ? AND status = 'in_stock'");
        $stmt->execute([$productId]);
        $totalStock = (int)$stmt->fetchColumn();
        if ($quantity > $totalStock) {
            $quantity = $totalStock;
            $_SESSION['cart_message'] = "Quantity adjusted to available stock ($totalStock).";
        }
        addToCart($pdo, $cartId, $productId, $quantity);
    }
    header('Location: cart.php');
    exit;
}

// Handle Quantity Update (auto-submit)
if (isset($_GET['action']) && $_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    if ($productId > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE product_id = ? AND status = 'in_stock'");
        $stmt->execute([$productId]);
        $totalStock = (int)$stmt->fetchColumn();
        if ($quantity > $totalStock) {
            $quantity = $totalStock;
            $_SESSION['cart_message'] = "Quantity adjusted to available stock ($totalStock).";
        }
        if ($quantity <= 0) {
            removeFromCart($pdo, $cartId, $productId);
        } else {
            updateCartQuantity($pdo, $cartId, $productId, $quantity);
        }
    }
    header('Location: cart.php');
    exit;
}

// Handle Remove
$action = $_GET['action'] ?? '';
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($action === 'remove' && $productId > 0) {
    removeFromCart($pdo, $cartId, $productId);
    header('Location: cart.php');
    exit;
}

// Get cart items
$cartItems = getCartItems($pdo, $cartId);
$cartTotal = getCartTotal($pdo, $cartId);
$cartCount = getCartCount($pdo, $cartId);
$message = $_SESSION['cart_message'] ?? '';
unset($_SESSION['cart_message']);

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div class="cart-page">
    <div class="cart-page-container">
        <div class="cart-page-header">
            <a href="../index.php" class="cart-page-brand">SYNCRO LAB</a>
            <a href="shop.php" class="cart-page-shop-link">Continue Shopping →</a>
        </div>

        <h1 class="cart-page-title">Your Cart</h1>
        <p class="cart-page-intro">
            <?= $cartCount > 0 ? "You have {$cartCount} item(s) in your cart." : "Your cart is empty." ?>
        </p>

        <?php if ($message): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: var(--radius); margin-bottom: 20px; border-left: 4px solid var(--green);">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="cart-page-empty">
                <strong>Your cart is empty</strong>
                <p>Browse the catalog to add precision components, rider gear, or services.</p>
                <div style="margin-top: 20px;">
                    <a href="shop.php" class="btn btn--green">CONTINUE SHOPPING</a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-page-layout">
                <section class="cart-page-panel" aria-labelledby="cart-items-title">
                    <h2 id="cart-items-title">Cart Items</h2>
                    
                    <?php foreach ($cartItems as $item): ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE product_id = ? AND status = 'in_stock'");
                        $stmt->execute([$item['product_id']]);
                        $totalStock = (int)$stmt->fetchColumn();
                        $isMax = ($item['quantity'] >= $totalStock);
                        ?>
                        <div style="display: flex; gap: 16px; padding: 16px 0; border-bottom: 1px solid var(--gray);">
                            <img src="<?= !empty($item['image']) ? '../' . htmlspecialchars($item['image']) : '../assets/images/placeholder.png' ?>" 
                                 alt="<?= htmlspecialchars($item['product_name']) ?>"
                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius);">
                            
                            <div style="flex: 1;">
                                <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 4px;">
                                    <?= htmlspecialchars($item['product_name']) ?>
                                </h3>
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    <?= htmlspecialchars($item['brand'] ?? '') ?>
                                </p>
                                <p style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">
                                    ₱ <?= number_format($item['price'], 2) ?>
                                </p>
                                <p style="color: var(--gray-dark); font-size: 12px;">
                                    Available stock: <?= $totalStock ?>
                                </p>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <!-- QUANTITY INPUT -->
                                <form method="POST" action="?action=update" style="display: flex; align-items: center; gap: 8px;">
                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="0" max="<?= $totalStock ?>" 
                                           oninput="this.form.submit()" 
                                           onchange="this.form.submit()"
                                           style="width: 60px; height: 40px; padding: 0 8px; border: 2px solid var(--gray); border-radius: var(--radius); text-align: center; font-size: 16px;">
                                    <?php if ($isMax): ?>
                                        <span style="font-size: 12px; color: var(--green); font-weight: 700;">MAX</span>
                                    <?php endif; ?>
                                </form>
                                
                                <a href="?action=remove&product_id=<?= $item['product_id'] ?>" 
                                   onclick="return confirm('Remove this item from cart?')"
                                   style="color: #d9534f; font-weight: 700; text-decoration: none; font-size: 20px;">✕</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                </section>

                <aside class="cart-page-panel" aria-labelledby="cart-summary-title">
                    <h2 id="cart-summary-title">Order Summary</h2>
                    
                    <div class="cart-summary-row">
                        <span>Items</span>
                        <span><?= $cartCount ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Subtotal</span>
                        <span>₱ <?= number_format($cartTotal, 2) ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>
                    <div class="cart-summary-row cart-summary-total">
                        <span>Total</span>
                        <span>₱ <?= number_format($cartTotal, 2) ?></span>
                    </div>
                    
                    <a href="checkout.php" class="btn btn--green" style="width: 100%; justify-content: center; margin-top: 16px;">
                        PROCEED TO CHECKOUT
                    </a>
                </aside>
            </div>
        <?php endif; ?>
        
        <p class="cart-page-back">
            <a href="../index.php" class="cart-page-link">← Back to SYNCRO LAB</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>