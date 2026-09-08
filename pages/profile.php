<?php
// pages/profile.php - User Profile with Chat

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to view your profile.');
    exit;
}

$user = getCurrentUser();
$pdo = getConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'customer';
$branchId = $_SESSION['branch_id'] ?? 0;

// Get branches for chat (customers)
$branches = [];
if ($userRole === 'customer') {
    $stmt = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");
    $branches = $stmt->fetchAll();
}

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1100px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            My Profile
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 40px;">
            Welcome back, <?= htmlspecialchars($user['full_name']) ?>
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
            
            <!-- ==================== LEFT: PROFILE INFO ==================== -->
            <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow);">
                <h2 style="font-family: var(--font-heading); font-size: 24px; margin-bottom: 16px; text-transform: uppercase;">Account Details</h2>
                
                <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not set') ?></p>
                <p><strong>Role:</strong> <?= ucfirst($user['role']) ?></p>
                <p><strong>Member Since:</strong> <?= date('F d, Y', strtotime($user['created_at'])) ?></p>
                <?php if ($user['membership_expiry']): ?>
                    <p><strong>Membership Expires:</strong> <?= date('F d, Y', strtotime($user['membership_expiry'])) ?></p>
                <?php endif; ?>
                
                <p style="margin-top: 20px;">
                    <a href="orders.php" style="color: var(--green); font-weight: 600;">📦 View My Orders</a>
                </p>
                <?php if ($userRole === 'hq_admin' || $userRole === 'branch_manager'): ?>
                    <p style="margin-top: 8px;">
                        <a href="admin/dashboard.php" style="color: var(--green); font-weight: 600;">📊 Dashboard</a>
                    </p>
                <?php endif; ?>
            </div>

            <!-- ==================== RIGHT: CHAT ==================== -->
            <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow);">
                <h2 style="font-family: var(--font-heading); font-size: 24px; margin-bottom: 16px; text-transform: uppercase;">💬 Chat Support</h2>
                
                <?php if ($userRole === 'customer'): ?>
                    <!-- Customer: select branch to chat -->
                    <div style="margin-bottom: 16px;">
                        <label for="chat-branch" style="font-weight: 700; display: block; margin-bottom: 4px;">Select Branch:</label>
                        <select id="chat-branch" style="width: 100%; padding: 10px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                            <option value="">-- Select --</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= $branch['id'] ?>"><?= htmlspecialchars($branch['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="chat-messages-container" style="display: none;">
                <?php else: ?>
                    <!-- Manager/Admin: auto-load their branch -->
                    <div id="chat-messages-container">
                <?php endif; ?>
                    
                    <!-- Chat Messages -->
                    <div id="chat-messages" style="height: 280px; overflow-y: auto; padding: 12px; background: var(--light); border-radius: var(--radius); border: 1px solid var(--gray); margin-bottom: 12px;">
                        <p style="color: var(--gray-dark); text-align: center; padding: 40px 0; font-size: 14px;">
                            <?= $userRole === 'customer' ? 'Select a branch to start chatting.' : 'No messages yet.' ?>
                        </p>
                    </div>

                    <!-- Send Message -->
                    <form id="chat-form" style="display: flex; gap: 8px;">
                        <input type="text" id="chat-input" placeholder="Type a message..." 
                               style="flex: 1; padding: 10px 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 14px;">
                        <button type="submit" class="btn btn--green" style="height: 42px; font-size: 14px; padding: 0 16px;">Send</button>
                    </form>
                </div>
            </div>

        </div>

        <p style="margin-top: 32px;">
            <a href="../index.php" style="color: var(--green); font-weight: 700;">&larr; Back to Home</a>
        </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatBranch = document.getElementById('chat-branch');
    const messagesContainer = document.getElementById('chat-messages-container');
    const userId = <?= $userId ?>;
    const userRole = '<?= $userRole ?>';
    
    let currentBranch = 0;
    let lastMessageId = 0;
    let pollInterval = null;

    <?php if ($userRole !== 'customer' && $branchId > 0): ?>
        // Auto-load for managers/admins
        currentBranch = <?= $branchId ?>;
        messagesContainer.style.display = 'flex';
        messagesContainer.style.flexDirection = 'column';
        messagesContainer.style.flex = '1';
        loadMessages();
        pollInterval = setInterval(loadMessages, 3000);
    <?php endif; ?>

    // Branch selection (customers)
    if (chatBranch) {
        chatBranch.addEventListener('change', function() {
            currentBranch = parseInt(this.value);
            if (currentBranch > 0) {
                messagesContainer.style.display = 'flex';
                messagesContainer.style.flexDirection = 'column';
                messagesContainer.style.flex = '1';
                lastMessageId = 0;
                chatMessages.innerHTML = '';
                loadMessages();
                if (pollInterval) clearInterval(pollInterval);
                pollInterval = setInterval(loadMessages, 3000);
            } else {
                messagesContainer.style.display = 'none';
                clearInterval(pollInterval);
            }
        });
    }

    // Send message
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = chatInput.value.trim();
        if (!message || currentBranch <= 0) return;

        fetch('chat-send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'branch_id=' + encodeURIComponent(currentBranch) + '&message=' + encodeURIComponent(message)
        })
        .then(response => response.text())
        .then(data => {
            chatInput.value = '';
            loadMessages();
        })
        .catch(error => console.error('Error sending message:', error));
    });

    // Load messages
    function loadMessages() {
        if (currentBranch <= 0) return;

        fetch('chat-fetch.php?branch_id=' + encodeURIComponent(currentBranch) + '&last_id=' + encodeURIComponent(lastMessageId))
        .then(response => response.text())
        .then(html => {
            if (html.trim() !== '') {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const newMessages = tempDiv.querySelectorAll('.chat-msg');
                newMessages.forEach(function(msg) {
                    const id = msg.dataset.id || 0;
                    if (id > lastMessageId) {
                        lastMessageId = id;
                        chatMessages.appendChild(msg);
                    }
                });
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        })
        .catch(error => console.error('Error loading messages:', error));
    }

    // Initial scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
});
</script>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>