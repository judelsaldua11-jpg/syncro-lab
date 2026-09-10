<?php
// pages/profile.php - User Profile & Service Appointments

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to view your profile.');
    exit;
}

$user = getCurrentUser();
$pdo = getConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'customer';

// Fetch customer bookings
$stmt = $pdo->prepare("
    SELECT sb.*, b.name AS branch_name
    FROM service_bookings sb
    JOIN branches b ON sb.branch_id = b.id
    WHERE sb.user_id = ?
    ORDER BY sb.scheduled_date DESC
");
$stmt->execute([$userId]);
$userBookings = $stmt->fetchAll();

// Fetch customer order count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$orderCount = $stmt->fetchColumn();

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1100px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            My Profile
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 40px;">
            Welcome back, <?= htmlspecialchars($user['full_name']) ?>
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 32px;">
            
            <!-- Account Details -->
            <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow); height: fit-content;">
                <h2 style="font-family: var(--font-heading); font-size: 24px; margin-bottom: 20px; text-transform: uppercase;">Account Details</h2>
                
                <div style="display: flex; flex-direction: column; gap: 10px; font-size: 15px;">
                    <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not set') ?></p>
                    <p><strong>Role:</strong> <?= ucfirst($user['role']) ?></p>
                    <p><strong>Member Since:</strong> <?= date('F d, Y', strtotime($user['created_at'])) ?></p>
                    <?php if (!empty($user['membership_expiry'])): ?>
                        <p><strong>Membership Status:</strong> 
                            <span style="color: var(--green); font-weight: 700;">Active</span> (expires <?= date('M d, Y', strtotime($user['membership_expiry'])) ?>)
                        </p>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 10px;">
                    <a href="orders.php" class="btn btn--green btn--small" style="text-align: center; justify-content: center;">
                        📦 View My Orders (<?= $orderCount ?>)
                    </a>
                    <a href="booking.php" class="btn btn--outline btn--small" style="text-align: center; justify-content: center;">
                        🛠️ Book New Service
                    </a>
                    <?php if ($userRole === 'hq_admin' || $userRole === 'branch_manager'): ?>
                        <a href="admin/dashboard.php" class="btn btn--outline btn--small" style="text-align: center; justify-content: center;">
                            📊 Control Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Service Appointments -->
            <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow);">
                <h2 style="font-family: var(--font-heading); font-size: 24px; margin-bottom: 20px; text-transform: uppercase;">My Service Appointments</h2>
                
                <?php if (empty($userBookings)): ?>
                    <p style="color: var(--gray-dark); padding: 40px 0; text-align: center;">You have no active or previous service bookings.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($userBookings as $ub): 
                            $bStatus = $ub['status'];
                            $bColor = '#68747b';
                            if ($bStatus === 'pending') $bColor = '#f0ad4e';
                            elseif ($bStatus === 'confirmed') $bColor = '#0275d8';
                            elseif ($bStatus === 'in_progress') $bColor = '#6f42c1';
                            elseif ($bStatus === 'completed') $bColor = 'var(--green)';
                            elseif ($bStatus === 'cancelled') $bColor = '#d9534f';
                        ?>
                            <div style="background: var(--light); padding: 16px; border-radius: var(--radius); border-left: 4px solid <?= $bColor ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <strong style="font-size: 16px; text-transform: capitalize;">
                                        <?= str_replace('_', ' ', $ub['service_type']) ?>
                                    </strong>
                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: <?= $bColor ?>; border: 1px solid <?= $bColor ?>; padding: 2px 8px; border-radius: 12px;">
                                        <?= ucfirst(str_replace('_', ' ', $bStatus)) ?>
                                    </span>
                                </div>
                                <p style="font-size: 13px; color: var(--gray-dark); margin: 2px 0;">
                                    📅 <?= date('l, F d, Y @ h:i A', strtotime($ub['scheduled_date'])) ?>
                                </p>
                                <p style="font-size: 13px; color: var(--gray-dark); margin: 2px 0;">
                                    📍 <?= htmlspecialchars($ub['branch_name']) ?>
                                </p>
                                <?php if (!empty($ub['notes'])): ?>
                                    <p style="font-size: 12px; color: var(--dark); margin-top: 6px; background: #fff; padding: 6px 10px; border-radius: var(--radius);">
                                        <strong>Notes:</strong> <?= htmlspecialchars($ub['notes']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <p style="margin-top: 32px;">
            <a href="../index.php" style="color: var(--green); font-weight: 700;">&larr; Back to Home</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>