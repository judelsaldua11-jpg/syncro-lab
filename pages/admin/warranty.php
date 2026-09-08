<?php
// pages/admin/warranty.php - Manage Warranty Claims

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

// Check role
if (!isLoggedIn()) {
    header('Location: ../auth/login.php?error=Please log in.');
    exit;
}

$role = getUserRole();
if ($role !== 'hq_admin' && $role !== 'branch_manager') {
    header('Location: ../../index.php?error=You do not have permission.');
    exit;
}

$isAdmin = ($role === 'hq_admin');
$branchId = $_SESSION['branch_id'] ?? 0;

$pdo = getConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $claimId = (int)($_POST['claim_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $allowedStatuses = ['approved', 'rejected', 'repaired', 'replaced', 'refunded'];

    if ($claimId > 0 && in_array($newStatus, $allowedStatuses)) {
        try {
            if (!$isAdmin) {
                // Verify claim belongs to branch
                $stmt = $pdo->prepare("SELECT branch_id FROM warranty_claims WHERE id = ?");
                $stmt->execute([$claimId]);
                $claim = $stmt->fetch();
                if (!$claim || $claim['branch_id'] != $branchId) {
                    throw new Exception('You do not have permission.');
                }
            }
            $stmt = $pdo->prepare("UPDATE warranty_claims SET status = ?, resolved_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $claimId]);
            $message = 'Warranty claim updated successfully.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get filter
$statusFilter = $_GET['status'] ?? '';
$refundFilter = $_GET['refund'] ?? '';

// Build query
$sql = "
    SELECT 
        wc.*,
        u.full_name AS customer_name,
        u.email AS customer_email,
        b.name AS branch_name,
        p.name AS product_name,
        i.serial_number,
        o.id AS order_id,
        COUNT(wa.id) AS attachment_count
    FROM warranty_claims wc
    JOIN users u ON wc.user_id = u.id
    JOIN branches b ON wc.branch_id = b.id
    JOIN order_items oi ON wc.order_item_id = oi.id
    JOIN inventory i ON oi.inventory_id = i.id
    JOIN products p ON i.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    LEFT JOIN warranty_attachments wa ON wc.id = wa.warranty_claim_id
    WHERE 1=1
";
$params = [];

if (!$isAdmin) {
    $sql .= " AND wc.branch_id = ?";
    $params[] = $branchId;
}

if (!empty($statusFilter)) {
    $sql .= " AND wc.status = ?";
    $params[] = $statusFilter;
}

if (!empty($refundFilter)) {
    $sql .= " AND wc.refund_option = ?";
    $params[] = $refundFilter;
}

$sql .= " GROUP BY wc.id ORDER BY wc.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$claims = $stmt->fetchAll();

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">

        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            Warranty Claims
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Manage customer warranty claims.
        </p>

        <?php if (isset($message)): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid var(--green);">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
            <a href="?status=&refund=" class="btn btn--small <?= empty($statusFilter) && empty($refundFilter) ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                All
            </a>
            <a href="?status=pending" class="btn btn--small <?= $statusFilter === 'pending' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                Pending
            </a>
            <a href="?status=approved" class="btn btn--small <?= $statusFilter === 'approved' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                Approved
            </a>
            <a href="?status=rejected" class="btn btn--small <?= $statusFilter === 'rejected' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                Rejected
            </a>
            <a href="?status=refunded" class="btn btn--small <?= $statusFilter === 'refunded' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                Refunded
            </a>
        </div>

        <?php if (empty($claims)): ?>
            <p style="color: var(--gray-dark); text-align: center; padding: 60px 0;">No warranty claims found.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($claims as $claim): ?>
                    <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 20px 24px; box-shadow: var(--shadow);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                            <div>
                                <p style="font-weight: 700; font-family: var(--font-heading); font-size: 18px;">
                                    <?= htmlspecialchars($claim['customer_name']) ?>
                                </p>
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    <?= htmlspecialchars($claim['product_name']) ?> (SN: <?= htmlspecialchars($claim['serial_number']) ?>)
                                </p>
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    Order #<?= $claim['order_id'] ?> | Branch: <?= htmlspecialchars($claim['branch_name']) ?>
                                </p>
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    <?= date('M d, Y', strtotime($claim['created_at'])) ?>
                                </p>
                                <p style="color: var(--gray-dark); font-size: 14px; margin-top: 4px;">
                                    <strong>Issue:</strong> <?= htmlspecialchars(substr($claim['issue_description'], 0, 100)) ?>...
                                </p>
                                <p style="color: var(--gray-dark); font-size: 14px;">
                                    <strong>Resolution:</strong> <?= ucfirst(str_replace('_', ' ', $claim['refund_option'])) ?>
                                </p>
                                <p style="font-size: 14px; color: <?= $claim['status'] === 'pending' ? '#f0ad4e' : ($claim['status'] === 'approved' || $claim['status'] === 'refunded' ? 'var(--green)' : '#d9534f') ?>; font-weight: 700;">
                                    <?= ucfirst($claim['status']) ?>
                                </p>
                                <?php if ($claim['attachment_count'] > 0): ?>
                                    <p style="color: var(--gray-dark); font-size: 12px;">📎 <?= $claim['attachment_count'] ?> attachment(s)</p>
                                <?php endif; ?>
                            </div>
                            <?php if ($claim['status'] === 'pending'): ?>
                                <form method="POST" action="" style="display: flex; flex-direction: column; gap: 8px;">
                                    <input type="hidden" name="claim_id" value="<?= $claim['id'] ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <select name="status" style="height: 36px; padding: 0 12px; border: 1px solid var(--gray); border-radius: var(--radius); background: #fff;">
                                        <option value="">-- Update --</option>
                                        <option value="approved">Approve</option>
                                        <option value="rejected">Reject</option>
                                        <option value="repaired">Repaired</option>
                                        <option value="replaced">Replaced</option>
                                        <option value="refunded">Refunded</option>
                                    </select>
                                    <button type="submit" class="btn btn--green btn--small" style="height: 36px; font-size: 14px; padding: 0 12px;">Update</button>
                                </form>
                            <?php else: ?>
                                <span style="color: var(--gray-dark); font-size: 14px;">Claim finalized</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p style="margin-top: 32px;">
            <a href="dashboard.php" style="color: var(--green); font-weight: 700;">&larr; Back to Dashboard</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>