<?php
// pages/admin/orders.php - Order Fulfillment & Management

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

// Authentication & Role Check
if (!isLoggedIn()) {
    header('Location: ../auth/login.php?error=' . urlencode('Please log in to manage orders.'));
    exit;
}

$role = getUserRole();
if ($role !== 'hq_admin' && $role !== 'branch_manager') {
    header('Location: ../../index.php?error=' . urlencode('You do not have permission to view this page.'));
    exit;
}

$isAdmin = ($role === 'hq_admin');
$branchId = (int)($_SESSION['branch_id'] ?? 0);
$pdo = getConnection();

$message = '';
$error = '';

// Handle Status & Tracking Number Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $trackingNumber = trim($_POST['tracking_number'] ?? '');
    $allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled', 'returned'];

    if ($orderId > 0 && in_array($newStatus, $allowedStatuses)) {
        try {
            // Verify branch access if not HQ Admin
            if (!$isAdmin) {
                $checkStmt = $pdo->prepare("SELECT branch_id FROM orders WHERE id = ?");
                $checkStmt->execute([$orderId]);
                $orderBranch = $checkStmt->fetchColumn();
                if ((int)$orderBranch !== $branchId) {
                    throw new Exception('Permission denied: Order does not belong to your branch.');
                }
            }

            if ($newStatus === 'cancelled') {
                $cancelResult = cancelOrder($pdo, $orderId, null, 'Cancelled by admin (' . ($_SESSION['user_name'] ?? 'Admin') . ')');
                if (!$cancelResult['success']) {
                    throw new Exception($cancelResult['message']);
                }
                if (!empty($trackingNumber)) {
                    $stmt = $pdo->prepare("UPDATE orders SET tracking_number = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$trackingNumber, $orderId]);
                }
            } else {
                if (!empty($trackingNumber)) {
                    $stmt = $pdo->prepare("UPDATE orders SET status = ?, tracking_number = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$newStatus, $trackingNumber, $orderId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$newStatus, $orderId]);
                }
            }

            $message = "Order #$orderId status updated to " . ucfirst($newStatus) . " successfully.";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'Invalid order or status value provided.';
    }
}

// Search & Filter Parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$filterBranch = (int)($_GET['branch'] ?? 0);
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

// Base Query
$sql = "
    SELECT 
        o.id AS order_id,
        o.order_date,
        o.total_amount,
        o.status,
        o.payment_method,
        o.shipping_address,
        o.tracking_number,
        o.notes,
        u.full_name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,
        b.id AS branch_id,
        b.name AS branch_name,
        COUNT(oi.id) AS total_items
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN branches b ON o.branch_id = b.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE 1=1
";

$params = [];

if (!$isAdmin) {
    $sql .= " AND o.branch_id = ?";
    $params[] = $branchId;
} elseif ($filterBranch > 0) {
    $sql .= " AND o.branch_id = ?";
    $params[] = $filterBranch;
}

if (!empty($statusFilter)) {
    $sql .= " AND o.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR o.id = ? OR o.tracking_number LIKE ?)";
    $like = "%$search%";
    $orderSearchId = is_numeric($search) ? (int)$search : 0;
    $params[] = $like;
    $params[] = $like;
    $params[] = $orderSearchId;
    $params[] = $like;
}

if (!empty($dateFrom)) {
    $sql .= " AND DATE(o.order_date) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $sql .= " AND DATE(o.order_date) <= ?";
    $params[] = $dateTo;
}

$sql .= " GROUP BY o.id ORDER BY o.order_date DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Fetch items for displayed orders
$orderItemsMap = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'order_id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemsStmt = $pdo->prepare("
        SELECT 
            oi.order_id,
            oi.price_at_sale,
            oi.quantity,
            p.name AS product_name,
            p.sku,
            i.serial_number,
            b.name AS stock_branch
        FROM order_items oi
        LEFT JOIN inventory i ON oi.inventory_id = i.id
        LEFT JOIN products p ON i.product_id = p.id
        LEFT JOIN branches b ON i.branch_id = b.id
        WHERE oi.order_id IN ($placeholders)
    ");
    $itemsStmt->execute($orderIds);
    while ($row = $itemsStmt->fetch()) {
        $orderItemsMap[$row['order_id']][] = $row;
    }
}

$allBranches = $isAdmin ? getBranches() : [];

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 70vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <div>
                <a href="dashboard.php" style="color: var(--gray-dark); font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                    &larr; Back to Dashboard
                </a>
                <h1 style="font-family: var(--font-heading); font-size: 38px; text-transform: uppercase; margin: 0;">
                    Order Management
                </h1>
                <p style="color: var(--gray-dark); font-size: 16px; margin-top: 4px;">
                    <?= $isAdmin ? 'Global Orders across all branches' : 'Branch Orders for ' . htmlspecialchars($_SESSION['branch_name'] ?? 'Your Branch') ?>
                </p>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <a href="dashboard.php" class="btn btn--outline btn--small" style="height: 38px; padding: 0 16px;">
                    📊 Dashboard
                </a>
                <a href="inventory.php" class="btn btn--outline btn--small" style="height: 38px; padding: 0 16px;">
                    📦 Inventory
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 14px 18px; border-radius: var(--radius); margin-bottom: 20px; border-left: 4px solid var(--green); font-weight: 500;">
                ✓ <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 14px 18px; border-radius: var(--radius); margin-bottom: 20px; border-left: 4px solid #d32f2f; font-weight: 500;">
                ✕ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 20px; box-shadow: var(--shadow); margin-bottom: 24px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px; gap: 12px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; color: var(--gray-dark);">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Order #, Customer, Email..." 
                           style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px;">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; color: var(--gray-dark);">Status</label>
                    <select name="status" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px; background: #fff;">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="returned" <?= $statusFilter === 'returned' ? 'selected' : '' ?>>Returned</option>
                    </select>
                </div>

                <?php if ($isAdmin): ?>
                <div>
                    <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; color: var(--gray-dark);">Branch</label>
                    <select name="branch" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px; background: #fff;">
                        <option value="0">All Branches</option>
                        <?php foreach ($allBranches as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div>
                    <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; color: var(--gray-dark);">Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" 
                           style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px;">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; color: var(--gray-dark);">Date To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" 
                           style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px;">
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn--green" style="height: 38px; font-size: 14px; padding: 0 16px; flex: 1;">Filter</button>
                    <a href="orders.php" class="btn btn--outline" style="height: 38px; font-size: 14px; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center;">✕</a>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <?php if (empty($orders)): ?>
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 50px 20px; text-align: center; box-shadow: var(--shadow);">
                <p style="font-size: 18px; color: var(--gray-dark); margin-bottom: 12px;">No orders found matching your criteria.</p>
                <a href="orders.php" class="btn btn--small btn--outline">Reset Filters</a>
            </div>
        <?php else: ?>
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                        <thead style="background: var(--dark); color: var(--light); text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">
                            <tr>
                                <th style="padding: 14px 16px;">Order ID</th>
                                <th style="padding: 14px 16px;">Date</th>
                                <th style="padding: 14px 16px;">Customer</th>
                                <th style="padding: 14px 16px;">Branch</th>
                                <th style="padding: 14px 16px;">Total</th>
                                <th style="padding: 14px 16px;">Status</th>
                                <th style="padding: 14px 16px;">Tracking</th>
                                <th style="padding: 14px 16px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $ord): 
                                $statusColor = getOrderStatusColor($ord['status']);
                                $items = $orderItemsMap[$ord['order_id']] ?? [];
                            ?>
                                <tr style="border-bottom: 1px solid var(--gray); transition: background 0.15s ease;" onmouseover="this.style.background='#fdfdfd'" onmouseout="this.style.background='white'">
                                    <td style="padding: 14px 16px; font-weight: 700; font-family: var(--font-heading);">
                                        #<?= str_pad($ord['order_id'], 5, '0', STR_PAD_LEFT) ?>
                                    </td>
                                    <td style="padding: 14px 16px; color: var(--gray-dark); white-space: nowrap;">
                                        <?= date('M d, Y', strtotime($ord['order_date'])) ?><br>
                                        <span style="font-size: 11px;"><?= date('h:i A', strtotime($ord['order_date'])) ?></span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <strong><?= htmlspecialchars($ord['customer_name']) ?></strong><br>
                                        <span style="font-size: 12px; color: var(--gray-dark);"><?= htmlspecialchars($ord['customer_email']) ?></span>
                                    </td>
                                    <td style="padding: 14px 16px; font-size: 13px;">
                                        <?= htmlspecialchars($ord['branch_name'] ?? 'Multi-Branch') ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-weight: 700; color: var(--dark); font-family: var(--font-heading);">
                                        ₱ <?= number_format($ord['total_amount'], 2) ?>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; background: <?= $statusColor ?>22; color: <?= $statusColor ?>; border: 1px solid <?= $statusColor ?>;">
                                            <?= htmlspecialchars(getOrderStatusLabel($ord['status'])) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px; font-size: 13px; color: var(--gray-dark);">
                                        <?= $ord['tracking_number'] ? '<code style="background: var(--light); padding: 2px 6px; border-radius: 3px;">' . htmlspecialchars($ord['tracking_number']) . '</code>' : '<span style="color:#bbb;">—</span>' ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: right;">
                                        <button type="button" class="btn btn--small btn--outline" 
                                                onclick="toggleOrderModal(<?= $ord['order_id'] ?>)" 
                                                style="height: 32px; font-size: 12px; padding: 0 12px;">
                                            Manage ▾
                                        </button>
                                    </td>
                                </tr>

                                <!-- Expandable Manage & Detail Row -->
                                <tr id="order-detail-<?= $ord['order_id'] ?>" style="display: none; background: #fafafa; border-bottom: 2px solid var(--gray);">
                                    <td colspan="8" style="padding: 20px 24px;">
                                        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px;">
                                            
                                            <!-- Items & Shipping Info -->
                                            <div>
                                                <h4 style="font-family: var(--font-heading); font-size: 16px; text-transform: uppercase; margin-bottom: 8px;">Order Items (<?= count($items) ?>)</h4>
                                                <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 12px; margin-bottom: 12px;">
                                                    <?php foreach ($items as $it): ?>
                                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                                                            <div>
                                                                <strong style="font-size: 13px;"><?= htmlspecialchars($it['product_name']) ?></strong>
                                                                <div style="font-size: 11px; color: var(--gray-dark);">
                                                                    SKU: <?= htmlspecialchars($it['sku'] ?? 'N/A') ?> 
                                                                    <?= $it['serial_number'] ? '| S/N: ' . htmlspecialchars($it['serial_number']) : '' ?>
                                                                    <?= $it['stock_branch'] ? '| Branch: ' . htmlspecialchars($it['stock_branch']) : '' ?>
                                                                </div>
                                                            </div>
                                                            <div style="text-align: right; font-size: 13px;">
                                                                <span>x<?= $it['quantity'] ?></span> &nbsp;
                                                                <strong>₱ <?= number_format($it['price_at_sale'] * $it['quantity'], 2) ?></strong>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <p style="font-size: 13px; color: var(--gray-dark); margin: 4px 0;">
                                                    <strong>Shipping Address:</strong> <?= htmlspecialchars($ord['shipping_address']) ?>
                                                </p>
                                                <p style="font-size: 13px; color: var(--gray-dark); margin: 4px 0;">
                                                    <strong>Payment Method:</strong> <?= htmlspecialchars(strtoupper($ord['payment_method'] ?? 'COD')) ?>
                                                </p>
                                                <?php if (!empty($ord['notes'])): ?>
                                                    <p style="font-size: 13px; color: var(--gray-dark); margin: 4px 0;">
                                                        <strong>Notes:</strong> <?= htmlspecialchars($ord['notes']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Status Update Form -->
                                            <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 16px;">
                                                <h4 style="font-family: var(--font-heading); font-size: 16px; text-transform: uppercase; margin-bottom: 12px;">Update Status & Fulfillment</h4>
                                                
                                                <form method="POST" action="">
                                                    <input type="hidden" name="action" value="update_order">
                                                    <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">

                                                    <div style="margin-bottom: 12px;">
                                                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Lifecycle Status</label>
                                                        <select name="status" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px; background: #fff;">
                                                            <option value="pending" <?= $ord['status'] === 'pending' ? 'selected' : '' ?>>Pending Payment / Confirmation</option>
                                                            <option value="confirmed" <?= $ord['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed (Preparing for Dispatch)</option>
                                                            <option value="shipped" <?= $ord['status'] === 'shipped' ? 'selected' : '' ?>>Shipped / In-Transit</option>
                                                            <option value="delivered" <?= $ord['status'] === 'delivered' ? 'selected' : '' ?>>Delivered / Picked Up</option>
                                                            <option value="cancelled" <?= $ord['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                            <option value="returned" <?= $ord['status'] === 'returned' ? 'selected' : '' ?>>Returned</option>
                                                        </select>
                                                    </div>

                                                    <div style="margin-bottom: 16px;">
                                                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Tracking Number / Delivery Ref</label>
                                                        <input type="text" name="tracking_number" value="<?= htmlspecialchars($ord['tracking_number'] ?? '') ?>" placeholder="e.g. JNT-982347102"
                                                               style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px;">
                                                    </div>

                                                    <button type="submit" class="btn btn--green" style="width: 100%; height: 38px; font-size: 14px;">
                                                        Save Order Changes
                                                    </button>
                                                </form>
                                            </div>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
function toggleOrderModal(orderId) {
    const row = document.getElementById('order-detail-' + orderId);
    if (row.style.display === 'none' || row.style.display === '') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>
