<?php
// pages/membership-handler.php - Process Membership Confirmation & Activation

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// ── Auth guard
if (!isLoggedIn()) {
    header('Location: /syncro lab/pages/auth/login.php?error=Please log in to register for membership.');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /syncro lab/pages/membership-details.php');
    exit;
}

$userId    = $_SESSION['user_id'];
$paymentId = (int)($_POST['payment_id'] ?? 0);
$confirmed = isset($_POST['confirm']) && $_POST['confirm'] === '1';

if (!$confirmed) {
    header('Location: /syncro lab/pages/register-membership.php?error=Payment was not confirmed. Please try again.');
    exit;
}

// ── Retrieve pending payment from DB
$pdo  = getConnection();
$stmt = $pdo->prepare("
    SELECT * FROM membership_payments
    WHERE id = ? AND user_id = ? AND status = 'pending'
    LIMIT 1
");
$stmt->execute([$paymentId, $userId]);
$payment = $stmt->fetch();

if (!$payment) {
    header('Location: /syncro lab/pages/register-membership.php?error=Payment session not found or already processed. Please try again.');
    exit;
}

// ── Confirmed — begin transaction
try {
    $pdo->beginTransaction();

    $startDate  = new DateTime($payment['membership_start']);
    $expiryDate = new DateTime($payment['membership_expiry']);

    // 1. Activate user's membership
    $stmt = $pdo->prepare("
        UPDATE users
        SET membership_start   = ?,
            membership_expiry  = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $startDate->format('Y-m-d'),
        $expiryDate->format('Y-m-d'),
        $userId,
    ]);

    // 2. Mark payment as verified
    $stmt = $pdo->prepare("
        UPDATE membership_payments
        SET status      = 'verified',
            verified_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$paymentId]);

    $pdo->commit();

    // 3. Clear payment session
    unset($_SESSION['membership_payment']);

    $action     = $payment['action'];
    $methodMap  = ['gcash' => 'GCash', 'maya' => 'Maya', 'card' => 'Card'];
    $methodName = $methodMap[$payment['payment_method']] ?? ucfirst($payment['payment_method']);
    $refNum     = $payment['reference_number'];

    $successMsg = sprintf(
        'Membership %s successfully via %s! Ref: %s. Valid from %s to %s.',
        $action === 'renew' ? 'renewed' : 'registered',
        $methodName,
        $refNum,
        $startDate->format('F d, Y'),
        $expiryDate->format('F d, Y')
    );

    header('Location: /syncro lab/pages/membership-details.php?success=' . urlencode($successMsg));
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: /syncro lab/pages/register-membership.php?error=Failed to activate membership. Please try again.');
    exit;
}