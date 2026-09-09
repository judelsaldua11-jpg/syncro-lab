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
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND is_active = 1");
    $stats['total_customers'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'branch_manager' AND is_active = 1");
    $stats['total_managers'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled' AND MONTH(order_date) = MONTH(CURRENT_DATE()) AND YEAR(order_date) = YEAR(CURRENT_DATE())");
    $stats['monthly_revenue'] = $stmt->fetchColumn() ?? 0;
    
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
}

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Page Header -->
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            Dashboard
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 40px;">
            Welcome, <?= htmlspecialchars($user['full_name']) ?> 
            (<?= $isAdmin ? 'HQ Admin' : 'Branch Manager' ?>)
        </p>

        <?php if ($isAdmin): ?>
            <!-- ==================== HQ ADMIN DASHBOARD ==================== -->
            
            <!-- Stats Grid -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px;">
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Products</p>
                    <p style="font-family: var(--font-heading); font-size: 32px; color: var(--dark);"><?= number_format($stats['total_products']) ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Total Stock</p>
                    <p style="font-family: var(--font-heading); font-size: 32px; color: var(--green);"><?= number_format($stats['total_stock']) ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Branches</p>
                    <p style="font-family: var(--font-heading); font-size: 32px; color: var(--dark);"><?= $stats['total_branches'] ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <p style="color: var(--gray-dark); font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Monthly Revenue</p>
                    <p style="font-family: var(--font-heading); font-size: 32px; color: var(--green);">₱ <?= number_format($stats['monthly_revenue'], 2) ?></p>
                </div>
            </div>

            <!-- Quick Links -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px;">
                <a href="products.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">📦 Products</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Manage product catalog and featured items</p>
                </a>
                <a href="inventory.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);;">📦 Inventory</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Manage stock across all branches</p>
                </a>
                <a href="bookings.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">📅 Bookings</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Manage customer service appointments</p>
                </a>
                <a href="warranty.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">🛡️ Warranty</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Manage warranty claims</p>
                </a>
                <a href="../chat.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">💬 Chat</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Respond to customer inquiries</p>
                </a>
                <a href="locations.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">📍 Locations</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Manage lab branches</p>
                </a>
                <a href="categories.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">📂 Categories</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Manage product categories</p>
                </a>
                <a href="users.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">👥 Users</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Manage user accounts & roles</p>
                </a>
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
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px;">
                <a href="inventory.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">📦 Inventory</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Manage your branch stock</p>
                </a>
                <a href="inventory-add.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">➕ Add Stock</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Add new items to inventory</p>
                </a>
                <a href="bookings.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">📅 Bookings</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Manage customer service appointments</p>
                </a>
                <a href="warranty.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">🛡️ Warranty</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Manage warranty claims</p>
                </a>
                <a href="../chat.php" style="text-decoration: none; background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); transition: transform 0.2s ease;">
                    <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--dark);">💬 Chat</h3>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-top: 8px;">Respond to customer inquiries</p>
                </a>
            </div>

            <!-- Pending Bookings -->
            <div style="background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); margin-bottom: 40px;">
                <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 16px;">📅 Pending Bookings</h3>
                <?php if ($stats['pending_bookings'] > 0): ?>
                    <p><strong><?= $stats['pending_bookings'] ?></strong> booking(s) waiting for confirmation.</p>
                    <a href="bookings.php" style="color: var(--green); font-weight: 600;">View all bookings →</a>
                <?php else: ?>
                    <p style="color: var(--gray-dark);">No pending bookings. ✅</p>
                <?php endif; ?>
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