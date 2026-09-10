<?php
// pages/admin/dashboard.php - Admin Dashboard

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php?error=Please log in to access the dashboard.');
    exit;
}

$role = getUserRole();
if ($role !== 'hq_admin' && $role !== 'branch_manager') {
    header('Location: ../profile.php?error=You do not have permission to access the dashboard.');
    exit;
}

$user = getCurrentUser();
$isAdmin = ($role === 'hq_admin');
$branchId = $_SESSION['branch_id'] ?? 0;
$branchName = $_SESSION['branch_name'] ?? 'Your Branch';

$pdo = getConnection();

// Get statistics based on role
$stats = [];

if ($isAdmin) {
    // HQ Admin: Get global stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
    $stats['total_products'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM inventory WHERE status = 'in_stock'");
    $stats['total_stock'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM branches WHERE is_active = 1");
    $stats['total_branches'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $stats['pending_orders'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM service_bookings WHERE status = 'pending'");
    $stats['pending_bookings'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND is_active = 1");
    $stats['total_customers'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'branch_manager' AND is_active = 1");
    $stats['total_managers'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled' AND MONTH(order_date) = MONTH(CURRENT_DATE()) AND YEAR(order_date) = YEAR(CURRENT_DATE())");
    $stats['monthly_revenue'] = $stmt->fetchColumn() ?? 0;

    // Recent Pending Orders for Quick Action
    $stmt = $pdo->query("
        SELECT o.id, o.order_date, o.total_amount, o.status, u.full_name, b.name as branch_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN branches b ON o.branch_id = b.id
        WHERE o.status = 'pending'
        ORDER BY o.order_date DESC LIMIT 5
    ");
    $pendingOrdersList = $stmt->fetchAll();

    // Upcoming Bookings
    $stmt = $pdo->query("
        SELECT sb.id, sb.scheduled_date, sb.service_type, sb.status, u.full_name, b.name as branch_name
        FROM service_bookings sb
        JOIN users u ON sb.user_id = u.id
        JOIN branches b ON sb.branch_id = b.id
        WHERE sb.status IN ('pending', 'confirmed')
        ORDER BY sb.scheduled_date ASC LIMIT 5
    ");
    $upcomingBookingsList = $stmt->fetchAll();
    
} else {
    // Branch Manager: Get branch-specific stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE branch_id = ? AND status = 'in_stock'");
    $stmt->execute([$branchId]);
    $stats['branch_stock'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE branch_id = ? AND status = 'reserved'");
    $stmt->execute([$branchId]);
    $stats['reserved_items'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE branch_id = ? AND status = 'pending'");
    $stmt->execute([$branchId]);
    $stats['pending_orders'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_bookings WHERE branch_id = ? AND status = 'pending'");
    $stmt->execute([$branchId]);
    $stats['pending_bookings'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_bookings WHERE branch_id = ? AND status = 'confirmed'");
    $stmt->execute([$branchId]);
    $stats['confirmed_bookings'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE branch_id = ? AND status != 'cancelled' AND MONTH(order_date) = MONTH(CURRENT_DATE()) AND YEAR(order_date) = YEAR(CURRENT_DATE())");
    $stmt->execute([$branchId]);
    $stats['monthly_revenue'] = $stmt->fetchColumn() ?? 0;

    // Recent Pending Orders for this Branch
    $stmt = $pdo->prepare("
        SELECT o.id, o.order_date, o.total_amount, o.status, u.full_name, b.name as branch_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN branches b ON o.branch_id = b.id
        WHERE o.branch_id = ? AND o.status = 'pending'
        ORDER BY o.order_date DESC LIMIT 5
    ");
    $stmt->execute([$branchId]);
    $pendingOrdersList = $stmt->fetchAll();

    // Upcoming Bookings for this Branch
    $stmt = $pdo->prepare("
        SELECT sb.id, sb.scheduled_date, sb.service_type, sb.status, u.full_name, b.name as branch_name
        FROM service_bookings sb
        JOIN users u ON sb.user_id = u.id
        JOIN branches b ON sb.branch_id = b.id
        WHERE sb.branch_id = ? AND sb.status IN ('pending', 'confirmed')
        ORDER BY sb.scheduled_date ASC LIMIT 5
    ");
    $stmt->execute([$branchId]);
    $upcomingBookingsList = $stmt->fetchAll();
}

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 70vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-family: var(--font-heading); font-size: 42px; text-transform: uppercase; margin: 0 0 6px;">
                    Control Dashboard
                </h1>
                <p style="color: var(--gray-dark); font-size: 16px; margin: 0;">
                    Welcome back, <strong><?= htmlspecialchars($user['full_name']) ?></strong> &bull; 
                    <span style="color: var(--green); font-weight: 700;"><?= $isAdmin ? 'HQ Admin' : htmlspecialchars($branchName) . ' Manager' ?></span>
                </p>
            </div>
            <div>
                <a href="inventory-add.php" class="btn btn--green btn--small" style="height: 38px; padding: 0 16px;">
                    ➕ Stock Item
                </a>
            </div>
        </div>

        <?php if ($isAdmin): ?>
            <!-- ==================== HQ ADMIN DASHBOARD ==================== -->
            
            <!-- Stats Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 32px;">
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 6px;">Monthly Revenue</p>
                    <p style="font-family: var(--font-heading); font-size: 28px; color: var(--green); margin: 0;">₱ <?= number_format($stats['monthly_revenue'], 2) ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 6px;">Pending Orders</p>
                    <p style="font-family: var(--font-heading); font-size: 28px; color: <?= $stats['pending_orders'] > 0 ? '#f0ad4e' : 'var(--dark)' ?>; margin: 0;"><?= number_format($stats['pending_orders']) ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 6px;">Pending Bookings</p>
                    <p style="font-family: var(--font-heading); font-size: 28px; color: <?= $stats['pending_bookings'] > 0 ? '#0275d8' : 'var(--dark)' ?>; margin: 0;"><?= number_format($stats['pending_bookings']) ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 6px;">Available Stock</p>
                    <p style="font-family: var(--font-heading); font-size: 28px; color: var(--dark); margin: 0;"><?= number_format($stats['total_stock']) ?> <span style="font-size: 14px; color: var(--gray-dark); font-family: var(--font-body);">units</span></p>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 6px;">Active Labs</p>
                    <p style="font-family: var(--font-heading); font-size: 28px; color: var(--dark); margin: 0;"><?= $stats['total_branches'] ?> <span style="font-size: 14px; color: var(--gray-dark); font-family: var(--font-body);">locations</span></p>
                </div>
            </div>

            <!-- Quick Action Management Links -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 32px;">
                <a href="orders.php" style="text-decoration: none; background: #fff; padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; color: var(--dark); margin: 0 0 4px;">🛒 Orders</h3>
                    <p style="color: var(--gray-dark); font-size: 13px; margin: 0;">Fulfillment & tracking</p>
                </a>
                <a href="bookings.php" style="text-decoration: none; background: #fff; padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; color: var(--dark); margin: 0 0 4px;">📅 Bookings</h3>
                    <p style="color: var(--gray-dark); font-size: 13px; margin: 0;">Service & fit appointments</p>
                </a>
                <a href="inventory.php" style="text-decoration: none; background: #fff; padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; color: var(--dark); margin: 0 0 4px;">📦 Inventory</h3>
                    <p style="color: var(--gray-dark); font-size: 13px; margin: 0;">Multi-branch stock</p>
                </a>
                <a href="products.php" style="text-decoration: none; background: #fff; padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; color: var(--dark); margin: 0 0 4px;">🏷️ Catalog</h3>
                    <p style="color: var(--gray-dark); font-size: 13px; margin: 0;">Products & prices</p>
                </a>
                <a href="locations.php" style="text-decoration: none; background: #fff; padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; color: var(--dark); margin: 0 0 4px;">📍 Locations</h3>
                    <p style="color: var(--gray-dark); font-size: 13px; margin: 0;">Branch hubs</p>
                </a>
                <a href="users.php" style="text-decoration: none; background: #fff; padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; color: var(--dark); margin: 0 0 4px;">👥 Users</h3>
                    <p style="color: var(--gray-dark); font-size: 13px; margin: 0;">Accounts & roles</p>
                </a>
            </div>

            <!-- Priority Queues Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                
                <!-- Pending Orders Widget -->
                <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 20px; box-shadow: var(--shadow);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                        <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin: 0;">
                            ⚡ Orders Awaiting Dispatch
                        </h3>
                        <a href="orders.php?status=pending" style="color: var(--green); font-size: 12px; font-weight: 700;">View All &rarr;</a>
                    </div>
                    <?php if (empty($pendingOrdersList)): ?>
                        <p style="color: var(--gray-dark); font-size: 13px; padding: 24px 0; text-align: center;">No orders awaiting dispatch. ✅</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($pendingOrdersList as $po): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: var(--light); border-radius: var(--radius); border-left: 3px solid #f0ad4e;">
                                    <div>
                                        <strong style="font-size: 14px;">Order #<?= str_pad($po['id'], 5, '0', STR_PAD_LEFT) ?></strong> &bull; <?= htmlspecialchars($po['full_name']) ?>
                                        <div style="font-size: 11px; color: var(--gray-dark);"><?= htmlspecialchars($po['branch_name'] ?? 'Multi-Branch') ?> &bull; <?= date('M d, h:i A', strtotime($po['order_date'])) ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <strong style="font-size: 14px; color: var(--dark);">₱ <?= number_format($po['total_amount'], 2) ?></strong><br>
                                        <a href="orders.php?search=<?= $po['id'] ?>" class="btn btn--small btn--outline" style="height: 24px; font-size: 11px; padding: 0 8px; margin-top: 4px;">Fulfill</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Upcoming Appointments Widget -->
                <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 20px; box-shadow: var(--shadow);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                        <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin: 0;">
                            🛠️ Upcoming Lab Services
                        </h3>
                        <a href="bookings.php" style="color: var(--green); font-size: 12px; font-weight: 700;">View All &rarr;</a>
                    </div>
                    <?php if (empty($upcomingBookingsList)): ?>
                        <p style="color: var(--gray-dark); font-size: 13px; padding: 24px 0; text-align: center;">No upcoming service bookings. ✅</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($upcomingBookingsList as $ub): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: var(--light); border-radius: var(--radius); border-left: 3px solid <?= $ub['status'] === 'pending' ? '#f0ad4e' : '#0275d8' ?>;">
                                    <div>
                                        <strong style="font-size: 14px;"><?= htmlspecialchars($ub['full_name']) ?></strong> &bull; <span style="font-size: 12px; text-transform: capitalize;"><?= str_replace('_', ' ', $ub['service_type']) ?></span>
                                        <div style="font-size: 11px; color: var(--gray-dark);"><?= htmlspecialchars($ub['branch_name']) ?> &bull; <?= date('M d, Y @ h:i A', strtotime($ub['scheduled_date'])) ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: <?= $ub['status'] === 'pending' ? '#f0ad4e' : '#0275d8' ?>;"><?= ucfirst($ub['status']) ?></span><br>
                                        <a href="bookings.php?status=<?= $ub['status'] ?>" class="btn btn--small btn--outline" style="height: 24px; font-size: 11px; padding: 0 8px; margin-top: 4px;">Manage</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- ============================================ -->
            <!-- INVENTORY ACTIVITY LOG (HQ Admin)            -->
            <!-- ============================================ -->
            <div style="margin-top: 40px; background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 24px; box-shadow: var(--shadow);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
                    <h3 style="font-family: var(--font-heading); font-size: 24px; text-transform: uppercase; margin: 0;">📋 Inventory Activity</h3>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                        <form method="GET" action="" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                            <input type="hidden" name="section" value="activity">
                            <select name="filter_branch" style="height: 36px; padding: 0 12px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px; background: #fff;">
                                <option value="0">All Branches</option>
                                <?php
                                $branches = getBranches();
                                foreach ($branches as $b) {
                                    $selected = (isset($_GET['filter_branch']) && $_GET['filter_branch'] == $b['id']) ? 'selected' : '';
                                    echo "<option value='{$b['id']}' $selected>" . htmlspecialchars($b['name']) . "</option>";
                                }
                                ?>
                            </select>
                            <select name="filter_action" style="height: 36px; padding: 0 12px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px; background: #fff;">
                                <option value="">All Actions</option>
                                <option value="add" <?= (isset($_GET['filter_action']) && $_GET['filter_action'] == 'add') ? 'selected' : '' ?>>Add Stock</option>
                                <option value="update_status" <?= (isset($_GET['filter_action']) && $_GET['filter_action'] == 'update_status') ? 'selected' : '' ?>>Status Change</option>
                            </select>
                            <input type="date" name="filter_date_from" value="<?= isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '' ?>" style="height: 36px; padding: 0 8px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px;">
                            <span style="color: var(--gray-dark);">to</span>
                            <input type="date" name="filter_date_to" value="<?= isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '' ?>" style="height: 36px; padding: 0 8px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px;">
                            <button type="submit" class="btn btn--green btn--small" style="height: 36px; font-size: 14px; padding: 0 16px;">Filter</button>
                            <a href="?section=activity" class="btn btn--outline btn--small" style="height: 36px; font-size: 14px; padding: 0 16px;">Clear</a>
                        </form>
                    </div>
                </div>

                <?php
                // Build query for activity logs
                $filterBranch = isset($_GET['filter_branch']) ? (int)$_GET['filter_branch'] : 0;
                $filterAction = isset($_GET['filter_action']) ? $_GET['filter_action'] : '';
                $filterDateFrom = isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '';
                $filterDateTo = isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '';

                $sql = "
                    SELECT l.*, 
                           u.full_name AS user_name,
                           p.name AS product_name,
                           b.name AS branch_name
                    FROM inventory_logs l
                    LEFT JOIN users u ON l.user_id = u.id
                    LEFT JOIN products p ON l.product_id = p.id
                    LEFT JOIN branches b ON l.branch_id = b.id
                    WHERE 1=1
                ";
                $params = [];

                if ($filterBranch > 0) {
                    $sql .= " AND l.branch_id = ?";
                    $params[] = $filterBranch;
                }
                if (!empty($filterAction)) {
                    $sql .= " AND l.action = ?";
                    $params[] = $filterAction;
                }
                if (!empty($filterDateFrom)) {
                    $sql .= " AND DATE(l.created_at) >= ?";
                    $params[] = $filterDateFrom;
                }
                if (!empty($filterDateTo)) {
                    $sql .= " AND DATE(l.created_at) <= ?";
                    $params[] = $filterDateTo;
                }
                $sql .= " ORDER BY l.created_at DESC LIMIT 50";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $logs = $stmt->fetchAll();
                ?>

                <?php if (empty($logs)): ?>
                    <p style="color: var(--gray-dark); text-align: center; padding: 20px 0;">No activity found.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <thead style="background: var(--dark); color: var(--light);">
                                <tr>
                                    <th style="padding: 10px 12px; text-align: left;">Date</th>
                                    <th style="padding: 10px 12px; text-align: left;">User</th>
                                    <th style="padding: 10px 12px; text-align: left;">Branch</th>
                                    <th style="padding: 10px 12px; text-align: left;">Product</th>
                                    <th style="padding: 10px 12px; text-align: left;">Action</th>
                                    <th style="padding: 10px 12px; text-align: left;">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr style="border-bottom: 1px solid var(--gray);">
                                        <td style="padding: 8px 12px;"><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
                                        <td style="padding: 8px 12px;"><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></td>
                                        <td style="padding: 8px 12px;"><?= htmlspecialchars($log['branch_name'] ?? 'N/A') ?></td>
                                        <td style="padding: 8px 12px;"><?= htmlspecialchars($log['product_name'] ?? 'Product #' . $log['product_id']) ?></td>
                                        <td style="padding: 8px 12px;">
                                            <?php
                                            $actionLabels = [
                                                'add' => '➕ Add Stock',
                                                'update_status' => '🔄 Status Change',
                                                'delete' => '🗑️ Delete',
                                                'reserve' => '🔒 Reserve',
                                                'release' => '🔓 Release'
                                            ];
                                            echo $actionLabels[$log['action']] ?? ucfirst($log['action']);
                                            ?>
                                        </td>
                                        <td style="padding: 8px 12px;">
                                            <?php if ($log['action'] == 'add'): ?>
                                                Added <?= $log['quantity'] ?> item(s)
                                            <?php elseif ($log['action'] == 'update_status'): ?>
                                                <?= $log['old_status'] ? ucfirst(str_replace('_', ' ', $log['old_status'])) : 'N/A' ?> → <?= $log['new_status'] ? ucfirst(str_replace('_', ' ', $log['new_status'])) : 'N/A' ?>
                                            <?php else: ?>
                                                <?= htmlspecialchars($log['notes'] ?? '') ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- ==================== BRANCH MANAGER DASHBOARD ==================== -->
            
            <!-- Branch Info -->
            <div style="background: #fff; padding: 20px 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); margin-bottom: 32px;">
                <h2 style="font-family: var(--font-heading); font-size: 24px; color: var(--dark); margin-bottom: 4px;">
                    <?= htmlspecialchars($branchName) ?>
                </h2>
                <p style="color: var(--gray-dark); font-size: 14px;">Your branch dashboard</p>
            </div>

            <?php if (!$isAdmin && $branchId <= 0): ?>
                <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                    ⚠️ Your account is not assigned to a branch. Please contact HQ Admin.
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px;">
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">In Stock</p>
                    <p style="font-family: var(--font-heading); font-size: 32px; color: var(--green);"><?= number_format($stats['branch_stock'] ?? 0) ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Reserved</p>
                    <p style="font-family: var(--font-heading); font-size: 32px; color: #f0ad4e;"><?= number_format($stats['reserved_items'] ?? 0) ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Pending Orders</p>
                    <p style="font-family: var(--font-heading); font-size: 32px; color: #f0ad4e;"><?= number_format($stats['pending_orders'] ?? 0) ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Monthly Revenue</p>
                    <p style="font-family: var(--font-heading); font-size: 32px; color: var(--green);">₱ <?= number_format($stats['monthly_revenue'] ?? 0, 2) ?></p>
                </div>
            </div>

            <!-- Quick Links -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 32px;">
                <a href="orders.php" style="text-decoration: none; background: #fff; padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; color: var(--dark); margin: 0 0 4px;">🛒 Branch Orders</h3>
                    <p style="color: var(--gray-dark); font-size: 13px; margin: 0;">Fulfill local orders</p>
                </a>
                <a href="bookings.php" style="text-decoration: none; background: #fff; padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; color: var(--dark); margin: 0 0 4px;">📅 Bookings</h3>
                    <p style="color: var(--gray-dark); font-size: 13px; margin: 0;">Workshop calendar</p>
                </a>
                <a href="inventory.php" style="text-decoration: none; background: #fff; padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; color: var(--dark); margin: 0 0 4px;">📦 Inventory</h3>
                    <p style="color: var(--gray-dark); font-size: 13px; margin: 0;">Stock count & serials</p>
                </a>
                <a href="inventory-add.php" style="text-decoration: none; background: #fff; padding: 18px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; color: var(--dark); margin: 0 0 4px;">➕ Add Stock</h3>
                    <p style="color: var(--gray-dark); font-size: 13px; margin: 0;">Receive new units</p>
                </a>
            </div>

            <!-- Priority Action Panels (Orders & Bookings) -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                
                <!-- Pending Branch Orders -->
                <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 20px; box-shadow: var(--shadow);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                        <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin: 0;">
                            ⚡ Awaiting Fulfillment
                        </h3>
                        <a href="orders.php?status=pending" style="color: var(--green); font-size: 12px; font-weight: 700;">View All &rarr;</a>
                    </div>
                    <?php if (empty($pendingOrdersList)): ?>
                        <p style="color: var(--gray-dark); font-size: 13px; padding: 24px 0; text-align: center;">No orders awaiting dispatch for this branch. ✅</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($pendingOrdersList as $po): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: var(--light); border-radius: var(--radius); border-left: 3px solid #f0ad4e;">
                                    <div>
                                        <strong style="font-size: 14px;">Order #<?= str_pad($po['id'], 5, '0', STR_PAD_LEFT) ?></strong> &bull; <?= htmlspecialchars($po['full_name']) ?>
                                        <div style="font-size: 11px; color: var(--gray-dark);"><?= date('M d, h:i A', strtotime($po['order_date'])) ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <strong style="font-size: 14px; color: var(--dark);">₱ <?= number_format($po['total_amount'], 2) ?></strong><br>
                                        <a href="orders.php?search=<?= $po['id'] ?>" class="btn btn--small btn--outline" style="height: 24px; font-size: 11px; padding: 0 8px; margin-top: 4px;">Process</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Upcoming Appointments -->
                <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 20px; box-shadow: var(--shadow);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                        <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin: 0;">
                            🛠️ Upcoming Workshop Sessions
                        </h3>
                        <a href="bookings.php" style="color: var(--green); font-size: 12px; font-weight: 700;">View All &rarr;</a>
                    </div>
                    <?php if (empty($upcomingBookingsList)): ?>
                        <p style="color: var(--gray-dark); font-size: 13px; padding: 24px 0; text-align: center;">No scheduled services at this branch. ✅</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($upcomingBookingsList as $ub): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: var(--light); border-radius: var(--radius); border-left: 3px solid <?= $ub['status'] === 'pending' ? '#f0ad4e' : '#0275d8' ?>;">
                                    <div>
                                        <strong style="font-size: 14px;"><?= htmlspecialchars($ub['full_name']) ?></strong> &bull; <span style="font-size: 12px; text-transform: capitalize;"><?= str_replace('_', ' ', $ub['service_type']) ?></span>
                                        <div style="font-size: 11px; color: var(--gray-dark);"><?= date('M d, Y @ h:i A', strtotime($ub['scheduled_date'])) ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: <?= $ub['status'] === 'pending' ? '#f0ad4e' : '#0275d8' ?>;"><?= ucfirst($ub['status']) ?></span><br>
                                        <a href="bookings.php?status=<?= $ub['status'] ?>" class="btn btn--small btn--outline" style="height: 24px; font-size: 11px; padding: 0 8px; margin-top: 4px;">Manage</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- ============================================ -->
            <!-- INVENTORY ACTIVITY LOG (Branch Manager)      -->
            <!-- ============================================ -->
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 24px; box-shadow: var(--shadow);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
                    <h3 style="font-family: var(--font-heading); font-size: 24px; text-transform: uppercase; margin: 0;">📋 Inventory Activity</h3>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                        <form method="GET" action="" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                            <input type="hidden" name="section" value="activity">
                            <select name="filter_action" style="height: 36px; padding: 0 12px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px; background: #fff;">
                                <option value="">All Actions</option>
                                <option value="add" <?= (isset($_GET['filter_action']) && $_GET['filter_action'] == 'add') ? 'selected' : '' ?>>Add Stock</option>
                                <option value="update_status" <?= (isset($_GET['filter_action']) && $_GET['filter_action'] == 'update_status') ? 'selected' : '' ?>>Status Change</option>
                            </select>
                            <input type="date" name="filter_date_from" value="<?= isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '' ?>" style="height: 36px; padding: 0 8px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px;">
                            <span style="color: var(--gray-dark);">to</span>
                            <input type="date" name="filter_date_to" value="<?= isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '' ?>" style="height: 36px; padding: 0 8px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px;">
                            <button type="submit" class="btn btn--green btn--small" style="height: 36px; font-size: 14px; padding: 0 16px;">Filter</button>
                            <a href="?section=activity" class="btn btn--outline btn--small" style="height: 36px; font-size: 14px; padding: 0 16px;">Clear</a>
                        </form>
                    </div>
                </div>

                <?php
                // Build query for branch manager – only their branch
                $filterAction = isset($_GET['filter_action']) ? $_GET['filter_action'] : '';
                $filterDateFrom = isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '';
                $filterDateTo = isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '';

                $sql = "
                    SELECT l.*, 
                           u.full_name AS user_name,
                           p.name AS product_name,
                           b.name AS branch_name
                    FROM inventory_logs l
                    LEFT JOIN users u ON l.user_id = u.id
                    LEFT JOIN products p ON l.product_id = p.id
                    LEFT JOIN branches b ON l.branch_id = b.id
                    WHERE l.branch_id = ?
                ";
                $params = [$branchId];

                if (!empty($filterAction)) {
                    $sql .= " AND l.action = ?";
                    $params[] = $filterAction;
                }
                if (!empty($filterDateFrom)) {
                    $sql .= " AND DATE(l.created_at) >= ?";
                    $params[] = $filterDateFrom;
                }
                if (!empty($filterDateTo)) {
                    $sql .= " AND DATE(l.created_at) <= ?";
                    $params[] = $filterDateTo;
                }
                $sql .= " ORDER BY l.created_at DESC LIMIT 50";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $logs = $stmt->fetchAll();
                ?>

                <?php if (empty($logs)): ?>
                    <p style="color: var(--gray-dark); text-align: center; padding: 20px 0;">No activity found.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <thead style="background: var(--dark); color: var(--light);">
                                <tr>
                                    <th style="padding: 10px 12px; text-align: left;">Date</th>
                                    <th style="padding: 10px 12px; text-align: left;">User</th>
                                    <th style="padding: 10px 12px; text-align: left;">Product</th>
                                    <th style="padding: 10px 12px; text-align: left;">Action</th>
                                    <th style="padding: 10px 12px; text-align: left;">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr style="border-bottom: 1px solid var(--gray);">
                                        <td style="padding: 8px 12px;"><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
                                        <td style="padding: 8px 12px;"><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></td>
                                        <td style="padding: 8px 12px;"><?= htmlspecialchars($log['product_name'] ?? 'Product #' . $log['product_id']) ?></td>
                                        <td style="padding: 8px 12px;">
                                            <?php
                                            $actionLabels = [
                                                'add' => '➕ Add Stock',
                                                'update_status' => '🔄 Status Change',
                                                'delete' => '🗑️ Delete',
                                                'reserve' => '🔒 Reserve',
                                                'release' => '🔓 Release'
                                            ];
                                            echo $actionLabels[$log['action']] ?? ucfirst($log['action']);
                                            ?>
                                        </td>
                                        <td style="padding: 8px 12px;">
                                            <?php if ($log['action'] == 'add'): ?>
                                                Added <?= $log['quantity'] ?> item(s)
                                            <?php elseif ($log['action'] == 'update_status'): ?>
                                                <?= $log['old_status'] ? ucfirst(str_replace('_', ' ', $log['old_status'])) : 'N/A' ?> → <?= $log['new_status'] ? ucfirst(str_replace('_', ' ', $log['new_status'])) : 'N/A' ?>
                                            <?php else: ?>
                                                <?= htmlspecialchars($log['notes'] ?? '') ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <!-- Back to Home -->
        <p style="margin-top: 40px;">
            <a href="../../index.php" style="color: var(--green); font-weight: 700;">&larr; Back to Home</a>
        </p>

    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>