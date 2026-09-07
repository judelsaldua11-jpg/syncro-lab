<?php
// pages/auth/login-handler.php
session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: login.php?error=Please enter both email and password.');
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare("SELECT id, email, full_name, password_hash, role, branch_id FROM users WHERE email = ? AND is_active = 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !verifyPassword($password, $user['password_hash'])) {
    header('Location: login.php?error=Invalid email or password.');
    exit;
}

// Login successful - store basic user info
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['role'] = $user['role'];
$_SESSION['branch_id'] = $user['branch_id'];

// If user is a branch manager, get their branch name
if ($user['role'] === 'branch_manager' && !empty($user['branch_id'])) {
    $stmt2 = $pdo->prepare("SELECT name FROM branches WHERE id = ? AND is_active = 1");
    $stmt2->execute([$user['branch_id']]);
    $branch = $stmt2->fetch();
    $_SESSION['branch_name'] = $branch['name'] ?? 'Your Branch';
} else {
    $_SESSION['branch_name'] = '';
}

header('Location: ../../index.php');
exit;