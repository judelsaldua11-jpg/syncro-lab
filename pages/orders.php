<?php
// pages/orders.php - SYNCRO LAB Order History (Enhanced)

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Location: /syncro lab/pages/auth/login.php?error=Please log in to view your orders.');
    exit;
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
        o.shipping_address,
        o.tracking_number,
        b.name AS branch_name,
        b.address AS branch_address,
        COUNT(oi.id) AS item_count,
        GROUP_CONCAT(DISTINCT CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') AS product_names
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

// Get detailed order items for each order (for the modal/dropdown)
$orderDetails = [];
foreach ($orders as $order) {
    $stmt = $pdo->prepare("
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
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['order_id']]);
    $orderDetails[$order['order_id']] = $stmt->fetchAll();
}

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<style>
    .order-timeline {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 12px 0;
        flex-wrap: wrap;
    }
    .order-timeline .step {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        color: var(--gray-dark);
    }
    .order-timeline .step.active {
        color: var(--green);
        font-weight: 700;
    }
    .order-timeline .step.completed {
        color: var(--green);
    }
    .order-timeline .line {
        width: 20px;
        height: 2px;
        background: var(--gray);
    }
    .order-timeline .line.completed {
        background: var(--green);
    }
    
    .order-items-toggle {
        cursor: pointer;
        color: var(--green);
        font-weight: 600;
        font-size: 14px;
        transition: color 0.2s ease;
    }
    .order-items-toggle:hover {
        color: #759616;
        text-decoration: underline;
    }
    .order-items-detail {
        display: none;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--gray);
    }
    .order-items-detail.show {
        display: block;
    }
    
    .order-product-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 6px 0;
        border-bottom: 1px solid rgba(159, 164, 168, 0.2);
    }
    .order-product-item:last-child {
        border-bottom: none;
    }
    .order-product-item img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: var(--radius);
    }
    
    .reorder-btn {
        background: none;
        border: 1px solid var(--gray);
        border-radius: var(--radius);
        padding: 4px 12px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        color: var(--dark);
    }
    .reorder-btn:hover {
        background: var(--green);
        color: var(--dark);
        border-color: var(--green);
    }
</style>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1100px; margin: 0 auto; padding: 0 40px;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 4px;">
                    My Orders
                </h1>
                <p style="color: var(--gray-dark); font-size: 18px;">
                    View your complete order history.
                </p>
            </div>
            <?php if (!empty($orders)): ?>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <span style="color: var(--gray-dark); font-size: 14px;">
                        Total orders: <?= count($orders) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 60px 0; background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); margin-top: 32px;">
                <p style="font-size: 22px; color: var(--gray-dark);">
                    You haven't placed any orders yet.
                </p>
                <a href="/syncro lab/pages/shop.php" class="btn btn--green" style="margin-top: 20px;">Start Shopping</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 24px; margin-top: 32px;">
                <?php foreach ($orders as $order): 
                    $orderItems = $orderDetails[$order['order_id']] ?? [];
                    $statusStep = [
                        'pending' => 0,
                        'confirmed' => 1,
                        'shipped' => 2,
                        'delivered' => 3,
                        'cancelled' => -1,
                        'returned' => -2
                    ];
                    $currentStep = $statusStep[$order['status']] ?? 0;
                    $isCancelled = $order['status'] === 'cancelled' || $order['status'] === 'returned';
                ?>
                    <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 24px 32px; box-shadow: var(--shadow); border-left: 4px solid <?= getOrderStatusColor($order['status']) ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                                    <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 4px;">
                                        Order #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?>
                                    </h3>
                                    <span style="display: inline-block; padding: 4px 16px; border-radius: 20px; font-size: 14px; font-weight: 700; background: <?= getOrderStatusColor($order['status']) ?>; color: #fff;">
                                        <?= getOrderStatusLabel($order['status']) ?>
                                    </span>
                                </div>
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    📅 <?= date('F d, Y', strtotime($order['order_date'])) ?>
                                </p>
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    📍 <?= htmlspecialchars($order['branch_name'] ?? 'Branch not assigned') ?>
                                </p>
                                <?php if ($order['shipping_address']): ?>
                                    <p style="color: var(--gray-dark); font-size: 14px;">
                                        📦 <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($order['tracking_number']): ?>
                                    <p style="color: var(--gray-dark); font-size: 14px;">
                                        🔢 Tracking: <strong><?= htmlspecialchars($order['tracking_number']) ?></strong>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-family: var(--font-heading); font-size: 28px; color: var(--dark);">
                                    ₱ <?= number_format($order['total_amount'], 2) ?>
                                </p>
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    <?= $order['item_count'] ?> item(s) · <?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?>
                                </p>
                            </div>
                        </div>

                        <!-- Product Names (Quick Preview) -->
                        <?php if ($order['product_names']): ?>
                            <div style="margin: 12px 0; padding: 12px 16px; background: var(--light); border-radius: var(--radius);">
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    <strong>Items:</strong> <?= htmlspecialchars($order['product_names']) ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <!-- Order Timeline -->
                        <?php if (!$isCancelled): ?>
                            <div class="order-timeline">
                                <?php
                                $steps = ['Order Placed', 'Confirmed', 'Shipped', 'Delivered'];
                                foreach ($steps as $index => $step):
                                    $isActive = $currentStep >= $index;
                                    $isCurrent = $currentStep === $index;
                                ?>
                                    <?php if ($index > 0): ?>
                                        <div class="line <?= $isActive ? 'completed' : '' ?>"></div>
                                    <?php endif; ?>
                                    <div class="step <?= $isActive ? ($isCurrent ? 'active' : 'completed') : '' ?>">
                                        <?php if ($isActive): ?>
                                            ✅
                                        <?php else: ?>
                                            ⏳
                                        <?php endif; ?>
                                        <?= $step ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray);">
                            <button class="order-items-toggle" data-order-id="<?= $order['order_id'] ?>" onclick="toggleOrderItems(this)">
                                📋 View Items
                            </button>
                            <button class="reorder-btn" onclick="reorder('<?= $order['order_id'] ?>')">
                                🔄 Reorder
                            </button>
                            <a href="/syncro lab/pages/order-success.php?id=<?= $order['order_id'] ?>" style="color: var(--gray-dark); font-size: 14px; text-decoration: none; padding: 4px 12px; border: 1px solid var(--gray); border-radius: var(--radius);">
                                📄 View Order
                            </a>
                        </div>

                        <!-- Order Items Detail (Toggled) -->
                        <div class="order-items-detail" id="order-items-<?= $order['order_id'] ?>">
                            <h4 style="font-family: var(--font-heading); font-size: 16px; margin-bottom: 12px;">Order Details</h4>
                            <?php if (empty($orderItems)): ?>
                                <p style="color: var(--gray-dark); font-size: 14px;">No items found.</p>
                            <?php else: ?>
                                <?php foreach ($orderItems as $item): ?>
                                    <div class="order-product-item">
                                        <img src="<?= !empty($item['product_image']) ? '/syncro lab/' . htmlspecialchars($item['product_image']) : '/syncro lab/assets/images/placeholder.png' ?>" 
                                             alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>">
                                        <div style="flex: 1;">
                                            <p style="font-weight: 700; font-size: 15px;"><?= htmlspecialchars($item['product_name'] ?? 'Unknown Product') ?></p>
                                            <p style="color: var(--gray-dark); font-size: 12px;">
                                                SKU: <?= htmlspecialchars($item['sku'] ?? 'N/A') ?>
                                                <?php if ($item['serial_number']): ?>
                                                    · SN: <?= htmlspecialchars($item['serial_number']) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div style="text-align: right;">
                                            <p style="font-weight: 700; font-size: 16px;">₱ <?= number_format($item['price_at_sale'] * $item['quantity'], 2) ?></p>
                                            <p style="color: var(--gray-dark); font-size: 12px;">Qty: <?= $item['quantity'] ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p style="margin-top: 32px;">
            <a href="/syncro lab/index.php" style="color: var(--green); font-weight: 700;">&larr; Back to Home</a>
        </p>
    </div>
</div>

<script>
// Toggle order items visibility
function toggleOrderItems(btn) {
    const orderId = btn.dataset.orderId;
    const detailDiv = document.getElementById('order-items-' + orderId);
    if (detailDiv.classList.contains('show')) {
        detailDiv.classList.remove('show');
        btn.textContent = '📋 View Items';
    } else {
        detailDiv.classList.add('show');
        btn.textContent = '📋 Hide Items';
    }
}

// Reorder functionality
function reorder(orderId) {
    if (!confirm('Add all items from this order to your cart?')) return;
    
    fetch('/syncro lab/pages/reorder.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'order_id=' + encodeURIComponent(orderId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/syncro lab/pages/cart.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error reordering items. Please try again.');
        console.error('Reorder error:', error);
    });
}
</script>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>