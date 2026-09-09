<?php
// pages/admin/users.php - User Management (HQ Admin Only)

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

// Check if user is logged in and is HQ Admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../auth/login.php?error=Please log in to access the admin panel.');
    exit;
}

$pdo = getConnection();
$message = '';
$error = '';

// Handle user role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['role'] ?? '';
    $allowedRoles = ['customer', 'branch_manager', 'hq_admin'];
    
    if ($userId > 0 && in_array($newRole, $allowedRoles)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            $message = "User role updated successfully!";
        } catch (PDOException $e) {
            $error = "Failed to update role.";
        }
    }
}

// Handle user toggle active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_active') {
    $userId = (int)$_POST['user_id'];
    $currentStatus = (int)$_POST['current_status'];
    $newStatus = $currentStatus ? 0 : 1;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        $message = "User status updated successfully!";
    } catch (PDOException $e) {
        $error = "Failed to update user status.";
    }
}

// Get filter parameter
$roleFilter = isset($_GET['role']) ? $_GET['role'] : 'all';

// Build query
$sql = "SELECT id, email, full_name, phone, role, is_active, membership_start, membership_expiry, created_at FROM users";
$params = [];

if ($roleFilter !== 'all') {
    $sql .= " WHERE role = ?";
    $params[] = $roleFilter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<style>
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }
    .status-badge.active {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .status-badge.inactive {
        background: #ffebee;
        color: #c62828;
    }
    .status-badge.member {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .status-badge.non-member {
        background: #fff3cd;
        color: #856404;
    }
    .role-badge {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        background: var(--gray);
        color: #fff;
    }
    .role-badge.admin {
        background: #d9534f;
    }
    .role-badge.manager {
        background: #5bc0de;
    }
    .role-badge.customer {
        background: #5cb85c;
    }
</style>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
                    👥 User Management
                </h1>
                <p style="color: var(--gray-dark); font-size: 18px;">
                    Manage all users and their roles.
                </p>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <span style="color: var(--gray-dark); font-size: 14px;">
                    Total users: <?= count($users) ?>
                </span>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid var(--green);">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Role Filter -->
        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px;">
            <a href="?role=all" class="btn btn--small <?= $roleFilter === 'all' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                All
            </a>
            <a href="?role=customer" class="btn btn--small <?= $roleFilter === 'customer' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                👤 Customers
            </a>
            <a href="?role=branch_manager" class="btn btn--small <?= $roleFilter === 'branch_manager' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                🏪 Branch Managers
            </a>
            <a href="?role=hq_admin" class="btn btn--small <?= $roleFilter === 'hq_admin' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                ⭐ HQ Admins
            </a>
        </div>

        <!-- Users Table -->
        <?php if (empty($users)): ?>
            <div style="text-align: center; padding: 60px 0; background: #fff; border-radius: var(--radius); border: 1px solid var(--gray);">
                <p style="font-size: 22px; color: var(--gray-dark);">No users found.</p>
            </div>
        <?php else: ?>
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); overflow: hidden; box-shadow: var(--shadow); overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <thead style="background: var(--dark); color: var(--light);">
                        <tr>
                            <th style="padding: 12px 16px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">User</th>
                            <th style="padding: 12px 16px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Role</th>
                            <th style="padding: 12px 16px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Membership</th>
                            <th style="padding: 12px 16px; text-align: center; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                            <th style="padding: 12px 16px; text-align: center; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            $isMember = isMembershipActive($user['id']);
                            $memberExpiry = $user['membership_expiry'] ? date('M d, Y', strtotime($user['membership_expiry'])) : 'N/A';
                        ?>
                            <tr style="border-bottom: 1px solid var(--gray);">
                                <td style="padding: 12px 16px;">
                                    <div style="display: flex; flex-direction: column;">
                                        <strong style="font-size: 15px;"><?= htmlspecialchars($user['full_name']) ?></strong>
                                        <span style="color: var(--gray-dark); font-size: 13px;"><?= htmlspecialchars($user['email']) ?></span>
                                        <?php if ($user['phone']): ?>
                                            <span style="color: var(--gray-dark); font-size: 12px;">📞 <?= htmlspecialchars($user['phone']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span class="role-badge <?= $user['role'] ?>">
                                        <?php
                                        $roleLabels = [
                                            'customer' => 'Customer',
                                            'branch_manager' => 'Branch Manager',
                                            'hq_admin' => 'HQ Admin'
                                        ];
                                        echo $roleLabels[$user['role']] ?? ucfirst($user['role']);
                                        ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div style="display: flex; flex-direction: column; gap: 2px;">
                                        <span class="status-badge <?= $isMember ? 'member' : 'non-member' ?>">
                                            <?= $isMember ? '✅ Active Member' : '❌ Non-Member' ?>
                                        </span>
                                        <?php if ($isMember): ?>
                                            <span style="color: var(--gray-dark); font-size: 12px;">
                                                Expires: <?= $memberExpiry ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span class="status-badge <?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
                                        <!-- Update Role Form -->
                                        <form method="POST" action="" style="display: flex; gap: 4px; align-items: center;">
                                            <input type="hidden" name="action" value="update_role">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <select name="role" style="height: 32px; padding: 0 8px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 12px; background: #fff;">
                                                <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                                <option value="branch_manager" <?= $user['role'] === 'branch_manager' ? 'selected' : '' ?>>Manager</option>
                                                <option value="hq_admin" <?= $user['role'] === 'hq_admin' ? 'selected' : '' ?>>HQ Admin</option>
                                            </select>
                                            <button type="submit" class="btn btn--green btn--small" style="height: 32px; font-size: 11px; padding: 0 10px;">Update</button>
                                        </form>
                                        <!-- Toggle Active Form -->
                                        <form method="POST" action="" style="display: flex; gap: 4px; align-items: center;">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $user['is_active'] ?>">
                                            <button type="submit" class="btn btn--small <?= $user['is_active'] ? 'btn--dark' : 'btn--green' ?>" style="height: 32px; font-size: 11px; padding: 0 10px;">
                                                <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p style="margin-top: 32px;">
            <a href="dashboard.php" style="color: var(--green); font-weight: 700;">&larr; Back to Dashboard</a>
        </p>
        
    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>