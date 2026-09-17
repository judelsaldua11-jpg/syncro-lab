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
$reason  = isset($_POST['reason'])   ? trim($_POST['reason'])   : '';

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

$pdo    = getConnection();
$userId = (int)$_SESSION['user_id'];

// ── Fetch order details BEFORE cancellation (for refund info in response)
$orderStmt = $pdo->prepare("
    SELECT o.total_amount, o.payment_method, o.payment_reference, o.payment_details
    FROM orders o
    WHERE o.id = ? AND o.user_id = ?
    LIMIT 1
");
$orderStmt->execute([$orderId, $userId]);
$orderInfo = $orderStmt->fetch(PDO::FETCH_ASSOC);

$cancellationNote = !empty($reason) ? "Customer cancelled: $reason" : "Customer cancelled order";

$result = cancelOrder($pdo, $orderId, $userId, $cancellationNote);

if ($result['success'] && $orderInfo) {
    $paymentMethod  = $orderInfo['payment_method'] ?? '';
    $isCOD          = ($paymentMethod === 'cash_on_delivery');
    $refundAmount   = (float)($orderInfo['total_amount'] ?? 0);

    // Build human-readable refund message
    if ($isCOD) {
        $result['refund_message'] = 'No payment was collected yet (Cash on Delivery). No refund is needed.';
        $result['refund_eligible'] = false;
    } else {
        $methodLabels = [
            'credit_card' => 'Credit Card',
            'gcash'       => 'GCash',
            'paymaya'     => 'PayMaya',
        ];
        $methodLabel = $methodLabels[$paymentMethod] ?? ucwords(str_replace('_', ' ', $paymentMethod));
        $result['refund_message'] = "You are eligible for a refund of ₱" . number_format($refundAmount, 2) . " to your {$methodLabel} account.";
        $result['refund_eligible'] = true;
    }

    $result['refund_amount']   = $refundAmount;
    $result['payment_method']  = $paymentMethod;
    $result['payment_details'] = $orderInfo['payment_details'] ?? null;
    $result['payment_ref']     = $orderInfo['payment_reference'] ?? null;
}

if (!$result['success']) {
    http_response_code(400);
}

// Append refund request ID to message for the front-end alert if available
if (!empty($result['refund_request_id'])) {
    $result['refund_request_id'] = (int)$result['refund_request_id'];
}

echo json_encode($result);
exit;

