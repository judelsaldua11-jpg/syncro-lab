<?php
// pages/admin/inventory.php - Inventory Management

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
$branchId = $_SESSION['branch_id'] ?? 0;

$message = '';
$error = '';
$pdo = getConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $inventoryId = (int)$_POST['inventory_id'];
    $newStatus = $_POST['status'] ?? '';

    $allowedStatuses = ['in_stock', 'reserved', 'sold', 'returned_qc', 'defective_warranty'];
    
    if (!in_array($newStatus, $allowedStatuses)) {
        $error = 'Invalid status.';
    } else {
        try {
            // Get the current inventory record to log changes
            $stmt = $pdo->prepare("SELECT product_id, branch_id, status FROM inventory WHERE id = ?");
            $stmt->execute([$inventoryId]);
            $inv = $stmt->fetch();
            if (!$inv) {
                $error = 'Inventory item not found.';
            } else {
                // If not admin, verify this inventory belongs to their branch
                if (!$isAdmin && $inv['branch_id'] != $branchId) {
                    $error = 'You do not have permission to update this item.';
                }
            }
            
            if (empty($error)) {
                $oldStatus = $inv['status'];
                $stmt = $pdo->prepare("UPDATE inventory SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $inventoryId]);
                $message = 'Inventory status updated successfully.';
                
                // Log the status change
                logInventoryActivity(
                    $pdo,
                    $inventoryId,
                    $inv['product_id'],
                    $inv['branch_id'],
                    'update_status',
                    $oldStatus,
                    $newStatus,
                    null,
                    "Status changed from $oldStatus to $newStatus"
                );
            }
        } catch (PDOException $e) {
            $error = 'Failed to update inventory status: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$branchFilter = isset($_GET['branch']) ? (int)$_GET['branch'] : 0;

// Build query
$sql = "
    SELECT 
        i.id AS inventory_id,
        i.serial_number,
        i.status,
        i.reserved_until,
        p.id AS product_id,
        p.name AS product_name,
        p.sku,
        b.id AS branch_id,
        b.name AS branch_name
    FROM inventory i
    JOIN products p ON i.product_id = p.id
    JOIN branches b ON i.branch_id = b.id
    WHERE 1=1
";

$params = [];

// Role-based filtering
if (!$isAdmin) {
    $sql .= " AND i.branch_id = :branch_id";
    $params[':branch_id'] = $branchId;
}

// Search filter
if (!empty($search)) {
    $sql .= " AND (p.name LIKE :search OR p.sku LIKE :search OR i.serial_number LIKE :search)";
    $params[':search'] = "%$search%";
}

// Status filter
if (!empty($statusFilter)) {
    $sql .= " AND i.status = :status";
    $params[':status'] = $statusFilter;
}

// Branch filter (HQ only)
if ($isAdmin && $branchFilter > 0) {
    $sql .= " AND i.branch_id = :branch_filter";
    $params[':branch_filter'] = $branchFilter;
}

$sql .= " ORDER BY i.id DESC";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$inventoryItems = $stmt->fetchAll();

// Get branches for filter dropdown (HQ only)
$branches = [];
if ($isAdmin) {
    $stmt = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");
    $branches = $stmt->fetchAll();
}

$statusOptions = ['in_stock', 'reserved', 'sold', 'returned_qc', 'defective_warranty'];

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <div>
                <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
                    Inventory Management
                </h1>
                <p style="color: var(--gray-dark); font-size: 18px;">
                    <?= $isAdmin ? 'Manage stock across all branches.' : 'Manage stock for your branch.' ?>
                </p>
            </div>
            <a href="inventory-add.php" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 24px;">
                + ADD STOCK
            </a>
        </div>

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

        <!-- Search & Filter Bar -->
        <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 32px; background: #fff; padding: 16px 24px; border-radius: var(--radius); border: 1px solid var(--gray);">
            <form method="GET" action="" style="display: flex; gap: 12px; flex-wrap: wrap; width: 100%;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search by product, SKU, or serial..." 
                           style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px;">
                </div>
                <select name="status" style="height: 40px; padding: 0 12px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px; background: #fff;">
                    <option value="">All Statuses</option>
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?= $status ?>" <?= $statusFilter == $status ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', $status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isAdmin): ?>
                    <select name="branch" style="height: 40px; padding: 0 12px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 14px; background: #fff;">
                        <option value="0">All Branches</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch['id'] ?>" <?= $branchFilter == $branch['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($branch['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <button type="submit" class="btn btn--green" style="height: 40px; font-size: 14px; padding: 0 16px;">FILTER</button>
                <a href="inventory.php" class="btn btn--outline" style="height: 40px; font-size: 14px; padding: 0 16px;">CLEAR</a>
            </form>
        </div>

        <!-- Inventory Table -->
        <?php if (empty($inventoryItems)): ?>
            <div style="text-align: center; padding: 60px 0; background: #fff; border-radius: var(--radius); border: 1px solid var(--gray);">
                <p style="font-size: 22px; color: var(--gray-dark);">No inventory items found.</p>
                <a href="inventory-add.php" class="btn btn--green" style="margin-top: 20px;">Add Your First Stock</a>
            </div>
        <?php else: ?>
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); overflow: hidden; box-shadow: var(--shadow);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background: var(--dark); color: var(--light);">
                        <tr>
                            <th style="padding: 12px 16px; text-align: left; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Product</th>
                            <th style="padding: 12px 16px; text-align: left; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Serial Number</th>
                            <th style="padding: 12px 16px; text-align: left; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Branch</th>
                            <th style="padding: 12px 16px; text-align: center; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                            <th style="padding: 12px 16px; text-align: center; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventoryItems as $item): ?>
                            <tr style="border-bottom: 1px solid var(--gray);">
                                <td style="padding: 12px 16px;">
                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                    <br>
                                    <span style="color: var(--gray-dark); font-size: 12px;"><?= htmlspecialchars($item['sku']) ?></span>
                                </td>
                                <td style="padding: 12px 16px; font-family: monospace; font-size: 14px;">
                                    <?= htmlspecialchars($item['serial_number']) ?>
                                </td>
                                <td style="padding: 12px 16px; font-size: 14px;">
                                    <?= htmlspecialchars($item['branch_name']) ?>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <?php
                                    $statusColors = [
                                        'in_stock' => 'var(--green)',
                                        'reserved' => '#f0ad4e',
                                        'sold' => '#777777',
                                        'returned_qc' => '#5bc0de',
                                        'defective_warranty' => '#d9534f'
                                    ];
                                    $color = $statusColors[$item['status']] ?? '#777777';
                                    $label = ucfirst(str_replace('_', ' ', $item['status']));
                                    ?>
                                    <span style="display: inline-block; padding: 2px 12px; border-radius: 12px; background: <?= $color ?>; color: #fff; font-size: 12px; font-weight: 700;">
                                        <?= $label ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <form method="POST" action="" style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="inventory_id" value="<?= $item['inventory_id'] ?>">
                                        <select name="status" style="height: 32px; padding: 0 8px; border: 1px solid var(--gray); border-radius: var(--radius); font-size: 12px; background: #fff;">
                                            <?php foreach ($statusOptions as $status): ?>
                                                <option value="<?= $status ?>" <?= $item['status'] == $status ? 'selected' : '' ?>>
                                                    <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn--green" style="height: 32px; font-size: 12px; padding: 0 12px;">UPDATE</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="color: var(--gray-dark); font-size: 14px; margin-top: 12px;">
                Total items: <?= count($inventoryItems) ?>
            </p>
        <?php endif; ?>

        <p style="margin-top: 32px;">
            <a href="dashboard.php" style="color: var(--green); font-weight: 700;">&larr; Back to Dashboard</a>
        </p>
        
    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>