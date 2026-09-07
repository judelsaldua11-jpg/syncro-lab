<?php
// pages/order-success.php - SYNCRO LAB Order Confirmation

require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Get order ID from URL
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId) {
    redirect('index.php');
}

$pdo = getConnection();

// Get order details with branch info
$sql = "
    SELECT 
        o.id AS order_id,
        o.order_date,
        o.total_amount,
        o.status,
        o.payment_method,
        o.shipping_address,
        b.name AS branch_name,
        b.address AS branch_address
    FROM orders o
    LEFT JOIN branches b ON o.branch_id = b.id
    WHERE o.id = :id
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $orderId, PDO::PARAM_INT);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('index.php');
}

// Get order items
$sql = "
    SELECT 
        oi.price_at_sale,
        oi.quantity,
        p.name AS product_name,
        p.sku,
        i.serial_number,
        pi.file_path AS product_image
    FROM order_items oi
    LEFT JOIN inventory i ON oi.inventory_id = i.id
    LEFT JOIN products p ON i.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE oi.order_id = :order_id
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
$stmt->execute();
$orderItems = $stmt->fetchAll();

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 60px 0; color: var(--dark); min-height: 60vh;">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Success Message -->
        <div style="text-align: center; margin-bottom: 40px;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; background: var(--green); border-radius: 50%; margin-bottom: 16px;">
                <span style="font-size: 40px; color: var(--dark);">✓</span>
            </div>
            <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; color: var(--green);">
                Order Confirmed!
            </h1>
            <p style="color: var(--gray-dark); font-size: 20px;">
                Thank you for your order. We'll notify you when it's ready.
            </p>
        </div>

        <!-- Order Details -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; max-width: 900px; margin: 0 auto;">
            
            <!-- Order Summary Card -->
            <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow); grid-column: 1 / -1;">
                <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;">
                    Order #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?>
                </h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Date</p>
                        <p style="font-weight: 700;"><?= date('F d, Y', strtotime($order['order_date'])) ?></p>
                    </div>
                    <div>
                        <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Status</p>
                        <span style="display: inline-block; padding: 4px 16px; border-radius: 20px; font-size: 14px; font-weight: 700; background: <?= getOrderStatusColor($order['status']) ?>; color: #fff;">
                            <?= getOrderStatusLabel($order['status']) ?>
                        </span>
                    </div>
                    <div>
                        <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Branch</p>
                        <p style="font-weight: 700;"><?= htmlspecialchars($order['branch_name'] ?? 'Not assigned') ?></p>
                        <p style="color: var(--gray-dark); font-size: 14px;"><?= htmlspecialchars($order['branch_address'] ?? '') ?></p>
                    </div>
                    <div>
                        <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Payment Method</p>
                        <p style="font-weight: 700;"><?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?></p>
                    </div>
                </div>
                <?php if ($order['shipping_address']): ?>
                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray);">
                        <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Shipping Address</p>
                        <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Order Items -->
            <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow); grid-column: 1 / -1;">
                <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;">
                    Items
                </h3>
                <?php if (empty($orderItems)): ?>
                    <p style="color: var(--gray-dark);">No items found.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="border-bottom: 2px solid var(--gray);">
                            <tr>
                                <th style="text-align: left; padding: 8px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-dark);">Product</th>
                                <th style="text-align: center; padding: 8px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-dark);">Qty</th>
                                <th style="text-align: right; padding: 8px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-dark);">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr style="border-bottom: 1px solid rgba(159, 164, 168, 0.3);">
                                    <td style="padding: 12px 0;">
                                        <span style="font-weight: 700;"><?= htmlspecialchars($item['product_name'] ?? 'Unknown Product') ?></span>
                                        <?php if ($item['serial_number']): ?>
                                            <br><span style="color: var(--gray-dark); font-size: 12px;">SN: <?= htmlspecialchars($item['serial_number']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center; padding: 12px 0;"><?= $item['quantity'] ?></td>
                                    <td style="text-align: right; padding: 12px 0;">₱ <?= number_format($item['price_at_sale'] * $item['quantity'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" style="text-align: right; padding: 16px 0; font-weight: 700;">Total</td>
                                <td style="text-align: right; padding: 16px 0; font-family: var(--font-heading); font-size: 24px; color: var(--green);">
                                    ₱ <?= number_format($order['total_amount'], 2) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div style="grid-column: 1 / -1; display: flex; gap: 16px; justify-content: center; margin-top: 16px;">
                <a href="shop.php" class="btn btn--green">CONTINUE SHOPPING</a>
                <a href="orders.php" class="btn btn--outline">VIEW MY ORDERS</a>
            </div>

        </div>
    </div>
</div>

<?php
// Helper function for order status colors (copied from orders.php for consistency)
function getOrderStatusColor($status) {
    $colors = [
        'pending' => '#f0ad4e',
        'confirmed' => '#5bc0de',
        'shipped' => '#0275d8',
        'delivered' => '#5cb85c',
        'cancelled' => '#d9534f',
        'returned' => '#777777'
    ];
    return $colors[$status] ?? '#777777';
}

include __DIR__ . '/../src/Views/layouts/footer.php';
?>