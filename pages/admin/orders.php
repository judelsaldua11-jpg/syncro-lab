<?php
// pages/admin/orders.php — Order Management
//
//   • HQ Admin        → all branches
//   • Branch Manager  → own branch only
//
//   10% member discount is auto-applied at checkout — this page only displays it.

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

/* ── AUTH ─────────────────────────────────────────────────── */
if (!isLoggedIn()) {
    header('Location: ../auth/login.php?error=Please log in.');
    exit;
}
$role = getUserRole();
if ($role !== 'hq_admin' && $role !== 'branch_manager') {
    header('Location: ../profile.php?error=Access denied.');
    exit;
}

$user       = getCurrentUser();
$isAdmin    = ($role === 'hq_admin');
$branchId   = (int)($_SESSION['branch_id'] ?? 0);
$branchName = $_SESSION['branch_name'] ?? 'Your Branch';
$pdo        = getConnection();
$msg        = '';
$err        = '';

/* ── CONFIG ──────────────────────────────────────────────── */
$STATUS_META = [
    'pending'   => ['label' => 'Pending',   'color' => '#f0ad4e'],
    'confirmed' => ['label' => 'Confirmed', 'color' => '#0275d8'],
    'shipped'   => ['label' => 'Shipped',   'color' => '#6c3483'],
    'delivered' => ['label' => 'Delivered', 'color' => '#2e7d32'],
    'cancelled' => ['label' => 'Cancelled', 'color' => '#c0392b'],
    'returned'  => ['label' => 'Returned',  'color' => '#7d5a00'],
];

$PAYMENT_LABELS = [
    'cash_on_delivery' => 'COD',
    'credit_card'      => 'Credit Card',
    'gcash'            => 'GCash',
    'paymaya'          => 'PayMaya',
];

$FINAL_STATUSES = ['delivered', 'cancelled', 'returned'];

/* ── HELPERS ──────────────────────────────────────────────── */
function isActiveMember(?string $expiry): bool {
    return !empty($expiry) && strtotime($expiry) > time();
}

function assertOrderAccess(PDO $pdo, int $orderId, bool $isAdmin, int $branchId): void {
    $sql    = "SELECT id FROM orders WHERE id = ?" . ($isAdmin ? '' : ' AND branch_id = ?');
    $params = $isAdmin ? [$orderId] : [$orderId, $branchId];
    $s = $pdo->prepare($sql);
    $s->execute($params);
    if (!$s->fetch()) throw new Exception('Order not found or outside your branch.');
}

/* ── POST HANDLERS ────────────────────────────────────────── */
$selfUrl = strtok($_SERVER['REQUEST_URI'] ?? 'orders.php', '#');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'update_status') {
        $orderId   = (int)($_POST['order_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';

        if ($orderId > 0 && isset($STATUS_META[$newStatus])) {
            try {
                assertOrderAccess($pdo, $orderId, $isAdmin, $branchId);

                $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
                    ->execute([$newStatus, $orderId]);

                if ($newStatus === 'delivered') {
                    $soldStmt = $pdo->prepare("UPDATE inventory i
                                               JOIN order_items oi ON oi.inventory_id = i.id
                                               SET i.status = 'sold', i.reserved_until = NULL, i.updated_at = NOW()
                                               WHERE oi.order_id = ? AND i.status = 'reserved'");
                    $soldStmt->execute([$orderId]);
                }

                $msg = "Order #" . str_pad($orderId, 5, '0', STR_PAD_LEFT)
                     . " status updated to " . $STATUS_META[$newStatus]['label'] . ".";
            } catch (Exception $e) {
                $err = $e->getMessage();
            }
        } else {
            $err = 'Invalid status update.';
        }
    }
}

/* ── FILTERS ─────────────────────────────────────────────── */
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');
$branchFilter = $isAdmin ? (int)($_GET['branch'] ?? 0) : 0;

$where  = ['1=1'];
$params = [];

if (!$isAdmin) {
    $where[]  = 'o.branch_id = ?';
    $params[] = $branchId;
} elseif ($branchFilter > 0) {
    $where[]  = 'o.branch_id = ?';
    $params[] = $branchFilter;
}

if ($statusFilter !== '' && isset($STATUS_META[$statusFilter])) {
    $where[]  = 'o.status = ?';
    $params[] = $statusFilter;
}

if ($search !== '') {
    if (ctype_digit($search)) {
        $where[]  = '(o.id = ? OR u.full_name LIKE ? OR u.email LIKE ?)';
        $params[] = (int)$search;
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    } else {
        $where[]  = '(u.full_name LIKE ? OR u.email LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
}

/* ── LOAD ORDERS ─────────────────────────────────────────── */
$sql = "
    SELECT
        o.id, o.order_date, o.total_amount, o.subtotal, o.discount_amount,
        o.status, o.payment_method, o.user_id,
        u.full_name AS customer_name,
        u.email     AS customer_email,
        u.membership_expiry,
        b.name      AS branch_name,
        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count,
        (SELECT COUNT(*) FROM refund_requests rr
          WHERE rr.order_id = o.id AND rr.status = 'pending') AS pending_refunds
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN branches b ON o.branch_id = b.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY o.order_date DESC
    LIMIT 100
";
$s = $pdo->prepare($sql);
$s->execute($params);
$orders = $s->fetchAll();

/* ── Status counts for tabs ── */
$countSql = "SELECT o.status, COUNT(*) AS n
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE 1=1" . ($isAdmin ? '' : ' AND o.branch_id = ?') . "
             GROUP BY o.status";
$s = $pdo->prepare($countSql);
$s->execute($isAdmin ? [] : [$branchId]);
$statusCounts = ['all' => 0];
foreach ($s->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int)$row['n'];
    $statusCounts['all'] += (int)$row['n'];
}

include __DIR__ . '/../../src/Views/layouts/header.php';

$card       = 'background:#fff;border-radius:var(--radius);border:1px solid var(--gray);box-shadow:var(--shadow);';
$inputStyle = 'height:38px;padding:0 12px;border:1px solid var(--gray);border-radius:var(--radius);font-size:14px;background:#fff;';
$smallInput = 'height:30px;padding:0 6px;border:1px solid var(--gray);border-radius:var(--radius);font-size:11px;background:#fff;';
$pillStyle  = 'display:inline-block;font-size:11px;font-weight:700;background:var(--light);border:1px solid var(--gray);border-radius:20px;padding:3px 9px;white-space:nowrap;';
$memberPill = 'display:inline-block;padding:1px 7px;border-radius:20px;font-size:10px;font-weight:700;background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;';
?>

<div style="padding:40px 0 60px;background:var(--light);min-height:70vh;color:var(--dark);">
<div style="max-width:1280px;margin:0 auto;padding:0 40px;">

    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
        <div>
            <h1 style="font-family:var(--font-heading);font-size:42px;text-transform:uppercase;margin:0 0 6px;">Order Management</h1>
            <p style="color:var(--gray-dark);font-size:16px;margin:0;">
                <?= $isAdmin ? 'All branches' : 'Branch: <strong>' . htmlspecialchars($branchName) . '</strong>' ?>
                &bull; <?= number_format($statusCounts['all']) ?> total orders
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="dashboard.php" class="btn btn--outline btn--small" style="height:38px;padding:0 16px;">← Dashboard</a>
        </div>
    </div>

    <?php if ($msg): ?>
        <div style="background:#e8f5e9;color:#2e7d32;padding:14px 20px;border-radius:var(--radius);border-left:4px solid var(--green);margin-bottom:20px;font-weight:600;">
            ✓ <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div style="background:#ffebee;color:#c62828;padding:14px 20px;border-radius:var(--radius);border-left:4px solid #d32f2f;margin-bottom:20px;font-weight:600;">
            ✕ <?= htmlspecialchars($err) ?>
        </div>
    <?php endif; ?>

    <div style="<?= $card ?>padding:16px;margin-bottom:20px;">
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;">
            <?php
            $tabs = ['' => 'All'] + array_map(fn($m) => $m['label'], $STATUS_META);
            foreach ($tabs as $key => $label):
                $active = ($statusFilter === $key);
                $count  = $statusCounts[$key ?: 'all'] ?? 0;
                $href   = '?' . http_build_query(array_filter([
                    'status' => $key,
                    'branch' => $isAdmin && $branchFilter ? $branchFilter : null,
                    'search' => $search ?: null,
                ]));
            ?>
                <a href="<?= $href ?>" style="text-decoration:none;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;
                    background:<?= $active ? 'var(--dark)' : 'var(--light)' ?>;
                    color:<?= $active ? '#fff' : 'var(--dark)' ?>;
                    border:1px solid <?= $active ? 'var(--dark)' : 'var(--gray)' ?>;">
                    <?= $label ?> <span style="opacity:0.7;">(<?= $count ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="GET" action="" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <?php if ($statusFilter !== ''): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <?php endif; ?>

            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search order #, customer name or email…"
                   style="<?= $inputStyle ?> flex:1;min-width:240px;">

            <?php if ($isAdmin): ?>
                <select name="branch" style="<?= $inputStyle ?>">
                    <option value="0">All Branches</option>
                    <?php foreach (getBranches() as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $branchFilter === (int)$b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <button type="submit" class="btn btn--green btn--small" style="height:38px;font-size:14px;padding:0 20px;">Search</button>
            <a href="orders.php" class="btn btn--outline btn--small" style="height:38px;font-size:14px;padding:0 16px;">Clear</a>
        </form>
    </div>

    <div style="<?= $card ?>overflow:hidden;">
        <?php if (empty($orders)): ?>
            <div style="padding:60px 20px;text-align:center;color:var(--gray-dark);">
                <p style="font-size:18px;margin:0 0 6px;">No orders found.</p>
                <p style="font-size:13px;margin:0;">Try adjusting your filters.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:14px;text-align:left;">
                    <thead style="background:var(--dark);color:var(--light);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;">
                        <tr>
                            <th style="padding:12px 14px;">Order #</th>
                            <th style="padding:12px 14px;">Date</th>
                            <th style="padding:12px 14px;">Customer</th>
                            <th style="padding:12px 14px;">Branch</th>
                            <th style="padding:12px 14px;">Items</th>
                            <th style="padding:12px 14px;">Amount</th>
                            <th style="padding:12px 14px;">Discount</th>
                            <th style="padding:12px 14px;">Payment</th>
                            <th style="padding:12px 14px;">Status</th>
                            <th style="padding:12px 14px;">Refund</th>
                            <th style="padding:12px 14px;min-width:240px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o):
                        $statusMeta     = $STATUS_META[$o['status']] ?? ['label' => ucfirst($o['status']), 'color' => 'var(--dark)'];
                        $orderNum       = str_pad($o['id'], 5, '0', STR_PAD_LEFT);
                        $isMember       = isActiveMember($o['membership_expiry']);
                        $isFinal        = in_array($o['status'], $FINAL_STATUSES, true);
                        $pmLabel        = $PAYMENT_LABELS[strtolower($o['payment_method'] ?? '')] ?? strtoupper($o['payment_method'] ?? '—');
                        $pendingRefunds = (int)$o['pending_refunds'];
                        $hasDiscount    = ($o['discount_amount'] ?? 0) > 0;
                    ?>
                        <tr id="order-<?= $o['id'] ?>" style="border-bottom:1px solid var(--gray);scroll-margin-top:100px;"
                            onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='transparent'">

                            <td style="padding:12px 14px;font-weight:700;font-family:var(--font-heading);white-space:nowrap;">
                                #<?= $orderNum ?>
                            </td>

                            <td style="padding:12px 14px;white-space:nowrap;">
                                <span style="font-size:13px;"><?= date('M d, Y', strtotime($o['order_date'])) ?></span><br>
                                <span style="font-size:11px;color:var(--gray-dark);"><?= date('h:i A', strtotime($o['order_date'])) ?></span>
                            </td>

                            <td style="padding:12px 14px;">
                                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <strong style="font-size:13px;"><?= htmlspecialchars($o['customer_name']) ?></strong>
                                    <?php if ($isMember): ?>
                                        <span title="Active member" style="<?= $memberPill ?>">💎 Member</span>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size:11px;color:var(--gray-dark);"><?= htmlspecialchars($o['customer_email']) ?></span>
                            </td>

                            <td style="padding:12px 14px;">
                                <span style="<?= $pillStyle ?>">📍 <?= htmlspecialchars($o['branch_name'] ?? 'N/A') ?></span>
                            </td>

                            <td style="padding:12px 14px;"><?= (int)$o['item_count'] ?></td>

                            <td style="padding:12px 14px;font-weight:700;white-space:nowrap;">
                                ₱ <?= number_format($o['total_amount'], 2) ?>
                            </td>

                            <td style="padding:12px 14px;">
                                <?php if ($hasDiscount): ?>
                                    <span style="font-size:12px;font-weight:700;color:#2e7d32;white-space:nowrap;">
                                        − ₱<?= number_format($o['discount_amount'], 2) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="font-size:11px;color:var(--gray-dark);">—</span>
                                <?php endif; ?>
                            </td>

                            <td style="padding:12px 14px;">
                                <span style="<?= $pillStyle ?>"><?= htmlspecialchars($pmLabel) ?></span>
                            </td>

                            <td style="padding:12px 14px;">
                                <span style="display:inline-block;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;background:<?= $statusMeta['color'] ?>;color:#fff;white-space:nowrap;">
                                    <?= $statusMeta['label'] ?>
                                </span>
                            </td>

                            <td style="padding:12px 14px;">
                                <?php if ($pendingRefunds > 0): ?>
                                    <a href="dashboard.php#refund-panel" title="Review in dashboard"
                                       style="display:inline-block;font-size:11px;font-weight:700;background:#fdf2f2;color:#c0392b;border:1px solid #f5c6c6;border-radius:20px;padding:4px 10px;text-decoration:none;white-space:nowrap;">
                                        💸 <?= $pendingRefunds ?> pending
                                    </a>
                                <?php else: ?>
                                    <span style="font-size:11px;color:var(--gray-dark);">—</span>
                                <?php endif; ?>
                            </td>

                            <td style="padding:10px 14px;">
                                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">

                                    <a href="../order-success.php?id=<?= $o['id'] ?>" target="_blank"
                                       class="btn btn--small btn--outline"
                                       style="height:30px;font-size:11px;padding:0 10px;">View</a>

                                    <?php if (!$isFinal): ?>
                                        <form method="POST" action="<?= htmlspecialchars($selfUrl) ?>#order-<?= $o['id'] ?>"
                                              style="display:flex;gap:4px;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <select name="new_status" style="<?= $smallInput ?>">
                                                <?php foreach ($STATUS_META as $key => $m): ?>
                                                    <option value="<?= $key ?>" <?= $key === $o['status'] ? 'selected' : '' ?>>
                                                        <?= $m['label'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn--small btn--green"
                                                    style="height:30px;font-size:11px;padding:0 10px;"
                                                    onclick="return confirm('Update status for order #<?= $orderNum ?>?');">
                                                Update
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($hasDiscount): ?>
                                        <span title="10% member discount auto-applied at checkout"
                                              style="display:inline-flex;align-items:center;gap:4px;height:30px;padding:0 10px;border-radius:var(--radius);background:#e8f5e9;color:#2e7d32;font-size:11px;font-weight:700;border:1px solid #a5d6a7;">
                                            💎 10% Auto-Applied
                                        </span>
                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <p style="margin-top:32px;">
        <a href="../../index.php" style="color:var(--green);font-weight:700;">← Back to Home</a>
    </p>

</div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>