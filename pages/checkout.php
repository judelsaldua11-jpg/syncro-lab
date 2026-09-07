<?php
// pages/checkout.php - Checkout Page

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check if user is logged in
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

// Check if cart is empty
if (empty($cartItems)) {
    header('Location: cart.php?error=Your cart is empty.');
    exit;
}

// Get user's branch if they have one (for managers)
$userBranchId = $_SESSION['branch_id'] ?? 0;

// Get all branches for selection
$branches = getBranches();

// Check stock availability
$stockErrors = [];
foreach ($cartItems as $item) {
    if ($item['quantity'] > $item['total_stock']) {
        $stockErrors[] = "Not enough stock for {$item['product_name']}. Available: {$item['total_stock']}";
    }
}

if (!empty($stockErrors)) {
    $_SESSION['checkout_error'] = implode(' ', $stockErrors);
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
            Review your order and complete your purchase.
        </p>

        <!-- Error Messages -->
        <?php if (isset($_GET['error'])): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="checkout-handler.php" style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
            
            <!-- Left: Order Summary -->
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 24px; box-shadow: var(--shadow);">
                <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 16px; text-transform: uppercase;">Your Order</h2>
                
                <?php foreach ($cartItems as $item): ?>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--gray);">
                        <span>
                            <?= htmlspecialchars($item['product_name']) ?>
                            <span style="color: var(--gray-dark); font-size: 14px;">× <?= $item['quantity'] ?></span>
                        </span>
                        <span>₱ <?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                    </div>
                <?php endforeach; ?>
                
                <div style="display: flex; justify-content: space-between; padding: 16px 0; border-top: 2px solid var(--gray); font-family: var(--font-heading); font-size: 24px;">
                    <span>Total</span>
                    <span style="color: var(--green);">₱ <?= number_format($cartTotal, 2) ?></span>
                </div>
                
                <a href="cart.php" style="color: var(--gray-dark); font-size: 14px;">← Back to Cart</a>
            </div>

            <!-- Right: Checkout Form -->
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 24px; box-shadow: var(--shadow);">
                <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 16px; text-transform: uppercase;">Shipping Details</h2>
                
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
                    
                    <?php if ($userBranchId > 0): ?>
                        <!-- If user is a manager, they have a default branch -->
                        <div>
                            <label style="font-weight: 700; display: block; margin-bottom: 4px;">Branch</label>
                            <p style="padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); background: var(--light); color: var(--gray-dark);">
                                <?php
                                $branchName = '';
                                foreach ($branches as $b) {
                                    if ($b['id'] == $userBranchId) {
                                        $branchName = $b['name'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($branchName);
                                ?>
                                <input type="hidden" name="branch_id" value="<?= $userBranchId ?>">
                            </p>
                            <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                                Your branch will fulfill this order.
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Regular customer: select branch -->
                        <div>
                            <label for="branch_id" style="font-weight: 700; display: block; margin-bottom: 4px;">Select Branch *</label>
                            <select id="branch_id" name="branch_id" required
                                    style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                                <option value="">-- Select Nearest Branch --</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?= $branch['id'] ?>">
                                        <?= htmlspecialchars($branch['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                                We'll try to assign your nearest branch automatically.
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="terms" required>
                            I agree to the terms and conditions
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn--green" style="width: 100%; justify-content: center; margin-top: 8px; height: 56px; font-size: 20px;">
                        PLACE ORDER
                    </button>
                </div>
            </div>
            
        </form>
    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>