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
    $allowedStatuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];

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
            $stmt = $pdo->prepare("UPDATE service_bookings SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $bookingId]);
            $message = 'Booking status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . ' successfully.';
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
           u.phone AS customer_phone,
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

        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <div>
                <a href="dashboard.php" style="color: var(--gray-dark); font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                    &larr; Back to Dashboard
                </a>
                <h1 style="font-family: var(--font-heading); font-size: 38px; text-transform: uppercase; margin: 0;">
                    Service Bookings
                </h1>
                <p style="color: var(--gray-dark); font-size: 16px; margin-top: 4px;">
                    Manage workshop appointments, fit sessions, and repair jobs.
                </p>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <a href="dashboard.php" class="btn btn--outline btn--small" style="height: 38px; padding: 0 16px;">
                    📊 Dashboard
                </a>
                <a href="orders.php" class="btn btn--outline btn--small" style="height: 38px; padding: 0 16px;">
                    🛒 Orders
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

        <!-- Filter Tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;">
            <a href="bookings.php" class="btn btn--small <?= empty($statusFilter) ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 13px; padding: 0 16px;">
                All Bookings
            </a>
            <a href="?status=pending" class="btn btn--small <?= $statusFilter === 'pending' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 13px; padding: 0 16px;">
                Pending Review
            </a>
            <a href="?status=confirmed" class="btn btn--small <?= $statusFilter === 'confirmed' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 13px; padding: 0 16px;">
                Confirmed
            </a>
            <a href="?status=in_progress" class="btn btn--small <?= $statusFilter === 'in_progress' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 13px; padding: 0 16px;">
                In Progress (Workshop)
            </a>
            <a href="?status=completed" class="btn btn--small <?= $statusFilter === 'completed' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 13px; padding: 0 16px;">
                Completed
            </a>
            <a href="?status=cancelled" class="btn btn--small <?= $statusFilter === 'cancelled' ? 'btn--green' : 'btn--outline' ?>" style="height: 36px; font-size: 13px; padding: 0 16px;">
                Cancelled
            </a>
        </div>

        <?php if (empty($bookings)): ?>
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 50px 20px; text-align: center; box-shadow: var(--shadow);">
                <p style="font-size: 18px; color: var(--gray-dark); margin-bottom: 12px;">No bookings found matching this filter.</p>
                <a href="bookings.php" class="btn btn--small btn--outline">Reset Filter</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($bookings as $booking): 
                    $bStatus = $booking['status'];
                    $bColor = '#68747b';
                    if ($bStatus === 'pending') $bColor = '#f0ad4e';
                    elseif ($bStatus === 'confirmed') $bColor = '#0275d8';
                    elseif ($bStatus === 'in_progress') $bColor = '#6f42c1';
                    elseif ($bStatus === 'completed') $bColor = 'var(--green)';
                    elseif ($bStatus === 'cancelled') $bColor = '#d9534f';
                    
                    $serviceLabel = ucwords(str_replace('_', ' ', $booking['service_type']));
                ?>
                    <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 20px 24px; box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                        <div style="flex: 1; min-width: 280px;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                                <h3 style="font-family: var(--font-heading); font-size: 20px; margin: 0;">
                                    <?= htmlspecialchars($booking['customer_name']) ?>
                                </h3>
                                <span style="display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: <?= $bColor ?>1a; color: <?= $bColor ?>; border: 1px solid <?= $bColor ?>;">
                                    <?= ucfirst(str_replace('_', ' ', $bStatus)) ?>
                                </span>
                            </div>
                            
                            <p style="color: var(--dark); font-weight: 600; font-size: 15px; margin-bottom: 4px;">
                                🛠️ <?= htmlspecialchars($serviceLabel) ?>
                            </p>
                            <p style="color: var(--gray-dark); font-size: 13px; margin-bottom: 4px;">
                                📅 <strong>Scheduled:</strong> <?= date('l, M d, Y @ h:i A', strtotime($booking['scheduled_date'])) ?>
                            </p>
                            <p style="color: var(--gray-dark); font-size: 13px; margin-bottom: 4px;">
                                📍 <strong>Branch:</strong> <?= htmlspecialchars($booking['branch_name']) ?> | 
                                ✉️ <?= htmlspecialchars($booking['customer_email']) ?>
                                <?= !empty($booking['customer_phone']) ? ' | 📞 ' . htmlspecialchars($booking['customer_phone']) : '' ?>
                            </p>
                            
                            <?php if (!empty($booking['notes'])): ?>
                                <div style="background: var(--light); padding: 8px 12px; border-radius: var(--radius); font-size: 13px; color: var(--dark); margin-top: 8px; border-left: 3px solid var(--gray);">
                                    <strong>Customer Notes:</strong> <?= htmlspecialchars($booking['notes']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Action Form -->
                        <div style="background: #fafafa; border: 1px solid var(--gray); border-radius: var(--radius); padding: 14px; min-width: 260px;">
                            <span style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--gray-dark); margin-bottom: 8px;">
                                Change Service Status
                            </span>
                            <form method="POST" action="" style="display: flex; gap: 8px; align-items: center;">
                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                <input type="hidden" name="action" value="update_status">
                                <select name="status" style="flex: 1; height: 38px; padding: 0 10px; border: 1px solid var(--gray); border-radius: var(--radius); background: #fff; font-size: 13px;">
                                    <option value="pending" <?= $bStatus === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                                    <option value="confirmed" <?= $bStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="in_progress" <?= $bStatus === 'in_progress' ? 'selected' : '' ?>>In Workshop</option>
                                    <option value="completed" <?= $bStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $bStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" class="btn btn--green btn--small" style="height: 38px; font-size: 13px; padding: 0 14px;">Update</button>
                            </form>
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