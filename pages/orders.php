<?php
// pages/orders.php - SYNCRO LAB Order History

require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    redirectWithMessage('auth/login.php', 'error', 'Please log in to view your orders.');
}

$pdo = getConnection();
$userId = $_SESSION['user_id'];

// Get user's orders with details
$sql = "
    SELECT 
        o.id AS order_id,
        o.order_date,
        o.total_amount,
        o.status,
        o.payment_method,
        b.name AS branch_name,
        COUNT(oi.id) AS item_count,
        GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') AS product_names
    FROM orders o
    LEFT JOIN branches b ON o.branch_id = b.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN inventory i ON oi.inventory_id = i.id
    LEFT JOIN products p ON i.product_id = p.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.order_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 60px 0; color: var(--dark); min-height: 60vh;">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            Your Orders
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 40px;">
            View your complete order history.
        </p>

        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 60px 0;">
                <p style="font-size: 22px; color: var(--gray-dark);">
                    You haven't placed any orders yet.
                </p>
                <a href="shop.php" class="btn btn--green" style="margin-top: 20px;">Start Shopping</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <?php foreach ($orders as $order): ?>
                    <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 24px 32px; box-shadow: var(--shadow);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                            <div>
                                <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 4px;">
                                    Order #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?>
                                </h3>
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    <?= date('F d, Y', strtotime($order['order_date'])) ?>
                                </p>
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    <?= htmlspecialchars($order['branch_name'] ?? 'Unknown Branch') ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <span style="display: inline-block; padding: 4px 16px; border-radius: 20px; font-size: 14px; font-weight: 700; background: <?= getOrderStatusColor($order['status']) ?>; color: #fff;">
                                    <?= getOrderStatusLabel($order['status']) ?>
                                </span>
                                <p style="font-family: var(--font-heading); font-size: 24px; margin-top: 8px;">
                                    ₱ <?= number_format($order['total_amount'], 2) ?>
                                </p>
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    <?= $order['item_count'] ?> item(s) · <?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?>
                                </p>
                            </div>
                        </div>
                        <?php if ($order['product_names']): ?>
                            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray);">
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    <strong>Items:</strong> <?= htmlspecialchars($order['product_names']) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p style="margin-top: 32px;">
            <a href="<?= BASE_URL ?>/index.php" style="color: var(--green); font-weight: 700;">&larr; Back to Home</a>
        </p>
    </div>
</div>

<?php
// Helper functions for order status colors
function getOrderStatusColor($status) {
    $colors = [
        'pending' => '#f0ad4e',    // yellow
        'confirmed' => '#5bc0de',   // blue
        'shipped' => '#0275d8',     // dark blue
        'delivered' => '#5cb85c',   // green
        'cancelled' => '#d9534f',   // red
        'returned' => '#777777'     // gray
    ];
    return $colors[$status] ?? '#777777';
}

include __DIR__ . '/../src/Views/layouts/footer.php';
?>