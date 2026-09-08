<?php
// pages/membership-handler.php - Process Membership Registration

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: /syncro lab/pages/auth/login.php?error=Please log in to register for membership.');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /syncro lab/pages/membership-details.php');
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? 'register';

// Check terms
if (!isset($_POST['terms'])) {
    header('Location: /syncro lab/pages/register-membership.php?error=You must agree to the terms and conditions.');
    exit;
}

$pdo = getConnection();

try {
    // Get current membership expiry
    $stmt = $pdo->prepare("SELECT membership_start, membership_expiry FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $now = new DateTime();
    $startDate = null;
    $expiryDate = null;
    
    if ($action === 'renew' && $user['membership_expiry']) {
        $currentExpiry = new DateTime($user['membership_expiry']);
        if ($currentExpiry > $now) {
            // Renew before expiry: extend from current expiry
            $startDate = $user['membership_start'] ? new DateTime($user['membership_start']) : $now;
            $expiryDate = clone $currentExpiry;
            $expiryDate->modify('+1 year');
        } else {
            // Expired: start fresh
            $startDate = $now;
            $expiryDate = clone $now;
            $expiryDate->modify('+1 year');
        }
    } else {
        // New registration
        $startDate = $now;
        $expiryDate = clone $now;
        $expiryDate->modify('+1 year');
    }
    
    // Update user's membership
    $stmt = $pdo->prepare("UPDATE users SET membership_start = ?, membership_expiry = ? WHERE id = ?");
    $stmt->execute([$startDate->format('Y-m-d'), $expiryDate->format('Y-m-d'), $userId]);
    
    $message = $action === 'renew' ? 'renewed' : 'registered';
    $successMsg = "Membership {$message} successfully! Valid from " . $startDate->format('F d, Y') . " to " . $expiryDate->format('F d, Y');
    
    header('Location: /syncro lab/pages/membership-details.php?success=' . urlencode($successMsg));
    exit;
    
} catch (PDOException $e) {
    header('Location: /syncro lab/pages/register-membership.php?error=Failed to process membership. Please try again.');
    exit;
}