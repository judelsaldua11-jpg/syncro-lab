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

// Login successful
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['role'] = $user['role'];
$_SESSION['branch_id'] = $user['branch_id'];

header('Location: ../../index.php');
exit;