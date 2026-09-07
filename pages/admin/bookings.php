<?php
// pages/admin/bookings.php - Manage Bookings

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

$message = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $allowedStatuses = ['confirmed', 'cancelled'];

    if ($bookingId > 0 && in_array($newStatus, $allowedStatuses)) {
        try {
            // If not admin, verify booking belongs to their branch
            if (!$isAdmin) {
                $stmt = $pdo->prepare("SELECT branch_id FROM service_bookings WHERE id = ?");
                $stmt->execute([$bookingId]);
                $booking = $stmt->fetch();
                if (!$booking || $booking['branch_id'] != $branchId) {
                    throw new Exception('You do not have permission.');
                }
            }
            $stmt = $pdo->prepare("UPDATE service_bookings SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $bookingId]);
            $message = 'Booking status updated successfully.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get filter
$statusFilter = $_GET['status'] ?? '';

// Build query
$sql = "
    SELECT sb.*, 
           u.full_name AS customer_name,
           u.email AS customer_email,
           b.name AS branch_name
    FROM service_bookings sb
    JOIN users u ON sb.user_id = u.id
    JOIN branches b ON sb.branch_id = b.id
    WHERE 1=1
";
$params = [];

if (!$isAdmin) {
    $sql .= " AND sb.branch_id = ?";
    $params[] = $branchId;
}

if (!empty($statusFilter)) {
    $sql .= " AND sb.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY sb.scheduled_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">

        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            Service Bookings
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Manage customer service appointments.
        </p>

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

        <!-- Filter -->
        <div style="display: flex; gap: 16px; margin-bottom: 24px;">
            <a href="?status=" class="btn btn--small <?= empty($statusFilter) ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                All
            </a>
            <a href="?status=pending" class="btn btn--small <?= $statusFilter === 'pending' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                Pending
            </a>
            <a href="?status=confirmed" class="btn btn--small <?= $statusFilter === 'confirmed' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                Confirmed
            </a>
            <a href="?status=cancelled" class="btn btn--small <?= $statusFilter === 'cancelled' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 14px; padding: 0 16px;">
                Cancelled
            </a>
        </div>

        <?php if (empty($bookings)): ?>
            <p style="color: var(--gray-dark); text-align: center; padding: 60px 0;">No bookings found.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($bookings as $booking): ?>
                    <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 20px 24px; box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <div>
                            <p style="font-weight: 700; font-family: var(--font-heading); font-size: 18px;">
                                <?= htmlspecialchars($booking['customer_name']) ?>
                            </p>
                            <p style="color: var(--gray-dark); font-size: 14px;">
                                <?= htmlspecialchars($booking['service_type']) ?>
                            </p>
                            <p style="color: var(--gray-dark); font-size: 14px;">
                                <?= date('M d, Y @ h:i A', strtotime($booking['scheduled_date'])) ?>
                            </p>
                            <p style="color: var(--gray-dark); font-size: 14px;">
                                Branch: <?= htmlspecialchars($booking['branch_name']) ?>
                            </p>
                            <?php if ($booking['notes']): ?>
                                <p style="color: var(--gray-dark); font-size: 14px; margin-top: 4px;">
                                    <strong>Notes:</strong> <?= htmlspecialchars($booking['notes']) ?>
                                </p>
                            <?php endif; ?>
                            <p style="font-size: 14px; color: <?= $booking['status'] === 'pending' ? '#f0ad4e' : ($booking['status'] === 'confirmed' ? 'var(--green)' : '#d9534f') ?>; font-weight: 700;">
                                <?= ucfirst($booking['status']) ?>
                            </p>
                        </div>
                        <?php if ($booking['status'] === 'pending'): ?>
                            <form method="POST" action="" style="display: flex; gap: 8px;">
                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                <input type="hidden" name="action" value="update_status">
                                <select name="status" style="height: 36px; padding: 0 12px; border: 1px solid var(--gray); border-radius: var(--radius); background: #fff;">
                                    <option value="">-- Update --</option>
                                    <option value="confirmed">Confirm</option>
                                    <option value="cancelled">Cancel</option>
                                </select>
                                <button type="submit" class="btn btn--green btn--small" style="height: 36px; font-size: 14px; padding: 0 12px;">Update</button>
                            </form>
                        <?php else: ?>
                            <span style="color: var(--gray-dark); font-size: 14px;">Status finalized</span>
                        <?php endif; ?>
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