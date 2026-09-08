<?php
// pages/checkout.php - Checkout with Manual Branch Selection

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to checkout.');
    exit;
}

$pdo = getConnection();
$userId = $_SESSION['user_id'];
$cartId = getOrCreateCart($pdo, $userId);

// Get cart items
$cartItems = getCartItems($pdo, $cartId);
$cartTotal = getCartTotal($pdo, $cartId);
$cartCount = getCartCount($pdo, $cartId);

if (empty($cartItems)) {
    header('Location: cart.php?error=Your cart is empty.');
    exit;
}

// Get all branches (active)
$branches = getBranches();

// For each product, get stock per branch
$productStock = [];
foreach ($cartItems as $item) {
    $stmt = $pdo->prepare("
        SELECT b.id AS branch_id, b.name AS branch_name, 
               COUNT(i.id) AS stock_count
        FROM inventory i
        JOIN branches b ON i.branch_id = b.id
        WHERE i.product_id = ? AND i.status = 'in_stock' AND b.is_active = 1
        GROUP BY b.id
        HAVING stock_count > 0
        ORDER BY stock_count DESC
    ");
    $stmt->execute([$item['product_id']]);
    $productStock[$item['product_id']] = $stmt->fetchAll();
}

// If any product has zero stock across all branches, show error
$zeroStock = false;
foreach ($cartItems as $item) {
    if (empty($productStock[$item['product_id']])) {
        $zeroStock = true;
        $_SESSION['checkout_error'] = "Product '{$item['product_name']}' is out of stock. Please remove it from your cart.";
    }
}
if ($zeroStock) {
    header('Location: cart.php');
    exit;
}

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 900px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            Checkout
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Select branches to fulfill each product. You can split quantities across multiple branches.
        </p>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="checkout-handler.php">
            <div style="display: grid; gap: 32px;">
                
                <?php foreach ($cartItems as $index => $item): ?>
                    <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 24px; box-shadow: var(--shadow);">
                        <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 8px;">
                            <?= htmlspecialchars($item['product_name']) ?>
                        </h3>
                        <p style="color: var(--gray-dark); font-size: 14px;">
                            Quantity needed: <?= $item['quantity'] ?>
                        </p>

                        <div style="margin-top: 16px;">
                            <p style="font-weight: 700; font-size: 14px; margin-bottom: 8px;">Select branches and quantities:</p>
                            <?php foreach ($productStock[$item['product_id']] as $branchStock): ?>
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                    <input type="number" 
                                           name="branch_quantity[<?= $item['product_id'] ?>][<?= $branchStock['branch_id'] ?>]" 
                                           min="0" max="<?= $branchStock['stock_count'] ?>" 
                                           value="0" 
                                           style="width: 70px; padding: 6px; border: 2px solid var(--gray); border-radius: var(--radius); text-align: center;">
                                    <span style="font-weight: 600;"><?= htmlspecialchars($branchStock['branch_name']) ?></span>
                                    <span style="color: var(--gray-dark); font-size: 14px;">(<?= $branchStock['stock_count'] ?> available)</span>
                                </div>
                            <?php endforeach; ?>
                            <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                                The total quantity must equal <?= $item['quantity'] ?>.
                            </p>
                        </div>
                        <input type="hidden" name="product_needed[<?= $item['product_id'] ?>]" value="<?= $item['quantity'] ?>">
                    </div>
                <?php endforeach; ?>

                <!-- Shipping Address & Payment -->
                <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 24px; box-shadow: var(--shadow);">
                    <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 16px;">Shipping Details</h2>
                    <div style="display: grid; gap: 16px;">
                        <div>
                            <label for="shipping_address" style="font-weight: 700; display: block; margin-bottom: 4px;">Shipping Address *</label>
                            <textarea id="shipping_address" name="shipping_address" rows="3" required
                                      style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; font-family: inherit;"><?= htmlspecialchars($_SESSION['user_address'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label for="payment_method" style="font-weight: 700; display: block; margin-bottom: 4px;">Payment Method *</label>
                            <select id="payment_method" name="payment_method" required
                                    style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                                <option value="cash_on_delivery">Cash on Delivery</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="gcash">GCash</option>
                                <option value="paymaya">PayMaya</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="terms" required>
                                I agree to the terms and conditions
                            </label>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 16px; justify-content: flex-end;">
                    <a href="cart.php" class="btn btn--outline" style="height: 48px; font-size: 16px; padding: 0 32px;">Back to Cart</a>
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        PLACE ORDER
                    </button>
                </div>

            </div>
        </form>

    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>