<?php
// pages/profile.php - User Profile (Placeholder)
session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to view your profile.');
    exit;
}

$user = getCurrentUser();
include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 60px 0; color: var(--dark); min-height: 60vh;">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            My Profile
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 40px;">
            Welcome back, <?= htmlspecialchars($user['full_name']) ?>
        </p>
        
        <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow); max-width: 600px;">
            <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not set') ?></p>
            <p><strong>Role:</strong> <?= ucfirst($user['role']) ?></p>
            <p><strong>Member Since:</strong> <?= date('F d, Y', strtotime($user['created_at'])) ?></p>
            <?php if ($user['membership_expiry']): ?>
                <p><strong>Membership:</strong> <?= date('F d, Y', strtotime($user['membership_expiry'])) ?></p>
            <?php endif; ?>
        </div>
        
        <p style="margin-top: 32px;">
            <a href="../index.php" style="color: var(--green); font-weight: 700;">&larr; Back to Home</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>