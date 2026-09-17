<?php
// pages/orders.php — Customer Order History

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (!isLoggedIn()) {
    header('Location: /syncro lab/pages/auth/login.php?error=Please log in to view your orders.');
    exit;
}

$pdo    = getConnection();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT o.id AS order_id, o.order_date, o.total_amount, o.subtotal, o.discount_amount,
           o.status, o.payment_method, o.shipping_address, o.tracking_number,
           b.name AS branch_name,
           COUNT(oi.id) AS item_count,
           GROUP_CONCAT(DISTINCT CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') AS product_names
    FROM orders o
    LEFT JOIN branches    b  ON o.branch_id = b.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN inventory   i  ON oi.inventory_id = i.id
    LEFT JOIN products    p  ON i.product_id = p.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.order_date DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

$orderDetails = [];
if ($orders) {
    $ids          = array_column($orders, 'order_id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT oi.order_id, oi.price_at_sale, oi.quantity,
               p.name AS product_name, p.sku,
               i.serial_number,
               pi.file_path AS product_image
        FROM order_items oi
        LEFT JOIN inventory      i  ON oi.inventory_id = i.id
        LEFT JOIN products       p  ON i.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE oi.order_id IN ($placeholders)
    ");
    $stmt->execute($ids);

    foreach ($stmt->fetchAll() as $row) {
        $orderDetails[$row['order_id']][] = $row;
    }
}

$ORDER_STEPS  = ['Order Placed', 'Confirmed', 'Shipped', 'Delivered'];
$STATUS_STEPS = ['pending' => 0, 'confirmed' => 1, 'shipped' => 2, 'delivered' => 3];

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<style>
    .order-timeline { display: flex; align-items: center; gap: 8px; margin: 12px 0; flex-wrap: wrap; }
    .order-timeline .step { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--gray-dark); }
    .order-timeline .step.active    { color: var(--green); font-weight: 700; }
    .order-timeline .step.completed { color: var(--green); }
    .order-timeline .line { width: 20px; height: 2px; background: var(--gray); }
    .order-timeline .line.completed { background: var(--green); }
    .order-items-toggle { cursor: pointer; color: var(--green); font-weight: 600; font-size: 14px; }
    .order-items-toggle:hover { color: #759616; text-decoration: underline; }
    .order-items-detail { display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--gray); }
    .order-items-detail.show { display: block; }
    .order-product-item { display: flex; align-items: center; gap: 12px; padding: 6px 0; border-bottom: 1px solid rgba(159, 164, 168, 0.2); }
    .order-product-item:last-child { border-bottom: none; }
    .order-product-item img { width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius); }
    .reorder-btn { background: none; border: 1px solid var(--gray); border-radius: var(--radius); padding: 4px 12px; font-size: 12px; cursor: pointer; color: var(--dark); }
    .reorder-btn:hover { background: var(--green); color: var(--dark); border-color: var(--green); }
    .cancel-order-btn { background: none; border: 1px solid #d9534f; border-radius: var(--radius); padding: 4px 12px; font-size: 12px; cursor: pointer; color: #d9534f; font-weight: 600; }
    .cancel-order-btn:hover { background: #d9534f; color: #fff; }
    .view-order-btn { color: var(--gray-dark); font-size: 12px; text-decoration: none; padding: 4px 12px; border: 1px solid var(--gray); border-radius: var(--radius); line-height: 1.5; }
    .view-order-btn:hover { background: var(--light); }
</style>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1100px; margin: 0 auto; padding: 0 40px;">

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 4px;">My Orders</h1>
                <p style="color: var(--gray-dark); font-size: 18px;">View your complete order history.</p>
            </div>
            <?php if ($orders): ?>
                <span style="color: var(--gray-dark); font-size: 14px;">Total orders: <?= count($orders) ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 60px 0; background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); margin-top: 32px;">
                <p style="font-size: 22px; color: var(--gray-dark);">You haven't placed any orders yet.</p>
                <a href="/syncro lab/pages/shop.php" class="btn btn--green" style="margin-top: 20px;">Start Shopping</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 24px; margin-top: 32px;">
            <?php foreach ($orders as $order):
                $orderItems   = $orderDetails[$order['order_id']] ?? [];
                $isCancelled  = in_array($order['status'], ['cancelled', 'returned'], true);
                $currentStep  = $STATUS_STEPS[$order['status']] ?? 0;
                $statusColor  = getOrderStatusColor($order['status']);
                $hasDiscount  = ($order['discount_amount'] ?? 0) > 0;
            ?>
                <div style="background: #fff; border: 1px solid var(--gray); border-left: 4px solid <?= $statusColor ?>; border-radius: var(--radius); padding: 24px 32px; box-shadow: var(--shadow);">

                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                                <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 4px;">
                                    Order #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?>
                                </h3>
                                <span style="display: inline-block; padding: 4px 16px; border-radius: 20px; font-size: 14px; font-weight: 700; background: <?= $statusColor ?>; color: #fff;">
                                    <?= getOrderStatusLabel($order['status']) ?>
                                </span>
                                <?php if ($hasDiscount): ?>
                                    <span style="display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7;">
                                        💎 10% OFF
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p style="color: var(--gray-dark); font-size: 14px;">📅 <?= date('F d, Y', strtotime($order['order_date'])) ?></p>
                            <p style="color: var(--gray-dark); font-size: 14px;">📍 <?= htmlspecialchars($order['branch_name'] ?? 'Branch not assigned') ?></p>
                            <?php if ($order['shipping_address']): ?>
                                <p style="color: var(--gray-dark); font-size: 14px;">📦 <?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                            <?php endif; ?>
                            <?php if ($order['tracking_number']): ?>
                                <p style="color: var(--gray-dark); font-size: 14px;">🔢 Tracking: <strong><?= htmlspecialchars($order['tracking_number']) ?></strong></p>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right; min-width: 200px;">
                            <?php if ($hasDiscount): ?>
                                <p style="font-size: 13px; color: var(--gray-dark); text-decoration: line-through; margin: 0;">
                                    ₱ <?= number_format($order['subtotal'], 2) ?>
                                </p>
                                <p style="font-size: 12px; color: #2e7d32; font-weight: 700; margin: 2px 0;">
                                    💎 − ₱ <?= number_format($order['discount_amount'], 2) ?> (10%)
                                </p>
                            <?php endif; ?>
                            <p style="font-family: var(--font-heading); font-size: 28px; color: var(--dark); margin: 0;">
                                ₱ <?= number_format($order['total_amount'], 2) ?>
                            </p>
                            <p style="color: var(--gray-dark); font-size: 14px;">
                                <?= $order['item_count'] ?> item(s) · <?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($order['product_names']): ?>
                        <div style="margin: 12px 0; padding: 12px 16px; background: var(--light); border-radius: var(--radius);">
                            <p style="color: var(--gray-dark); font-size: 14px;">
                                <strong>Items:</strong> <?= htmlspecialchars($order['product_names']) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if (!$isCancelled): ?>
                        <div class="order-timeline">
                            <?php foreach ($ORDER_STEPS as $i => $label):
                                $isActive  = $currentStep >= $i;
                                $isCurrent = $currentStep === $i;
                            ?>
                                <?php if ($i > 0): ?>
                                    <div class="line <?= $isActive ? 'completed' : '' ?>"></div>
                                <?php endif; ?>
                                <div class="step <?= $isActive ? ($isCurrent ? 'active' : 'completed') : '' ?>">
                                    <?= $isActive ? '✅' : '⏳' ?> <?= $label ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray); align-items: center;">
                        <button class="order-items-toggle" data-order-id="<?= $order['order_id'] ?>" onclick="toggleOrderItems(this)">📋 View Items</button>
                        <button class="reorder-btn" onclick="reorder('<?= $order['order_id'] ?>')">🔄 Reorder</button>
                        <a href="/syncro lab/pages/order-success.php?id=<?= $order['order_id'] ?>" class="view-order-btn">📄 View Order</a>
                        <?php if (in_array($order['status'], ['pending', 'confirmed'], true)): ?>
                            <button class="cancel-order-btn" onclick="cancelUserOrder('<?= $order['order_id'] ?>')">✕ Cancel Order</button>
                        <?php endif; ?>
                    </div>

                    <div class="order-items-detail" id="order-items-<?= $order['order_id'] ?>">
                        <h4 style="font-family: var(--font-heading); font-size: 16px; margin-bottom: 12px;">Order Details</h4>
                        <?php if (empty($orderItems)): ?>
                            <p style="color: var(--gray-dark); font-size: 14px;">No items found.</p>
                        <?php else: foreach ($orderItems as $item):
                            $img = !empty($item['product_image'])
                                 ? '/syncro lab/' . htmlspecialchars($item['product_image'])
                                 : '/syncro lab/assets/images/placeholder.png';
                        ?>
                            <div class="order-product-item">
                                <img src="<?= $img ?>" alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>">
                                <div style="flex: 1;">
                                    <p style="font-weight: 700; font-size: 15px;"><?= htmlspecialchars($item['product_name'] ?? 'Unknown Product') ?></p>
                                    <p style="color: var(--gray-dark); font-size: 12px;">
                                        SKU: <?= htmlspecialchars($item['sku'] ?? 'N/A') ?>
                                        <?php if ($item['serial_number']): ?> · SN: <?= htmlspecialchars($item['serial_number']) ?><?php endif; ?>
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <p style="font-weight: 700; font-size: 16px;">₱ <?= number_format($item['price_at_sale'] * $item['quantity'], 2) ?></p>
                                    <p style="color: var(--gray-dark); font-size: 12px;">Qty: <?= $item['quantity'] ?></p>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p style="margin-top: 32px;">
            <a href="/syncro lab/index.php" style="color: var(--green); font-weight: 700;">← Back to Home</a>
        </p>
    </div>
</div>

<script>
function toggleOrderItems(btn) {
    const div = document.getElementById('order-items-' + btn.dataset.orderId);
    const open = div.classList.toggle('show');
    btn.textContent = open ? '📋 Hide Items' : '📋 View Items';
}

async function reorder(orderId) {
    const ok = await window.SyncroModal.confirm(
        'Add all items from this order back into your cart?',
        'REORDER ITEMS', 'info', 'ADD TO CART', 'CANCEL'
    );
    if (!ok) return;
    try {
        const res  = await fetch('/syncro lab/pages/reorder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'order_id=' + encodeURIComponent(orderId)
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = '/syncro lab/pages/cart.php';
        } else {
            await window.SyncroModal.alert(data.message || 'Failed to reorder items.', 'ERROR', 'danger');
        }
    } catch (e) {
        console.error(e);
        await window.SyncroModal.alert('Error reordering items. Please try again.', 'NETWORK ERROR', 'danger');
    }
}

async function cancelUserOrder(orderId) {
    const confirmed = await window.SyncroModal.confirm(
        `Are you sure you want to cancel <strong>Order #${String(orderId).padStart(6, '0')}</strong>?<br><br>
        Reserved products will be returned to stock.<br>
        If you paid online, a <strong>refund request</strong> will be submitted for admin review.`,
        'CANCEL ORDER', 'danger', 'CONFIRM CANCELLATION', 'KEEP ORDER'
    );
    if (!confirmed) return;

    const reason = await window.SyncroModal.prompt(
        'Optional: Please share the reason for cancelling this order:',
        'e.g., Changed mind, ordered wrong item…',
        'REASON FOR CANCELLATION'
    );
    if (reason === null) return;

    try {
        const res  = await fetch('/syncro lab/pages/cancel-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'order_id=' + encodeURIComponent(orderId) + '&reason=' + encodeURIComponent(reason)
        });
        const data = await res.json();

        if (data.success) {
            let html = `<strong>Order #${String(orderId).padStart(6, '0')} has been cancelled.</strong><br><br>`;
            if (data.refund_eligible === true) {
                html += `<div style="background:#e8f5e9;border:1px solid #a5d6a7;border-radius:8px;padding:14px 16px;margin:8px 0;text-align:left;">
                    <div style="font-size:15px;font-weight:700;color:#2e7d32;margin-bottom:6px;">💚 Refund Information</div>
                    <div style="font-size:14px;color:#1b5e20;">${data.refund_message}</div>`;
                if (data.payment_details) html += `<div style="font-size:12px;color:#388e3c;margin-top:4px;">Account: <strong>${data.payment_details}</strong></div>`;
                if (data.payment_ref)     html += `<div style="font-size:12px;color:#388e3c;">Ref: <strong>${data.payment_ref}</strong></div>`;
                if (data.refund_request_id) html += `<div style="font-size:12px;color:#388e3c;margin-top:4px;">🔖 Refund Request <strong>#${data.refund_request_id}</strong> submitted — pending admin review.</div>`;
                html += `</div><p style="font-size:13px;color:#666;margin-top:8px;">Refunds are typically processed within <strong>3–5 business days</strong> after admin approval.</p>`;
            } else if (data.refund_eligible === false) {
                html += `<div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:14px 16px;margin:8px 0;text-align:left;">
                    <div style="font-size:15px;font-weight:700;color:#f57f17;margin-bottom:4px;">ℹ️ No Refund Needed</div>
                    <div style="font-size:13px;color:#795548;">${data.refund_message}</div></div>`;
            } else if (data.refund_request_id) {
                html += `🔖 Refund Request <strong>#${data.refund_request_id}</strong> is pending admin review.`;
            }
            await window.SyncroModal.alert(html, 'ORDER CANCELLED', 'success');
            window.location.reload();
        } else {
            await window.SyncroModal.alert(data.message || 'Unable to cancel order.', 'CANCELLATION FAILED', 'danger');
        }
    } catch (e) {
        console.error(e);
        await window.SyncroModal.alert('Network error. Please try again.', 'SYSTEM ERROR', 'danger');
    }
}
</script>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>