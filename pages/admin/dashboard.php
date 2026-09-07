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
            </div>

            <!-- Additional Stats -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div style="background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 16px;">👥 Users</h3>
                    <p><strong>Customers:</strong> <?= number_format($stats['total_customers']) ?></p>
                    <p><strong>Branch Managers:</strong> <?= number_format($stats['total_managers']) ?></p>
                    <p><strong>Pending Orders:</strong> <?= number_format($stats['pending_orders']) ?></p>
                </div>
                <div style="background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 16px;">⚡ Quick Actions</h3>
                    <p style="margin-bottom: 8px;">
                        <a href="products-add.php" style="color: var(--green); font-weight: 600;">+ Add New Product</a>
                    </p>
                    <p style="margin-bottom: 8px;">
                        <a href="inventory-add.php" style="color: var(--green); font-weight: 600;">+ Add Stock</a>
                    </p>
                </div>
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
            </div>

            <!-- Pending Bookings -->
            <div style="background: #fff; padding: 24px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 16px;">📅 Pending Bookings</h3>
                <?php if ($stats['pending_bookings'] > 0): ?>
                    <p><strong><?= $stats['pending_bookings'] ?></strong> booking(s) waiting for confirmation.</p>
                    <a href="bookings.php" style="color: var(--green); font-weight: 600;">View all bookings →</a>
                <?php else: ?>
                    <p style="color: var(--gray-dark);">No pending bookings. ✅</p>
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