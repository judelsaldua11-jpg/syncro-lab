<?php
// pages/register-membership.php - Membership Registration

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to register for membership.');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getCurrentUser();
$isRenewal = isset($_GET['action']) && $_GET['action'] === 'renew';
$isActiveMember = isMembershipActive($userId);

// If already a member and not renewing, redirect to details
if ($isActiveMember && !$isRenewal) {
    header('Location: membership-details.php?message=You are already a member.');
    exit;
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 60px 0; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            <?= $isRenewal ? 'Renew Membership' : 'Register for Membership' ?>
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            <?= $isRenewal ? 'Extend your membership for another year.' : 'Join SYNCRO LAB Membership today!' ?>
        </p>

        <?php if ($error): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid var(--green);">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="membership-handler.php" style="background: #fff; padding: 32px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
            
            <input type="hidden" name="action" value="<?= $isRenewal ? 'renew' : 'register' ?>">
            
            <div style="display: grid; gap: 20px;">
                
                <!-- User Info (Read-only) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="font-weight: 700; display: block; margin-bottom: 4px;">Full Name</label>
                        <p style="padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); background: var(--light); color: var(--gray-dark);">
                            <?= htmlspecialchars($user['full_name']) ?>
                        </p>
                    </div>
                    <div>
                        <label style="font-weight: 700; display: block; margin-bottom: 4px;">Email</label>
                        <p style="padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); background: var(--light); color: var(--gray-dark);">
                            <?= htmlspecialchars($user['email']) ?>
                        </p>
                    </div>
                </div>

                <!-- Membership Benefits Summary -->
                <div style="background: var(--light); padding: 16px 20px; border-radius: var(--radius);">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 8px;">Membership Benefits</h3>
                    <ul style="margin: 0; padding-left: 20px; color: var(--gray-dark); line-height: 1.8;">
                        <li>✅ Guaranteed 24-hour repair turnaround</li>
                        <li>✅ Annual free laser bike fit</li>
                        <li>✅ Free deep clean once per year</li>
                        <li>✅ 10% discount on service labor</li>
                    </ul>
                </div>

                <!-- Price & Terms -->
                <div style="background: var(--dark); color: var(--light); padding: 20px; border-radius: var(--radius); text-align: center;">
                    <p style="font-size: 14px; color: var(--gray);">
                        <?= $isRenewal ? 'Renewal Fee' : 'Registration Fee' ?>
                    </p>
                    <p style="font-family: var(--font-heading); font-size: 36px; color: var(--green);">₱ 1,500</p>
                    <p style="font-size: 14px; color: var(--gray);">Valid for 12 months</p>
                    
                    <?php if ($isRenewal && $isActiveMember): ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT membership_expiry FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $currentExpiry = $stmt->fetchColumn();
                        
                        $now = new DateTime();
                        $expiryDate = new DateTime($currentExpiry);
                        
                        if ($expiryDate > $now) {
                            $newExpiry = clone $expiryDate;
                            $newExpiry->modify('+1 year');
                            $periodText = "Extends to: " . $newExpiry->format('F d, Y');
                        } else {
                            $newExpiry = new DateTime();
                            $newExpiry->modify('+1 year');
                            $periodText = "New expiry: " . $newExpiry->format('F d, Y');
                        }
                        ?>
                        <p style="font-size: 13px; color: var(--green); margin-top: 8px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 8px;">
                            📅 <?= $periodText ?>
                        </p>
                    <?php endif; ?>
                </div>


                <!-- Terms & Conditions -->
                <div>
                    <details style="margin: 8px 0 16px; cursor: pointer;">
                        <summary style="font-weight: 600; color: var(--green);">📜 View Terms & Conditions</summary>
                        <div style="padding: 16px; background: var(--light); border-radius: var(--radius); margin-top: 8px; max-height: 200px; overflow-y: auto; font-size: 14px; color: var(--gray-dark); line-height: 1.6;">
                            <p><strong>1. Membership Duration</strong><br>
                            Membership is valid for 12 months from the date of registration.</p>
                            
                            <p><strong>2. Benefits</strong><br>
                            Members receive: guaranteed 24-hour repair turnaround, annual free laser bike fit, free deep clean once per year, and 10% discount on service labor.</p>
                            
                            <p><strong>3. Renewal</strong><br>
                            Membership can be renewed before expiry. Renewal extends membership by 12 months from the current expiry date.</p>
                            
                            <p><strong>4. Cancellation</strong><br>
                            Membership fees are non-refundable. Members may cancel but will not receive a refund.</p>
                            
                            <p><strong>5. Changes</strong><br>
                            SYNCRO LAB reserves the right to modify benefits with prior notice to members.</p>
                        </div>
                    </details>
                </div>

                <div>
                    <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="terms" required>
                        I agree to the membership terms and conditions
                    </label>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 8px;">
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        <?= $isRenewal ? 'RENEW MEMBERSHIP' : 'REGISTER NOW' ?>
                    </button>
                    <a href="membership-details.php" class="btn btn--outline" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        CANCEL
                    </a>
                </div>
            </div>
        </form>

    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>