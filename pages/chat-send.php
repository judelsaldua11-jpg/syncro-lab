<?php
// pages/chat-send.php - Send Chat Message (AJAX)

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check login
if (!isLoggedIn()) {
    http_response_code(401);
    echo 'Please log in to send messages.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$userId = $_SESSION['user_id'];
$branchId = (int)($_POST['branch_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($branchId <= 0 || empty($message)) {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

// Determine sender type
$role = getUserRole();
$senderType = 'customer';
if ($role === 'branch_manager' || $role === 'hq_admin') {
    $senderType = 'manager';
}
if ($role === 'hq_admin') {
    $senderType = 'admin';
}

$pdo = getConnection();

try {
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (user_id, branch_id, message, sender_type)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $branchId, $message, $senderType]);
    echo 'success';
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Database error.';
}