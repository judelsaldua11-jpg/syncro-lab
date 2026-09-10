<?php
// pages/cancel-order.php - Customer Order Cancellation Endpoint

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to cancel an order.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

$pdo = getConnection();
$userId = (int)$_SESSION['user_id'];
$cancellationNote = !empty($reason) ? "User cancelled: $reason" : "User cancelled order";

$result = cancelOrder($pdo, $orderId, $userId, $cancellationNote);

if (!$result['success']) {
    http_response_code(400);
}

echo json_encode($result);
exit;
