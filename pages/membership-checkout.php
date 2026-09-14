<?php
// pages/membership-checkout.php
// Validates payment form, creates a pending payment record, shows confirmation page

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in.');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register-membership.php');
    exit;
}

$userId        = $_SESSION['user_id'];
$action        = $_POST['action']         ?? 'register';
$paymentMethod = $_POST['payment_method'] ?? '';
$walletNumber  = trim($_POST['wallet_number'] ?? '');
$cardName      = trim($_POST['card_name']     ?? '');
$cardNumber    = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
$cardExpiry    = trim($_POST['card_expiry']   ?? '');
$cardCvv       = trim($_POST['card_cvv']      ?? '');
$termsAgreed   = isset($_POST['terms']);

// ── Validate terms
if (!$termsAgreed) {
    header('Location: register-membership.php?error=You must agree to the terms and conditions.');
    exit;
}

// ── Validate payment method
if (!in_array($paymentMethod, ['gcash', 'maya', 'card'])) {
    header('Location: register-membership.php?error=Please select a valid payment method.');
    exit;
}

// ── Validate by method
if ($paymentMethod === 'gcash' || $paymentMethod === 'maya') {
    if (!preg_match('/^09\d{9}$/', $walletNumber)) {
        $p = urlencode('Please enter a valid 11-digit mobile number starting with 09.');
        header("Location: register-membership.php?error=$p" . ($action === 'renew' ? '&action=renew' : ''));
        exit;
    }
} elseif ($paymentMethod === 'card') {
    if (empty($cardName)) {
        $p = urlencode('Please enter the cardholder name.');
        header("Location: register-membership.php?error=$p" . ($action === 'renew' ? '&action=renew' : ''));
        exit;
    }
    if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
        $p = urlencode('Please enter a valid card number.');
        header("Location: register-membership.php?error=$p" . ($action === 'renew' ? '&action=renew' : ''));
        exit;
    }
    if (!preg_match('/^\d{2}\/\d{2}$/', $cardExpiry)) {
        $p = urlencode('Please enter a valid card expiry (MM/YY).');
        header("Location: register-membership.php?error=$p" . ($action === 'renew' ? '&action=renew' : ''));
        exit;
    }
    if (strlen($cardCvv) < 3) {
        $p = urlencode('Please enter a valid CVV.');
        header("Location: register-membership.php?error=$p" . ($action === 'renew' ? '&action=renew' : ''));
        exit;
    }
}

$pdo = getConnection();

$membershipPrice = getMembershipPrice();
$membershipDurationMonths = getMembershipDurationMonths();

// ── Pre-compute membership dates
$now        = new DateTime();
$startDate  = null;
$expiryDate = null;

$stmt = $pdo->prepare("SELECT membership_start, membership_expiry FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($action === 'renew' && $user['membership_expiry']) {
    $currentExpiry = new DateTime($user['membership_expiry']);
    if ($currentExpiry > $now) {
        $startDate  = $user['membership_start'] ? new DateTime($user['membership_start']) : $now;
        $expiryDate = clone $currentExpiry;
        $expiryDate->modify("+{$membershipDurationMonths} months");
    } else {
        $startDate  = $now;
        $expiryDate = clone $now;
        $expiryDate->modify("+{$membershipDurationMonths} months");
    }
} else {
    $startDate  = $now;
    $expiryDate = clone $now;
    $expiryDate->modify("+{$membershipDurationMonths} months");
}

// ── Generate reference number
$refNumber = strtoupper(substr($paymentMethod, 0, 1)) . date('YmdHis') . rand(100, 999);

// ── Store card last4 (for card payments)
$cardLast4 = ($paymentMethod === 'card' && strlen($cardNumber) >= 4)
    ? substr($cardNumber, -4)
    : null;

// ── Mask wallet number for display
$maskedWallet = '';
if (in_array($paymentMethod, ['gcash', 'maya']) && $walletNumber) {
    $maskedWallet = substr($walletNumber, 0, 3) . str_repeat('*', 5) . substr($walletNumber, -3);
}

// ── Invalidate any previous pending payments for this user
$pdo->prepare("UPDATE membership_payments SET status = 'failed' WHERE user_id = ? AND status = 'pending'")
    ->execute([$userId]);

// ── Insert new pending payment
$stmt = $pdo->prepare("
    INSERT INTO membership_payments
        (user_id, payment_method, wallet_number, card_last4, amount, reference_number, otp_code, status, action, membership_start, membership_expiry)
    VALUES (?, ?, ?, ?, ?, ?, '', 'pending', ?, ?, ?)
");
$stmt->execute([
    $userId,
    $paymentMethod,
    ($paymentMethod !== 'card') ? $walletNumber : null,
    $cardLast4,
    $membershipPrice,
    $refNumber,
    $action,
    $startDate->format('Y-m-d'),
    $expiryDate->format('Y-m-d'),
]);
$paymentId = $pdo->lastInsertId();

// ── Store in session for confirmation
$_SESSION['membership_payment'] = [
    'id'     => $paymentId,
    'method' => $paymentMethod,
    'ref'    => $refNumber,
    'start'  => $startDate->format('F d, Y'),
    'expiry' => $expiryDate->format('F d, Y'),
    'action' => $action,
];

// ─── Confirmation Page ───
include __DIR__ . '/../src/Views/layouts/header.php';

// Method display helpers
$methodMap = [
    'gcash' => ['label' => 'GCash', 'emoji' => '💙', 'color' => '#0070BA', 'bg' => '#e8f4ff'],
    'maya'  => ['label' => 'Maya',  'emoji' => '💚', 'color' => '#00a651', 'bg' => '#e6f9f0'],
    'card'  => ['label' => 'Card',  'emoji' => '💳', 'color' => '#444',    'bg' => '#f5f5f5'],
];
$m = $methodMap[$paymentMethod];
?>

<div style="padding: 60px 0; color: var(--dark); min-height: 70vh; background: var(--light);">
    <div style="max-width: 560px; margin: 0 auto; padding: 0 24px;">

        <!-- Header -->
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="font-size: 56px; margin-bottom: 12px;"><?= $m['emoji'] ?></div>
            <h1 style="font-family: var(--font-heading); font-size: 32px; text-transform: uppercase; color: <?= $m['color'] ?>; margin-bottom: 4px;">
                Confirm Payment
            </h1>
            <p style="color: var(--gray-dark); font-size: 16px;">
                Please review your order details before confirming.
            </p>
        </div>

        <!-- Payment Summary Card -->
        <div style="background: <?= $m['bg'] ?>; border: 2px solid <?= $m['color'] ?>; border-radius: var(--radius); padding: 24px 28px; margin-bottom: 28px;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="color: var(--gray-dark); font-size: 14px;">Reference No.</span>
                <strong style="font-size: 14px; letter-spacing: 0.5px; font-family: monospace;"><?= htmlspecialchars($refNumber) ?></strong>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="color: var(--gray-dark); font-size: 14px;">Payment Method</span>
                <span style="font-size: 14px; font-weight: 700;">
                    <?= $m['emoji'] ?> <?= $m['label'] ?>
                    <?php if ($paymentMethod !== 'card' && $maskedWallet): ?>
                        — <?= htmlspecialchars($maskedWallet) ?>
                    <?php elseif ($paymentMethod === 'card' && $cardLast4): ?>
                        •••• <?= htmlspecialchars($cardLast4) ?>
                    <?php endif; ?>
                </span>
            </div>

            <?php if ($paymentMethod === 'card' && $cardName): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="color: var(--gray-dark); font-size: 14px;">Cardholder</span>
                <span style="font-size: 14px;"><?= htmlspecialchars(strtoupper($cardName)) ?></span>
            </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="color: var(--gray-dark); font-size: 14px;">Membership</span>
                <span style="font-size: 14px;">
                    <?= htmlspecialchars($startDate->format('M d, Y')) ?> – <?= htmlspecialchars($expiryDate->format('M d, Y')) ?>
                </span>
            </div>

            <div style="border-top: 1px solid <?= $m['color'] ?>; padding-top: 14px; display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                <strong style="font-size: 15px;">Total Amount</strong>
                <strong style="font-size: 22px; color: <?= $m['color'] ?>;">₱ <?= number_format($membershipPrice, 2) ?></strong>
            </div>
        </div>

        <!-- Confirmation Note -->
        <div style="background: #fff8e1; border: 1px solid #f9a825; border-radius: var(--radius); padding: 14px 18px; margin-bottom: 28px; font-size: 13px; color: #6d4c00;">
            <strong>📋 Please confirm:</strong> By clicking <em>Confirm &amp; Activate</em>, you agree to the membership terms and authorize the payment of <strong>₱ <?= number_format($membershipPrice, 2) ?></strong> via <?= $m['label'] ?>.
        </div>

        <!-- Confirm Form -->
        <form method="POST" action="membership-handler.php" id="confirmForm">
            <input type="hidden" name="payment_id" value="<?= $paymentId ?>">
            <input type="hidden" name="confirm" value="1">

            <button type="submit" id="confirmBtn" class="btn btn--green"
                    style="width: 100%; height: 52px; font-size: 17px; justify-content: center;"
                    onclick="return confirmPayment()">
                ✅ CONFIRM &amp; ACTIVATE MEMBERSHIP
            </button>
        </form>

        <div style="text-align: center; margin-top: 16px;">
            <a href="register-membership.php<?= $action === 'renew' ? '?action=renew' : '' ?>"
               style="color: var(--gray-dark); font-size: 13px; text-decoration: underline;">
                ← Change payment method / Go back
            </a>
        </div>

    </div>
</div>

<script>
function confirmPayment() {
    return confirm('Confirm payment of ₱<?= number_format($membershipPrice, 2) ?> via <?= addslashes($m['label']) ?>?\n\nThis will activate your SYNCRO LAB membership.');
}
</script>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>
