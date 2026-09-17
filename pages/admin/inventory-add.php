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

// If branch manager has no branch assigned, redirect with error
if (!$isAdmin && $managerBranchId <= 0) {
    header('Location: ../dashboard.php?error=Your account is not assigned to any branch. Please contact HQ Admin.');
    exit;
}

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
        $selectedProduct = null;
        foreach ($products as $product) {
            if ((int)$product['id'] === $productId) {
                $selectedProduct = $product;
                break;
            }
        }

        if (!$selectedProduct) {
            $error = 'Please select a valid product.';
        } else {
        $skuParts = array_values(array_filter(explode('-', strtoupper($selectedProduct['sku']))));
        $serialPrefix = count($skuParts) >= 2
            ? $skuParts[0] . '-' . $skuParts[1]
            : strtoupper($selectedProduct['sku']);

        // Split, normalize, and remove blank serial numbers.
        $serials = preg_split('/[\n,]+/', $serialNumbers);
        $serials = array_map(static function ($serial) {
            return strtoupper(trim(preg_replace('/\s+/', '', $serial)));
        }, $serials);
        $serials = array_values(array_filter($serials));

        foreach ($serials as &$serial) {
            if (preg_match('/^' . preg_quote($serialPrefix, '/') . '-(\d+)$/', $serial, $matches)) {
                $number = ltrim($matches[1], '0');
                $number = $number === '' ? '0' : $number;
                $serial = $serialPrefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
            }
        }
        unset($serial);
        
        if (empty($serials)) {
            $error = 'Please enter valid serial numbers.';
        } else {
            $addedCount = 0;
            $failedSerials = [];
            $seenSerials = [];
            $duplicateInputSerials = [];
            $serialsToInsert = [];

            foreach ($serials as $serial) {
                if (!preg_match('/^' . preg_quote($serialPrefix, '/') . '-\d+$/', $serial)) {
                    $failedSerials[] = $serial . " (must start with {$serialPrefix}- and end with numbers)";
                    continue;
                }
                if (isset($seenSerials[$serial])) {
                    $duplicateInputSerials[] = $serial;
                    continue;
                }
                $seenSerials[$serial] = true;
                $serialsToInsert[] = $serial;
            }

            $existingSerials = [];
            $placeholders = implode(',', array_fill(0, count($serialsToInsert), '?'));
            if ($placeholders !== '') {
                $stmt = $pdo->prepare("SELECT serial_number FROM inventory WHERE serial_number IN ($placeholders)");
                $stmt->execute($serialsToInsert);
                $existingSerials = array_map('strtoupper', $stmt->fetchAll(PDO::FETCH_COLUMN));
            }

            foreach ($existingSerials as $serial) {
                $failedSerials[] = $serial . ' (already exists)';
            }
            foreach ($duplicateInputSerials as $serial) {
                $failedSerials[] = $serial . ' (entered more than once)';
            }
            $serialsToInsert = array_values(array_diff($serialsToInsert, $existingSerials));

            if (empty($serialsToInsert)) {
                $error = 'No new stock was added. ' . implode(', ', $failedSerials) . '.';
            } else {
            
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO inventory (product_id, branch_id, serial_number, status) VALUES (?, ?, ?, 'in_stock')");
                
                foreach ($serialsToInsert as $serial) {
                    try {
                        $stmt->execute([$productId, $branchId, $serial]);
                        $addedCount++;
                    } catch (PDOException $e) {
                        if ($e->errorInfo[1] == 1062) { // Duplicate serial
                            $failedSerials[] = $serial . ' (already exists)';
                        } else {
                            $failedSerials[] = $serial . ' (could not be added)';
                        }
                    }
                }
                
                $pdo->commit();
                
                if ($addedCount > 0) {
                    $message = "Successfully added {$addedCount} item(s) to inventory.";
                    if (!empty($failedSerials)) {
                        $error = "Failed to add: " . implode(', ', $failedSerials);
                    }
                    
                    // Log the bulk add (ensure this function exists)
                    if (function_exists('logInventoryActivity')) {
                        logInventoryActivity(
                            $pdo,
                            null,
                            $productId,
                            $branchId,
                            'add',
                            null,
                            'in_stock',
                            $addedCount,
                            "Added $addedCount item(s)."
                        );
                    }
                } else {
                    $error = "Failed to add any items. " . implode(', ', $failedSerials);
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'The stock could not be added. Please check the serial numbers and try again.';
            }
            }
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

        <form method="POST" action="" style="background: #fff; padding: 32px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
            
            <div style="display: grid; gap: 20px;">
                <div>
                    <label for="product_id" style="font-weight: 700; display: block; margin-bottom: 4px;">Product *</label>
                    <select id="product_id" name="product_id" required
                            style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                        <option value="0">-- Select Product --</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?= $product['id'] ?>" data-serial-prefix="<?= htmlspecialchars(strtoupper(implode('-', array_slice(array_filter(explode('-', $product['sku'])), 0, 2))), ENT_QUOTES) ?>">
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
                    <div id="serial-prefix-hint" style="display: flex; align-items: center; gap: 8px; min-height: 42px; margin-bottom: 8px; padding: 8px 12px; background: var(--light); border: 1px solid var(--gray); border-radius: var(--radius); color: var(--gray-dark); font-size: 14px;">
                        <span>Product prefix:</span>
                        <strong id="serial-prefix-value" style="font-family: monospace; color: var(--dark);">Select a product</strong>
                        <span id="serial-prefix-example" style="margin-left: auto; font-family: monospace; color: var(--gray-dark);"></span>
                    </div>
                    <textarea id="serial_numbers" name="serial_numbers" rows="6" required inputmode="numeric"
                              style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 14px; font-family: monospace;"
                              placeholder="Select a product first, then enter numbers only"></textarea>
                    <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                        Enter one number per line or separate numbers with commas. The product prefix will be added automatically, for example: CS-BB + 006 = CS-BB-006.
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

<script>
const productSelect = document.getElementById('product_id');
const serialInput = document.getElementById('serial_numbers');
const serialPrefixValue = document.getElementById('serial-prefix-value');
const serialPrefixExample = document.getElementById('serial-prefix-example');
const inventoryForm = serialInput.closest('form');

function updateSerialHint() {
    const selected = this.options[this.selectedIndex];
    const prefix = selected.dataset.serialPrefix || '';
    serialPrefixValue.textContent = prefix || 'Select a product';
    serialPrefixExample.textContent = prefix ? `Example: ${prefix}-006` : '';
    serialInput.placeholder = prefix
        ? 'Enter numbers only, one per line or comma separated'
        : 'Select a product first, then enter numbers only';
}

productSelect.addEventListener('change', updateSerialHint);
inventoryForm.addEventListener('submit', function () {
    const selected = productSelect.options[productSelect.selectedIndex];
    const prefix = selected.dataset.serialPrefix || '';
    if (!prefix) return;

    serialInput.value = serialInput.value.split(/[\n,]+/).map(value => {
        const serial = value.trim().toUpperCase().replace(/\s+/g, '');
        if (/^\d+$/.test(serial)) {
            serial = serial.replace(/^0+(?=\d)/, '');
            return `${prefix}-${serial.padStart(3, '0')}`;
        }
        return serial;
    }).filter(Boolean).join('\n');
});
</script>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>