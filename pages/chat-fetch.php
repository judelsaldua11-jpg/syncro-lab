<?php
// pages/chat-fetch.php - Fetch New Chat Messages (AJAX)

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check login
if (!isLoggedIn()) {
    http_response_code(401);
    exit;
}

$branchId = (int)($_GET['branch_id'] ?? 0);
$lastId = (int)($_GET['last_id'] ?? 0);

if ($branchId <= 0) {
    http_response_code(400);
    exit;
}

$userId = $_SESSION['user_id'];

$pdo = getConnection();

// Get new messages
$stmt = $pdo->prepare("
    SELECT cm.*, u.full_name AS sender_name
    FROM chat_messages cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.branch_id = ? AND cm.id > ?
    ORDER BY cm.created_at ASC
");
$stmt->execute([$branchId, $lastId]);
$messages = $stmt->fetchAll();

if (empty($messages)) {
    exit;
}

// Output each message as HTML
foreach ($messages as $msg) {
    $isOwn = ($msg['user_id'] == $userId);
    $align = $isOwn ? 'text-align: right;' : '';
    $bg = $isOwn ? 'background: var(--green); color: var(--dark);' : 'background: var(--gray); color: #fff;';
    ?>
    <div class="chat-msg" data-id="<?= $msg['id'] ?>" style="margin-bottom: 12px; <?= $align ?>">
        <div style="display: inline-block; max-width: 80%; padding: 10px 16px; border-radius: var(--radius); <?= $bg ?>">
            <p style="margin: 0; font-size: 14px;"><?= htmlspecialchars($msg['message']) ?></p>
            <p style="margin: 4px 0 0; font-size: 11px; opacity: 0.7;">
                <?= htmlspecialchars($msg['sender_name']) ?> · <?= date('h:i A', strtotime($msg['created_at'])) ?>
            </p>
        </div>
    </div>
    <?php
}