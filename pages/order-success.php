<?php
// pages/order-success.php - SYNCRO LAB Order Confirmation

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$orderId) {
    header('Location: /syncro lab/index.php');
    exit;
}

$pdo = getConnection();

$sql = "
    SELECT
        o.id AS order_id,
        o.order_date,
        o.total_amount,
        o.subtotal,
        o.discount_amount,
        o.status,
        o.payment_method,
        o.payment_reference,
        o.payment_details,
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
    header('Location: /syncro lab/index.php');
    exit;
}

$sql = "
    SELECT oi.price_at_sale, oi.quantity, p.name AS product_name, p.sku,
           i.serial_number, pi.file_path AS product_image
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

<div style="background: var(--light); padding-bottom: 40px;">
    <div style="padding: 60px 0 40px; color: var(--dark); min-height: 60vh;">
        <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">

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

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; max-width: 900px; margin: 0 auto;">

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
                        <?php if (!empty($order['payment_reference'])): ?>
                        <div>
                            <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Payment Reference</p>
                            <p style="font-weight: 700; font-family: monospace;"><?= htmlspecialchars($order['payment_reference']) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($order['payment_details'])): ?>
                        <div>
                            <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Payment Info</p>
                            <p style="font-weight: 700;"><?= htmlspecialchars($order['payment_details']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($order['shipping_address']): ?>
                        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray);">
                            <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Shipping Address</p>
                            <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

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
                                    <td colspan="2" style="text-align: right; padding: 8px 0; font-size: 14px; color: var(--gray-dark);">Subtotal</td>
                                    <td style="text-align: right; padding: 8px 0; font-size: 16px;">
                                        ₱ <?= number_format($order['subtotal'] ?? $order['total_amount'], 2) ?>
                                    </td>
                                </tr>
                                <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                                    <tr>
                                        <td colspan="2" style="text-align: right; padding: 8px 0; font-size: 14px; color: #2e7d32; font-weight: 700;">
                                            💎 Member Discount (10%)
                                        </td>
                                        <td style="text-align: right; padding: 8px 0; font-size: 16px; color: #2e7d32; font-weight: 700;">
                                            − ₱ <?= number_format($order['discount_amount'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="2" style="text-align: right; padding: 12px 0; font-weight: 700;">Total</td>
                                    <td style="text-align: right; padding: 12px 0; font-family: var(--font-heading); font-size: 24px; color: var(--green);">
                                        ₱ <?= number_format($order['total_amount'], 2) ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>

                <div style="grid-column: 1 / -1; display: flex; gap: 16px; justify-content: center; margin-top: 16px; flex-wrap: wrap; align-items: center;">
                    <a href="/syncro lab/pages/shop.php" class="btn btn--green">CONTINUE SHOPPING</a>
                    <a href="/syncro lab/pages/orders.php" class="btn btn--outline">VIEW MY ORDERS</a>
                    <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                        <button type="button" onclick="cancelSuccessOrder(<?= (int)$order['order_id'] ?>)"
                                style="background: none; border: 1px solid #d9534f; color: #d9534f; padding: 12px 24px; border-radius: var(--radius); font-weight: 700; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="this.style.background='#d9534f'; this.style.color='#fff';"
                                onmouseout="this.style.background='none'; this.style.color='#d9534f';">
                            CANCEL ORDER
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function cancelSuccessOrder(orderId) {
    const confirmed = await window.SyncroModal.confirm(
        `Are you sure you want to cancel <strong>Order #${String(orderId).padStart(6, '0')}</strong>?<br><br>All allocated items will immediately return to available branch inventory.<br>If you paid online, a <strong>refund request</strong> will be submitted for admin review.`,
        'CANCEL ORDER', 'danger', 'CONFIRM CANCELLATION', 'KEEP ORDER'
    );
    if (!confirmed) return;

    const reason = await window.SyncroModal.prompt(
        'Optional: Please let us know the reason for cancellation:',
        'e.g., Ordered by mistake, wrong delivery address...',
        'REASON FOR CANCELLATION'
    );
    if (reason === null) return;

    fetch('/syncro lab/pages/cancel-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'order_id=' + encodeURIComponent(orderId) + '&reason=' + encodeURIComponent(reason)
    })
    .then(r => r.json())
    .then(async data => {
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
            window.location.href = '/syncro lab/pages/orders.php';
        } else {
            await window.SyncroModal.alert(data.message || 'Unable to cancel order.', 'CANCELLATION FAILED', 'danger');
        }
    })
    .catch(async err => {
        await window.SyncroModal.alert('Error processing order cancellation.', 'SYSTEM ERROR', 'danger');
        console.error(err);
    });
}
</script>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>