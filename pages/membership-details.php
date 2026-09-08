<?php
// pages/membership-details.php - Membership Details

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;
$isMember = false;

if ($isLoggedIn) {
    $isMember = isMembershipActive($userId);
}

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 60px 0; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1100px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Hero Section -->
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

        <!-- Membership Status -->
        <?php if ($isLoggedIn && $isMember): ?>
            <?php
            // Get membership dates
            $stmt = $pdo->prepare("SELECT membership_start, membership_expiry FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $dates = $stmt->fetch();
            ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 16px 24px; border-radius: var(--radius); border-left: 4px solid var(--green); margin-bottom: 40px; text-align: center;">
                <strong>✅ You are a member!</strong>
                <br>
                <span style="font-size: 14px;">
                    Valid from <?= date('F d, Y', strtotime($dates['membership_start'])) ?> 
                    to <?= date('F d, Y', strtotime($dates['membership_expiry'])) ?>
                </span>
            </div>
        <?php elseif ($isLoggedIn && !$isMember): ?>
            <div style="background: #fff3cd; color: #856404; padding: 16px 24px; border-radius: var(--radius); border-left: 4px solid #f0ad4e; margin-bottom: 40px; text-align: center;">
                <strong>⏳ Membership expired or inactive.</strong> Renew to continue enjoying benefits.
            </div>
        <?php endif; ?>


        <!-- Benefits Grid -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 48px;">
            
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 32px; text-align: center; box-shadow: var(--shadow);">
                <div style="font-size: 48px; margin-bottom: 16px;">⚡</div>
                <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin-bottom: 8px;">Fast Service</h3>
                <p style="color: var(--gray-dark); font-size: 14px; line-height: 1.6;">
                    Guaranteed <strong>24-hour repair turnaround</strong> on all services.
                </p>
            </div>

            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 32px; text-align: center; box-shadow: var(--shadow);">
                <div style="font-size: 48px; margin-bottom: 16px;">🚴</div>
                <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin-bottom: 8px;">Free Bike Fit</h3>
                <p style="color: var(--gray-dark); font-size: 14px; line-height: 1.6;">
                    Annual <strong>laser bike fit</strong> session included with membership.
                </p>
            </div>

            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 32px; text-align: center; box-shadow: var(--shadow);">
                <div style="font-size: 48px; margin-bottom: 16px;">🧼</div>
                <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin-bottom: 8px;">Deep Clean</h3>
                <p style="color: var(--gray-dark); font-size: 14px; line-height: 1.6;">
                    <strong>Free deep clean</strong> service once per year.
                </p>
            </div>

            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 32px; text-align: center; box-shadow: var(--shadow);">
                <div style="font-size: 48px; margin-bottom: 16px;">💰</div>
                <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin-bottom: 8px;">10% Discount</h3>
                <p style="color: var(--gray-dark); font-size: 14px; line-height: 1.6;">
                    <strong>10% discount</strong> on all service labor costs.
                </p>
            </div>
        </div>

        <!-- Pricing & CTA -->
        <div style="background: var(--dark); border-radius: var(--radius); padding: 48px; text-align: center; box-shadow: var(--shadow);">
            <h2 style="font-family: var(--font-heading); font-size: 36px; color: var(--green); text-transform: uppercase; margin-bottom: 8px;">
                Join Today
            </h2>
            <p style="color: var(--gray); font-size: 20px; margin-bottom: 24px;">
                ₱ 1,500 / year – Unlock all member benefits
            </p>
            
            <?php if ($isLoggedIn): ?>
                <?php if ($isMember): ?>
                    <a href="register-membership.php?action=renew" class="btn btn--green" style="font-size: 20px; padding: 16px 48px;">
                        RENEW MEMBERSHIP
                    </a>
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

        <!-- Back to Services -->
        <p style="margin-top: 40px;">
            <a href="/syncro lab/index.php#services" style="color: var(--green); font-weight: 700;">&larr; Back to Services</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>