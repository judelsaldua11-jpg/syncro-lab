<?php
// pages/admin/inventory-add.php - Add Stock

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn()) {
    header('Location: ../auth/login.php?error=Please log in to access the inventory.');
    exit;
}

$role = getUserRole();
if ($role !== 'hq_admin' && $role !== 'branch_manager') {
    header('Location: ../../index.php?error=You do not have permission to access this page.');
    exit;
}

$isAdmin = ($role === 'hq_admin');
$managerBranchId = $_SESSION['branch_id'] ?? 0;

$message = '';
$error = '';
$pdo = getConnection();

// Get products for dropdown
$stmt = $pdo->query("
    SELECT id, name, sku 
    FROM products 
    WHERE is_active = 1 AND is_phase_out = 0 
    ORDER BY name
");
$products = $stmt->fetchAll();

// Get branches for dropdown (HQ only)
$branches = [];
if ($isAdmin) {
    $stmt = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");
    $branches = $stmt->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $branchId = (int)($_POST['branch_id'] ?? 0);
    $serialNumbers = trim($_POST['serial_numbers'] ?? '');
    
    // Validate
    if ($productId <= 0) {
        $error = 'Please select a product.';
    } elseif (!$isAdmin && $managerBranchId > 0) {
        // Force branch for managers
        $branchId = $managerBranchId;
    } elseif ($branchId <= 0) {
        $error = 'Please select a branch.';
    } elseif (empty($serialNumbers)) {
        $error = 'Please enter at least one serial number.';
    } else {
        // Split serial numbers by newline or comma
        $serials = preg_split('/[\n,]+/', $serialNumbers);
        $serials = array_map('trim', $serials);
        $serials = array_filter($serials);
        
        if (empty($serials)) {
            $error = 'Please enter valid serial numbers.';
        } else {
            $addedCount = 0;
            $failedSerials = [];
            
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO inventory (product_id, branch_id, serial_number, status) VALUES (?, ?, ?, 'in_stock')");
                
                foreach ($serials as $serial) {
                    try {
                        $stmt->execute([$productId, $branchId, $serial]);
                        $addedCount++;
                    } catch (PDOException $e) {
                        if ($e->errorInfo[1] == 1062) { // Duplicate serial
                            $failedSerials[] = $serial . ' (duplicate)';
                        } else {
                            $failedSerials[] = $serial . ' (' . $e->getMessage() . ')';
                        }
                    }
                }
                
                $pdo->commit();
                
                if ($addedCount > 0) {
                    $message = "Successfully added {$addedCount} item(s) to inventory.";
                    if (!empty($failedSerials)) {
                        $error = "Failed to add: " . implode(', ', $failedSerials);
                    }
                } else {
                    $error = "Failed to add any items. " . implode(', ', $failedSerials);
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 36px; text-transform: uppercase; margin-bottom: 8px;">
            Add Stock
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Add new items to your inventory by entering serial numbers.
        </p>

        <!-- Messages -->
        <?php if ($message): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid var(--green);">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="" style="background: #fff; padding: 32px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
            
            <div style="display: grid; gap: 20px;">
                <div>
                    <label for="product_id" style="font-weight: 700; display: block; margin-bottom: 4px;">Product *</label>
                    <select id="product_id" name="product_id" required
                            style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                        <option value="0">-- Select Product --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>">
                                <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($isAdmin): ?>
                    <div>
                        <label for="branch_id" style="font-weight: 700; display: block; margin-bottom: 4px;">Branch *</label>
                        <select id="branch_id" name="branch_id" required
                                style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                            <option value="0">-- Select Branch --</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= $branch['id'] ?>">
                                    <?= htmlspecialchars($branch['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div>
                        <label style="font-weight: 700; display: block; margin-bottom: 4px;">Branch</label>
                        <p style="padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); background: var(--light); color: var(--gray-dark);">
                            <?= htmlspecialchars($_SESSION['branch_name'] ?? 'Your Branch') ?>
                            <input type="hidden" name="branch_id" value="<?= $managerBranchId ?>">
                        </p>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="serial_numbers" style="font-weight: 700; display: block; margin-bottom: 4px;">Serial Numbers *</label>
                    <textarea id="serial_numbers" name="serial_numbers" rows="6" required
                              style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 14px; font-family: monospace;"
                              placeholder="Enter one serial number per line or comma separated&#10;Example:&#10;SN-001&#10;SN-002&#10;SN-003"></textarea>
                    <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                        Enter one serial number per line or separate with commas. Each serial number must be unique.
                    </p>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 8px;">
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        ADD TO INVENTORY
                    </button>
                    <a href="inventory.php" class="btn btn--outline" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        CANCEL
                    </a>
                </div>
            </div>
        </form>

    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>