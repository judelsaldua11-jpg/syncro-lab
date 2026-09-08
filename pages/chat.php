<?php
// pages/chat.php - Chat System

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: /syncro lab/pages/auth/login.php?error=Please log in to use chat.');
    exit;
}

$pdo = getConnection();
$userId = $_SESSION['user_id'];
$role = getUserRole();
$branchId = $_SESSION['branch_id'] ?? 0;

// Get branches for dropdown (customers) or list (managers)
$branches = getBranches();

// Determine branch to chat with
$selectedBranch = isset($_GET['branch']) ? (int)$_GET['branch'] : 0;
if ($role === 'branch_manager' && $branchId > 0) {
    $selectedBranch = $branchId; // Force their branch
}

// Get messages (initial load)
$messages = [];
if ($selectedBranch > 0) {
    $stmt = $pdo->prepare("
        SELECT cm.*, u.full_name AS sender_name
        FROM chat_messages cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.branch_id = ?
        ORDER BY cm.created_at ASC
        LIMIT 100
    ");
    $stmt->execute([$selectedBranch]);
    $messages = $stmt->fetchAll();
}

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1100px; margin: 0 auto; padding: 0 40px;">

        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            Chat
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            <?php if ($role === 'customer'): ?>
                Chat with our team about your orders or questions.
            <?php else: ?>
                Respond to customer inquiries.
            <?php endif; ?>
        </p>

        <?php if ($role === 'customer' && empty($branches)): ?>
            <p style="color: var(--gray-dark);">No branches available for chat.</p>
        <?php elseif ($role === 'customer'): ?>
            <!-- Customer Chat View -->
            <div style="display: grid; gap: 24px;">
                <div>
                    <label for="branch_select" style="font-weight: 700; display: block; margin-bottom: 4px;">Select Branch to Chat:</label>
                    <select id="branch_select" onchange="window.location.href='/syncro lab/pages/chat.php?branch=' + this.value" style="width: 100%; max-width: 400px; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                        <option value="0">-- Select Branch --</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch['id'] ?>" <?= $selectedBranch == $branch['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($branch['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($selectedBranch > 0): ?>
            <!-- Chat Messages Area -->
            <div id="chat-container" style="margin-top: 32px; background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                
                <!-- Messages -->
                <div id="chat-messages" style="padding: 20px; height: 400px; overflow-y: auto; border-bottom: 1px solid var(--gray);">
                    <?php if (empty($messages)): ?>
                        <p style="color: var(--gray-dark); text-align: center; padding: 40px 0;">No messages yet. Start the conversation!</p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="chat-msg" data-id="<?= $msg['id'] ?>" style="margin-bottom: 12px; <?= $msg['user_id'] == $userId ? 'text-align: right;' : '' ?>">
                                <div style="display: inline-block; max-width: 80%; padding: 10px 16px; border-radius: var(--radius); <?= $msg['user_id'] == $userId ? 'background: var(--green); color: var(--dark);' : 'background: var(--gray); color: #fff;' ?>">
                                    <p style="margin: 0; font-size: 14px;"><?= htmlspecialchars($msg['message']) ?></p>
                                    <p style="margin: 4px 0 0; font-size: 11px; opacity: 0.7;">
                                        <?= htmlspecialchars($msg['sender_name']) ?> · <?= date('h:i A', strtotime($msg['created_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Send Message Form -->
                <form id="chat-form" style="padding: 16px 20px; display: flex; gap: 12px;">
                    <input type="hidden" name="branch_id" value="<?= $selectedBranch ?>">
                    <input type="hidden" name="user_id" value="<?= $userId ?>">
                    <input type="text" id="message-input" name="message" placeholder="Type your message..." required
                           style="flex: 1; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 24px;">Send</button>
                </form>
            </div>
        <?php endif; ?>

        <p style="margin-top: 32px;">
            <a href="/syncro lab/index.php" style="color: var(--green); font-weight: 700;">&larr; Back to Home</a>
        </p>

    </div>
</div>

<script>
// Chat AJAX Functions
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const branchId = document.querySelector('input[name="branch_id"]')?.value;

    if (!chatForm || !branchId) return;

    // Send message via AJAX
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = messageInput.value.trim();
        if (!message) return;

        fetch('/syncro lab/pages/chat-send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'branch_id=' + encodeURIComponent(branchId) + '&message=' + encodeURIComponent(message)
        })
        .then(response => response.text())
        .then(data => {
            messageInput.value = '';
            loadMessages();
        })
        .catch(error => console.error('Error sending message:', error));
    });

    // Load new messages via AJAX (polling)
    function loadMessages() {
        const lastMsg = chatMessages.querySelector('.chat-msg:last-child');
        const lastId = lastMsg ? lastMsg.dataset.id : 0;

        fetch('/syncro lab/pages/chat-fetch.php?branch_id=' + encodeURIComponent(branchId) + '&last_id=' + encodeURIComponent(lastId))
        .then(response => response.text())
        .then(html => {
            if (html.trim() !== '') {
                chatMessages.insertAdjacentHTML('beforeend', html);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        })
        .catch(error => console.error('Error loading messages:', error));
    }

    // Poll every 3 seconds
    if (chatMessages) {
        setInterval(loadMessages, 3000);
        // Initial scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Also load when branch changes (page reload already)
});
</script>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>