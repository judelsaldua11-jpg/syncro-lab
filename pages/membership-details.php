<?php
// pages/membership-details.php - Membership Details (Overview)

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $_SESSION['user_id'] ?? null;
$isMember   = false;
$memberData = null;
$lastPayment = null;

if ($isLoggedIn) {
    $isMember = isMembershipActive($userId);
    $pdo = getConnection();

    $stmt = $pdo->prepare("SELECT membership_start, membership_expiry FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $memberData = $stmt->fetch();

    if ($isMember) {
        $stmtPay = $pdo->prepare("
            SELECT payment_method, reference_number, wallet_number, card_last4, verified_at, id AS payment_id
            FROM membership_payments
            WHERE user_id = ? AND status = 'verified'
            ORDER BY verified_at DESC LIMIT 1
        ");
        $stmtPay->execute([$userId]);
        $lastPayment = $stmtPay->fetch();
    }
}

/* Membership duration + remaining days */
$daysRemaining = null;
$durationLabel = null;
if ($isMember && $memberData) {
    $now    = new DateTime();
    $expiry = new DateTime($memberData['membership_expiry']);
    $start  = new DateTime($memberData['membership_start']);

    $daysRemaining = (int)$now->diff($expiry)->format('%a');

    $totalDuration = $start->diff($expiry);
    $durationLabel = $totalDuration->y . ' year' . ($totalDuration->y !== 1 ? 's' : '');
    if ($totalDuration->m > 0) {
        $durationLabel .= ', ' . $totalDuration->m . ' month' . ($totalDuration->m !== 1 ? 's' : '');
    }
}

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<style>
.benefit-card {
    background: #fff;
    border-radius: var(--radius);
    border: 1px solid var(--gray);
    padding: 28px 24px;
    text-align: center;
    box-shadow: var(--shadow);
    position: relative;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.benefit-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}
.benefit-card .included-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #e8f5e9;
    color: #2e7d32;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
}
.membership-progress {
    background: var(--gray);
    border-radius: 100px;
    height: 8px;
    overflow: hidden;
    margin-top: 10px;
}
.membership-progress-bar {
    height: 100%;
    background: var(--green);
    border-radius: 100px;
    transition: width 0.5s ease;
}
</style>

<div style="padding: 60px 0; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1100px; margin: 0 auto; padding: 0 40px;">

        <!-- Hero -->
        <div style="text-align: center; margin-bottom: 48px;">
            <p style="color: var(--green); font-family: var(--font-heading); font-size: 14px; text-transform: uppercase; letter-spacing: 2px;">
                Exclusive Program
            </p>
            <h1 style="font-family: var(--font-heading); font-size: 64px; text-transform: uppercase; margin-bottom: 16px;">
                SYNCRO LAB <span style="color: var(--green);">MEMBERSHIP</span>
            </h1>
            <p style="color: var(--gray-dark); font-size: 20px; max-width: 700px; margin: 0 auto;">
                Get 12 months of faster service, expert support, and priority access to rare gear.
            </p>
        </div>

        <!-- Flash -->
        <?php if (!empty($_GET['success'])): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 16px 24px; border-radius: var(--radius); border-left: 4px solid var(--green); margin-bottom: 32px;">
                ✅ <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php elseif (!empty($_GET['message'])): ?>
            <div style="background: #e3f2fd; color: #1565c0; padding: 16px 24px; border-radius: var(--radius); border-left: 4px solid #1976d2; margin-bottom: 32px;">
                ℹ️ <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>

        <!-- ACTIVE MEMBER CARD -->
        <?php if ($isLoggedIn && $isMember && $memberData): ?>
            <?php
            $startFormatted  = date('F d, Y', strtotime($memberData['membership_start']));
            $expiryFormatted = date('F d, Y', strtotime($memberData['membership_expiry']));

            $totalSec    = strtotime($memberData['membership_expiry']) - strtotime($memberData['membership_start']);
            $elapsedSec  = time() - strtotime($memberData['membership_start']);
            $progressPct = max(0, min(100, round(($elapsedSec / $totalSec) * 100)));
            $remainPct   = 100 - $progressPct;
            ?>
            <div style="background: #fff; border: 2px solid var(--green); border-radius: var(--radius); padding: 28px 32px; margin-bottom: 40px; box-shadow: var(--shadow);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <p style="color: var(--green); font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">✅ Active Membership</p>
                        <h2 style="font-family: var(--font-heading); font-size: 26px; margin-bottom: 4px;">SYNCRO LAB Member</h2>
                        <p style="color: var(--gray-dark); font-size: 14px; margin: 0;">
                            Duration: <strong><?= $durationLabel ?></strong>
                            &nbsp;·&nbsp; Valid from <strong><?= $startFormatted ?></strong> to <strong><?= $expiryFormatted ?></strong>
                        </p>
                    </div>
                    <div style="text-align: right; min-width: 140px;">
                        <p style="font-size: 36px; font-weight: 900; color: var(--dark); margin: 0; line-height: 1;"><?= $daysRemaining ?></p>
                        <p style="font-size: 13px; color: var(--gray-dark); margin: 0;">days remaining</p>
                        <?php if ($daysRemaining <= 30): ?>
                            <span style="background: #fff3cd; color: #856404; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 10px; margin-top: 4px; display: inline-block;">⚠️ Expiring Soon</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Progress -->
                <div style="margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--gray-dark); margin-bottom: 6px;">
                        <span>Started <?= $startFormatted ?></span>
                        <span><?= $remainPct ?>% remaining</span>
                        <span>Expires <?= $expiryFormatted ?></span>
                    </div>
                    <div class="membership-progress">
                        <div class="membership-progress-bar" style="width: <?= $progressPct ?>%;"></div>
                    </div>
                </div>

                <?php if ($lastPayment): ?>
                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray); font-size: 13px; color: var(--gray-dark);">
                        <?php
                        if ($lastPayment['payment_method'] === 'gcash') {
                            $methodLabel = '💙 GCash';
                            if ($lastPayment['wallet_number']) $methodLabel .= ' (' . $lastPayment['wallet_number'] . ')';
                        } elseif ($lastPayment['payment_method'] === 'maya') {
                            $methodLabel = '💚 Maya';
                            if ($lastPayment['wallet_number']) $methodLabel .= ' (' . $lastPayment['wallet_number'] . ')';
                        } else {
                            $methodLabel = '💳 Card';
                            if ($lastPayment['card_last4']) $methodLabel .= ' •••• ' . $lastPayment['card_last4'];
                        }
                        ?>
                        Paid via <strong><?= $methodLabel ?></strong>
                        &nbsp;·&nbsp; Ref: <strong><?= htmlspecialchars($lastPayment['reference_number']) ?></strong>
                        &nbsp;·&nbsp; <?= date('M d, Y g:i A', strtotime($lastPayment['verified_at'])) ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($isLoggedIn && !$isMember): ?>
            <?php if ($memberData && $memberData['membership_expiry']): ?>
                <div style="background: #fff3cd; color: #856404; padding: 16px 24px; border-radius: var(--radius); border-left: 4px solid #f0ad4e; margin-bottom: 40px;">
                    <strong>⏳ Membership Expired</strong>
                    — your membership ended on <strong><?= date('F d, Y', strtotime($memberData['membership_expiry'])) ?></strong>.
                    Renew to continue enjoying benefits.
                </div>
            <?php else: ?>
                <div style="background: #fff3cd; color: #856404; padding: 16px 24px; border-radius: var(--radius); border-left: 4px solid #f0ad4e; margin-bottom: 40px;">
                    <strong>❌ No active membership.</strong> Register below to unlock all member benefits.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- BENEFITS GRID (overview only) -->
        <h2 style="font-family: var(--font-heading); font-size: 28px; text-transform: uppercase; margin-bottom: 20px;">Member Benefits</h2>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 48px;">

            <div class="benefit-card">
                <?php if ($isMember): ?><span class="included-badge">✓ Included</span><?php endif; ?>
                <div style="font-size: 48px; margin-bottom: 16px;">⚡</div>
                <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin-bottom: 8px;">Fast Service</h3>
                <p style="color: var(--gray-dark); font-size: 14px; line-height: 1.6;">
                    Guaranteed <strong>24-hour repair turnaround</strong> on all services.
                </p>
            </div>

            <div class="benefit-card">
                <?php if ($isMember): ?><span class="included-badge">✓ Included</span><?php endif; ?>
                <div style="font-size: 48px; margin-bottom: 16px;">💰</div>
                <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin-bottom: 8px;">10% Discount</h3>
                <p style="color: var(--gray-dark); font-size: 14px; line-height: 1.6;">
                    <strong>10% discount</strong> on all service labor costs.
                </p>
            </div>

            <div class="benefit-card">
                <?php if ($isMember): ?><span class="included-badge">✓ Included</span><?php endif; ?>
                <div style="font-size: 48px; margin-bottom: 16px;">🚴</div>
                <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin-bottom: 8px;">Free Bike Fit</h3>
                <p style="color: var(--gray-dark); font-size: 14px; line-height: 1.6;">
                    Annual <strong>laser bike fit</strong> session included with membership.
                </p>
            </div>

            <div class="benefit-card">
                <?php if ($isMember): ?><span class="included-badge">✓ Included</span><?php endif; ?>
                <div style="font-size: 48px; margin-bottom: 16px;">🧼</div>
                <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin-bottom: 8px;">Deep Clean</h3>
                <p style="color: var(--gray-dark); font-size: 14px; line-height: 1.6;">
                    <strong>Free deep clean</strong> service once per year.
                </p>
            </div>

        </div>

        <!-- PRICING & CTA -->
        <div style="background: var(--dark); border-radius: var(--radius); padding: 48px; text-align: center; box-shadow: var(--shadow);">
            <h2 style="font-family: var(--font-heading); font-size: 36px; color: var(--green); text-transform: uppercase; margin-bottom: 8px;">
                <?= $isMember ? 'Renew Your Membership' : 'Join Today' ?>
            </h2>
            <?php
            $mPrice  = getMembershipPrice();
            $mMonths = getMembershipDurationMonths();
            $durationText = ($mMonths === 12) ? 'year' : ($mMonths . ' months');
            ?>
            <p style="color: var(--gray); font-size: 20px; margin-bottom: 24px;">
                ₱ <?= number_format($mPrice, 2) ?> / <?= $durationText ?> – Unlock all member benefits
            </p>

            <?php if ($isLoggedIn): ?>
                <?php if ($isMember): ?>
                    <a href="register-membership.php?action=renew" class="btn btn--green" style="font-size: 20px; padding: 16px 48px;">
                        RENEW MEMBERSHIP
                    </a>
                    <?php if ($daysRemaining !== null && $daysRemaining > 30): ?>
                        <p style="color: var(--gray-dark); font-size: 14px; margin-top: 12px;">
                            Your membership is still active. You can renew early to extend it.
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="register-membership.php" class="btn btn--green" style="font-size: 20px; padding: 16px 48px;">
                        REGISTER NOW
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="auth/login.php?redirect=membership-details.php" class="btn btn--green" style="font-size: 20px; padding: 16px 48px;">
                    LOG IN TO REGISTER
                </a>
                <p style="color: var(--gray-dark); font-size: 14px; margin-top: 12px;">
                    Need an account? <a href="auth/register.php?redirect=membership-details.php" style="color: var(--green);">Sign up</a>
                </p>
            <?php endif; ?>
        </div>

        <p style="margin-top: 40px;">
            <a href="/syncro lab/index.php#services" style="color: var(--green); font-weight: 700;">&larr; Back to Services</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>