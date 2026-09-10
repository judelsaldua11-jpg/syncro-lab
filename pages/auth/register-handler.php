<?php
// pages/auth/register-handler.php
session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';
require_once __DIR__ . '/../../inc/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$result = validateUserRegistration($_POST);
$errors = $result['errors'];

if (!empty($errors)) {
    header('Location: register.php?error=' . urlencode(implode(' ', $errors)));
    exit;
}

$data = $result['data'];
$hashedPassword = hashPassword($data['password']);

$pdo = getConnection();

try {
    // Role is strictly enforced as 'customer' for all public registrations
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, phone, role) VALUES (?, ?, ?, ?, 'customer')");
    $stmt->execute([$data['email'], $hashedPassword, $data['full_name'], $data['phone']]);
    $userId = $pdo->lastInsertId();

    // Auto-login after registration with customer role
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $data['email'];
    $_SESSION['user_name'] = $data['full_name'];
    $_SESSION['role'] = 'customer';

    header('Location: ../../index.php');
    exit;
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // duplicate entry
        header('Location: register.php?error=Email already registered. Please log in.');
    } else {
        header('Location: register.php?error=Registration failed. Please try again.');
    }
    exit;
}