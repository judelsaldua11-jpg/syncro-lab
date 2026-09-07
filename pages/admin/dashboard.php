<?php
// pages/admin/dashboard.php - Admin Dashboard (Placeholder)
session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php?error=Please log in to access the dashboard.');
    exit;
}

$role = getUserRole();
if ($role !== 'hq_admin' && $role !== 'branch_manager') {
    header('Location: ../profile.php?error=You do not have permission to access the dashboard.');
    exit;
}

$user = getCurrentUser();
include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 60px 0; color: var(--dark); min-height: 60vh;">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            Dashboard
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 40px;">
            Welcome, <?= htmlspecialchars($user['full_name']) ?> 
            (<?= $role === 'hq_admin' ? 'HQ Admin' : 'Branch Manager' ?>)
        </p>
        
        <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow);">
            <p>Dashboard content coming soon...</p>
            <p style="color: var(--gray-dark); font-size: 14px; margin-top: 12px;">
                This will show sales stats, inventory alerts, and booking management.
            </p>
        </div>
        
        <p style="margin-top: 32px;">
            <a href="../../index.php" style="color: var(--green); font-weight: 700;">&larr; Back to Home</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>