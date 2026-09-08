<?php
// pages/warranty.php - File a Warranty Claim

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to file a warranty claim.');
    exit;
}

$pdo = getConnection();
$userId = $_SESSION['user_id'];

// Get user's orders with items (for dropdown)
$stmt = $pdo->prepare("
    SELECT 
        oi.id AS order_item_id,
        o.id AS order_id,
        p.name AS product_name,
        i.serial_number
    FROM order_items oi
    JOIN inventory i ON oi.inventory_id = i.id
    JOIN products p ON i.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
");
$stmt->execute([$userId]);
$orderItems = $stmt->fetchAll();

// Get error/success messages
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 60px 0; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            Warranty Claim
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Submit a warranty claim for a product you purchased.
        </p>

        <?php if ($error): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid var(--green);">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($orderItems)): ?>
            <div style="text-align: center; padding: 60px 0; background: #fff; border-radius: var(--radius); border: 1px solid var(--gray);">
                <p style="font-size: 22px; color: var(--gray-dark);">You haven't purchased any products yet.</p>
                <a href="shop.php" class="btn btn--green" style="margin-top: 20px;">Start Shopping</a>
            </div>
        <?php else: ?>
            <form method="POST" action="warranty-handler.php" enctype="multipart/form-data" style="background: #fff; padding: 32px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
                
                <div style="display: grid; gap: 20px;">
                    
                    <div>
                        <label for="order_item_id" style="font-weight: 700; display: block; margin-bottom: 4px;">Select Product *</label>
                        <select id="order_item_id" name="order_item_id" required
                                style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                            <option value="">-- Select a product --</option>
                            <?php foreach ($orderItems as $item): ?>
                                <option value="<?= $item['order_item_id'] ?>">
                                    <?= htmlspecialchars($item['product_name']) ?> (SN: <?= htmlspecialchars($item['serial_number']) ?>) - Order #<?= $item['order_id'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="issue_description" style="font-weight: 700; display: block; margin-bottom: 4px;">Issue Description *</label>
                        <textarea id="issue_description" name="issue_description" rows="5" required
                                  style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; font-family: inherit;"
                                  placeholder="Describe the issue with the product..."></textarea>
                    </div>

                    <div>
                        <label for="refund_option" style="font-weight: 700; display: block; margin-bottom: 4px;">Preferred Resolution *</label>
                        <select id="refund_option" name="refund_option" required
                                style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                            <option value="repair">Repair</option>
                            <option value="replacement">Replacement</option>
                            <option value="store_credit">Store Credit</option>
                            <option value="cash_back">Cash Back</option>
                        </select>
                    </div>

                    <div>
                        <label for="attachments" style="font-weight: 700; display: block; margin-bottom: 4px;">Attachments (Photos)</label>
                        <input type="file" id="attachments" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                               style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                        <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                            Max 5 files, 5MB each. Allowed: JPG, PNG, GIF, WebP, PDF.
                        </p>
                    </div>

                    <div style="display: flex; gap: 16px; margin-top: 8px;">
                        <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;">
                            SUBMIT CLAIM
                        </button>
                        <a href="../index.php" class="btn btn--outline" style="height: 48px; font-size: 16px; padding: 0 32px;">
                            CANCEL
                        </a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>