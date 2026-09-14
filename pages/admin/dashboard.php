<?php
// pages/admin/dashboard.php — Unified Dashboard (HQ Admin + Branch Manager)

error_reporting(E_ALL);
ini_set('display_errors', 0);      // CHANGED: never show raw errors to users
ini_set('log_errors', 1);          // CHANGED: log instead
ini_set('error_log', __DIR__ . '/../../logs/app_errors.log'); // CHANGED: make sure this dir exists & is writable

if (session_status() === PHP_SESSION_NONE) { // CHANGED: guard against double session_start()
    session_start();
}
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

/* ── 1. AUTH ───────────────────────────────────────────────── */
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

/* CHANGED: CSRF token setup */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* ── 2. POST ACTIONS ───────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CHANGED: CSRF check applies to every POST action on this page */
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid or expired request. Please refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';

    /* Refund approve / reject — role-scoped */
    if ($action === 'handle_refund') {
        $refundId = (int)($_POST['refund_id'] ?? 0);
        $decision = $_POST['refund_action'] ?? '';
        $note     = trim($_POST['admin_note'] ?? '');

        if ($refundId > 0 && in_array($decision, ['approved', 'rejected'], true)) {
            try {
                $sql = "SELECT rr.*, o.branch_id, o.status AS order_status
                        FROM refund_requests rr
                        JOIN orders o ON rr.order_id = o.id
                        WHERE rr.id = ? AND rr.status = 'pending'";
                $params = [$refundId];
                if (!$isAdmin) { $sql .= " AND o.branch_id = ?"; $params[] = $branchId; }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $refund = $stmt->fetch();
                if (!$refund) throw new Exception('Refund not found, already processed, or outside your branch.');

                $pdo->beginTransaction();

                $pdo->prepare("UPDATE refund_requests
                               SET status = ?, admin_note = ?, resolved_by = ?, resolved_at = NOW()
                               WHERE id = ?")
                    ->execute([$decision, $note, $user['id'] ?? null, $refundId]);

                if ($decision === 'approved' && $refund['order_status'] !== 'cancelled') {
                    $by  = $isAdmin ? 'HQ Admin' : 'Branch Manager';
                    $res = cancelOrder($pdo, $refund['order_id'], null, "Refund approved by {$by}");
                    if (!$res['success']) {
                        throw new Exception('Order cancel failed: ' . $res['message']);
                    }
                }

                $pdo->commit();
                $msg = "Refund #{$refundId} has been {$decision}.";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $err = $e->getMessage();
            }
        } else {
            $err = 'Invalid refund action.';
        }
    }

    /* Membership pricing — HQ only */
    if ($isAdmin && $action === 'update_membership_settings') {
        $price    = (float)($_POST['membership_price'] ?? 0);
        $duration = (int)($_POST['membership_duration_months'] ?? 12);

        if ($price <= 0) {
            $err = 'Price must be greater than zero.';
        } elseif ($duration < 1 || $duration > 60) {
            $err = 'Duration must be 1–60 months.';
        } else {
            $ok1 = updateSiteSetting('membership_price', number_format($price, 2, '.', ''), $user['id']);
            $ok2 = updateSiteSetting('membership_duration_months', (string)$duration, $user['id']);
            if ($ok1 && $ok2) {
                $msg = "Membership updated to ₱ " . number_format($price, 2) . " for {$duration} month(s).";
            } else {
                $err = 'Failed to save membership settings.';
            }
        }
    }
}

/* ── 3. STATS ──────────────────────────────────────────────── */
$scope  = $isAdmin ? '' : ' AND branch_id = ?';
$params = $isAdmin ? [] : [$branchId];

$fetch = function (string $sql, array $p = []) use ($pdo) {
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s->fetchColumn();
};

$stats = [
    'revenue'          => (float)($fetch("SELECT SUM(total_amount) FROM orders
                            WHERE status NOT IN ('cancelled','returned')
                              AND MONTH(order_date)=MONTH(CURRENT_DATE())
                              AND YEAR(order_date)=YEAR(CURRENT_DATE())" . $scope, $params) ?? 0),
    'pending_orders'   => (int)$fetch("SELECT COUNT(*) FROM orders WHERE status='pending'" . $scope, $params),
    'pending_bookings' => (int)$fetch("SELECT COUNT(*) FROM service_bookings WHERE status='pending'" . $scope, $params),
    'stock'            => (int)$fetch("SELECT COUNT(*) FROM inventory WHERE status='in_stock'" . $scope, $params),
    'pending_refunds'  => (int)$fetch("SELECT COUNT(*) FROM refund_requests rr
                            JOIN orders o ON rr.order_id = o.id
                            WHERE rr.status='pending'" . ($isAdmin ? '' : ' AND o.branch_id = ?'), $params),
];

if ($isAdmin) {
    $stats['branches']       = (int)$fetch("SELECT COUNT(*) FROM branches WHERE is_active=1");
    $stats['active_members'] = (int)$fetch("SELECT COUNT(*) FROM users WHERE membership_expiry > NOW()");
    $memberPrice    = getMembershipPrice();
    $memberDuration = getMembershipDurationMonths();
}

/* ── 4. LISTS ──────────────────────────────────────────────── */
$branchFilter = $isAdmin ? '' : ' AND o.branch_id = ?';
$bp           = $isAdmin ? [] : [$branchId];

$s = $pdo->prepare("SELECT o.id, o.order_date, o.total_amount, u.full_name, b.name AS branch_name
                    FROM orders o
                    JOIN users u ON o.user_id = u.id
                    LEFT JOIN branches b ON o.branch_id = b.id
                    WHERE o.status='pending' {$branchFilter}
                    ORDER BY o.order_date DESC LIMIT 5");
$s->execute($bp);
$pendingOrders = $s->fetchAll();

$s = $pdo->prepare("SELECT sb.id, sb.scheduled_date, sb.service_type, sb.status, u.full_name, b.name AS branch_name
                    FROM service_bookings sb
                    JOIN users u ON sb.user_id = u.id
                    JOIN branches b ON sb.branch_id = b.id
                    WHERE sb.status IN ('pending','confirmed')" . ($isAdmin ? '' : ' AND sb.branch_id = ?') . "
                    ORDER BY sb.scheduled_date ASC LIMIT 5");
$s->execute($bp);
$upcomingBookings = $s->fetchAll();

/* Refund list (with filters) */
$rfBranch  = $isAdmin ? (int)($_GET['rf_branch'] ?? 0) : 0;
$rfPayment = trim($_GET['rf_payment'] ?? '');

$sql = "SELECT rr.id AS refund_id, rr.order_id, rr.amount, rr.payment_method, rr.reason, rr.requested_at,
               u.full_name AS customer_name, u.email AS customer_email,
               o.order_date, b.name AS branch_name
        FROM refund_requests rr
        JOIN orders o ON rr.order_id = o.id
        JOIN users  u ON rr.user_id  = u.id
        LEFT JOIN branches b ON o.branch_id = b.id
        WHERE rr.status = 'pending'";
$p = [];
if (!$isAdmin)         { $sql .= " AND o.branch_id = ?";         $p[] = $branchId; }
elseif ($rfBranch > 0) { $sql .= " AND o.branch_id = ?";         $p[] = $rfBranch; }
if ($rfPayment !== '') { $sql .= " AND rr.payment_method = ?";   $p[] = $rfPayment; }
$sql .= " ORDER BY rr.requested_at ASC LIMIT 50";
$s = $pdo->prepare($sql);
$s->execute($p);
$refunds = $s->fetchAll();

/* Activity log */
$afAction = $_GET['filter_action'] ?? '';
$sql = "SELECT l.*, u.full_name AS user_name, p.name AS product_name, b.name AS branch_name
        FROM inventory_logs l
        LEFT JOIN users u    ON l.user_id    = u.id
        LEFT JOIN products p ON l.product_id = p.id
        LEFT JOIN branches b ON l.branch_id  = b.id
        WHERE 1=1" . ($isAdmin ? '' : ' AND l.branch_id = ?') . ($afAction !== '' ? ' AND l.action = ?' : '') . "
        ORDER BY l.created_at DESC LIMIT 30";
$p = $isAdmin ? [] : [$branchId];
if ($afAction !== '') $p[] = $afAction;
$s = $pdo->prepare($sql);
$s->execute($p);
$activityLogs = $s->fetchAll();

include __DIR__ . '/../../src/Views/layouts/header.php';

$card = 'background:#fff;border-radius:var(--radius);border:1px solid var(--gray);box-shadow:var(--shadow);';
$kpiColor = fn($n) => $n > 0 ? '#f0ad4e' : 'var(--dark)';
?>

<div style="padding:40px 0 60px;background:var(--light);min-height:70vh;color:var(--dark);">
<div style="max-width:1280px;margin:0 auto;padding:0 40px;">

    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
        <div>
            <h1 style="font-family:var(--font-heading);font-size:42px;text-transform:uppercase;margin:0 0 6px;">Dashboard</h1>
            <p style="color:var(--gray-dark);font-size:16px;margin:0;">
                Welcome, <strong><?= htmlspecialchars($user['full_name']) ?></strong> &bull;
                <span style="color:var(--green);font-weight:700;">
                    <?= $isAdmin ? 'HQ Admin' : htmlspecialchars($branchName) . ' Manager' ?>
                </span>
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <?php if ($isAdmin): ?>
                <a href="#membership" class="btn btn--outline btn--small" style="height:38px;padding:0 16px;">💎 Membership</a>
            <?php endif; ?>
            <a href="inventory-add.php" class="btn btn--green btn--small" style="height:38px;padding:0 16px;">➕ Stock Item</a>
        </div>
    </div>

    <?php if ($msg): ?>
        <div style="background:#e8f5e9;color:#2e7d32;padding:14px 20px;border-radius:var(--radius);border-left:4px solid var(--green);margin-bottom:20px;font-weight:600;">✓ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div style="background:#ffebee;color:#c62828;padding:14px 20px;border-radius:var(--radius);border-left:4px solid #d32f2f;margin-bottom:20px;font-weight:600;">✕ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- STATS -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
        <div style="<?= $card ?>padding:20px;">
            <p style="color:var(--gray-dark);font-size:12px;text-transform:uppercase;font-weight:700;margin:0 0 6px;">Monthly Revenue</p>
            <p style="font-family:var(--font-heading);font-size:28px;color:var(--green);margin:0;">₱ <?= number_format($stats['revenue'], 2) ?></p>
        </div>
        <div style="<?= $card ?>padding:20px;">
            <p style="color:var(--gray-dark);font-size:12px;text-transform:uppercase;font-weight:700;margin:0 0 6px;">Pending Orders</p>
            <p style="font-family:var(--font-heading);font-size:28px;color:<?= $kpiColor($stats['pending_orders']) ?>;margin:0;"><?= number_format($stats['pending_orders']) ?></p>
        </div>
        <div style="<?= $card ?>padding:20px;">
            <p style="color:var(--gray-dark);font-size:12px;text-transform:uppercase;font-weight:700;margin:0 0 6px;">Pending Bookings</p>
            <p style="font-family:var(--font-heading);font-size:28px;color:<?= $stats['pending_bookings'] > 0 ? '#0275d8' : 'var(--dark)' ?>;margin:0;"><?= number_format($stats['pending_bookings']) ?></p>
        </div>
        <div style="<?= $card ?>padding:20px;border-color:<?= $stats['pending_refunds'] > 0 ? '#f0ad4e' : 'var(--gray)' ?>;">
            <p style="color:var(--gray-dark);font-size:12px;text-transform:uppercase;font-weight:700;margin:0 0 6px;">Pending Refunds</p>
            <p style="font-family:var(--font-heading);font-size:28px;color:<?= $stats['pending_refunds'] > 0 ? '#c0392b' : 'var(--dark)' ?>;margin:0;">
                <?= number_format($stats['pending_refunds']) ?>
                <?php if ($stats['pending_refunds'] > 0): ?>
                    <a href="#refund-panel" style="font-size:13px;color:var(--green);font-weight:700;margin-left:8px;text-decoration:none;">Review →</a>
                <?php endif; ?>
            </p>
        </div>
        <div style="<?= $card ?>padding:20px;">
            <p style="color:var(--gray-dark);font-size:12px;text-transform:uppercase;font-weight:700;margin:0 0 6px;">Stock</p>
            <p style="font-family:var(--font-heading);font-size:28px;margin:0;"><?= number_format($stats['stock']) ?></p>
        </div>
    </div>

    <!-- QUICK LINKS -->
    <div style="<?= $card ?>padding:16px;margin-bottom:24px;">
        <p style="color:var(--gray-dark);font-size:11px;text-transform:uppercase;letter-spacing:0.6px;font-weight:700;margin:0 6px 12px;">Quick Actions</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;">
            <?php
            $links = $isAdmin
                ? [['orders.php','🛒','Orders','Fulfillment & tracking'],
                   ['bookings.php','📅','Bookings','Service appointments'],
                   ['inventory.php','📦','Inventory','Multi-branch stock'],
                   ['products.php','🏷️','Catalog','Products & prices'],
                   ['locations.php','📍','Locations','Branch hubs'],
                   ['users.php','👥','Users','Accounts & roles'],
                   ['#membership','💎','Membership','₱ ' . number_format($memberPrice, 2)]]
                : [['orders.php','🛒','Orders','Fulfill local orders'],
                   ['bookings.php','📅','Bookings','Workshop calendar'],
                   ['inventory.php','📦','Inventory','Stock & serials'],
                   ['inventory-add.php','➕','Add Stock','Receive new units']];
            foreach ($links as [$href, $icon, $title, $desc]): ?>
                <a href="<?= htmlspecialchars($href) ?>" style="text-decoration:none;display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:var(--radius);border:1px solid transparent;"
                   onmouseover="this.style.background='var(--light)';this.style.borderColor='var(--gray)'"
                   onmouseout="this.style.background='transparent';this.style.borderColor='transparent'">
                    <span style="font-size:22px;"><?= $icon ?></span>
                    <div style="min-width:0;">
                        <div style="font-family:var(--font-heading);font-size:14px;color:var(--dark);"><?= htmlspecialchars($title) ?></div>
                        <div style="font-size:11px;color:var(--gray-dark);"><?= htmlspecialchars($desc) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ORDERS + BOOKINGS -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
        <div style="<?= $card ?>padding:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <h3 style="font-family:var(--font-heading);font-size:18px;text-transform:uppercase;margin:0;">⚡ Pending Orders</h3>
                <a href="orders.php?status=pending" style="color:var(--green);font-size:12px;font-weight:700;">View All →</a>
            </div>
            <?php if (empty($pendingOrders)): ?>
                <p style="color:var(--gray-dark);font-size:13px;text-align:center;padding:20px 0;">No pending orders. ✅</p>
            <?php else: foreach ($pendingOrders as $o): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:var(--light);border-radius:var(--radius);border-left:3px solid #f0ad4e;margin-bottom:8px;">
                    <div>
                        <strong style="font-size:14px;">Order #<?= str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT) ?></strong> — <?= htmlspecialchars($o['full_name']) ?>
                        <div style="font-size:11px;color:var(--gray-dark);">
                            <?= htmlspecialchars($o['branch_name'] ?? 'Multi-Branch') ?> &bull; <?= date('M d, h:i A', strtotime($o['order_date'])) ?>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <strong style="font-size:14px;">₱ <?= number_format($o['total_amount'], 2) ?></strong><br>
                        <a href="orders.php?search=<?= (int)$o['id'] ?>" class="btn btn--small btn--outline" style="height:24px;font-size:11px;padding:0 8px;margin-top:4px;">Manage</a>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <div style="<?= $card ?>padding:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <h3 style="font-family:var(--font-heading);font-size:18px;text-transform:uppercase;margin:0;">🛠️ Upcoming Bookings</h3>
                <a href="bookings.php" style="color:var(--green);font-size:12px;font-weight:700;">View All →</a>
            </div>
            <?php if (empty($upcomingBookings)): ?>
                <p style="color:var(--gray-dark);font-size:13px;text-align:center;padding:20px 0;">No upcoming bookings. ✅</p>
            <?php else: foreach ($upcomingBookings as $b):
                $c = $b['status'] === 'pending' ? '#f0ad4e' : '#0275d8';
            ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:var(--light);border-radius:var(--radius);border-left:3px solid <?= $c ?>;margin-bottom:8px;">
                    <div>
                        <strong style="font-size:14px;"><?= htmlspecialchars($b['full_name']) ?></strong>
                        <div style="font-size:11px;color:var(--gray-dark);">
                            <?= htmlspecialchars(str_replace('_', ' ', $b['service_type'])) ?> &bull; <?= date('M d, h:i A', strtotime($b['scheduled_date'])) ?>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <span style="font-size:11px;font-weight:700;text-transform:uppercase;color:<?= $c ?>;"><?= htmlspecialchars(ucfirst($b['status'])) ?></span><br>
                        <a href="bookings.php?status=<?= urlencode($b['status']) ?>" class="btn btn--small btn--outline" style="height:24px;font-size:11px;padding:0 8px;margin-top:4px;">Manage</a>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- REFUND PANEL -->
    <div id="refund-panel" style="<?= $card ?>margin-bottom:24px;overflow:hidden;border-color:<?= $stats['pending_refunds'] > 0 ? '#c0392b' : 'var(--gray)' ?>;">
        <div style="padding:18px 24px;background:<?= $stats['pending_refunds'] > 0 ? '#fdf2f2' : 'var(--light)' ?>;border-bottom:1px solid var(--gray);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:22px;">💸</span>
                <div>
                    <h3 style="font-family:var(--font-heading);font-size:20px;text-transform:uppercase;margin:0;">
                        Refund Requests
                        <?php if ($stats['pending_refunds'] > 0): ?>
                            <span style="font-size:12px;background:#c0392b;color:#fff;border-radius:20px;padding:3px 10px;margin-left:8px;"><?= $stats['pending_refunds'] ?> pending</span>
                        <?php endif; ?>
                    </h3>
                    <p style="font-size:12px;color:var(--gray-dark);margin:2px 0 0;"><?= $isAdmin ? 'All branches' : 'Your branch only' ?></p>
                </div>
            </div>
            <a href="orders.php" class="btn btn--outline btn--small" style="height:34px;font-size:13px;padding:0 14px;">Order Manager →</a>
        </div>

        <div style="padding:12px 20px;background:var(--light);border-bottom:1px solid var(--gray);">
            <form method="GET" action="#refund-panel" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="hidden" name="section" value="refunds">
                <?php if ($isAdmin): ?>
                    <select name="rf_branch" style="height:34px;padding:0 10px;border:1px solid var(--gray);border-radius:var(--radius);font-size:13px;background:#fff;">
                        <option value="0">All Branches</option>
                        <?php foreach (getBranches() as $b): ?>
                            <option value="<?= (int)$b['id'] ?>" <?= $rfBranch === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <select name="rf_payment" style="height:34px;padding:0 10px;border:1px solid var(--gray);border-radius:var(--radius);font-size:13px;background:#fff;">
                    <option value="">All Payment Methods</option>
                    <?php foreach (['cod'=>'Cash on Delivery','gcash'=>'GCash','bank'=>'Bank','card'=>'Card'] as $v => $l): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= $rfPayment === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn--green btn--small" style="height:34px;font-size:13px;padding:0 16px;">Filter</button>
                <a href="dashboard.php#refund-panel" class="btn btn--outline btn--small" style="height:34px;font-size:13px;padding:0 12px;">Clear</a>
            </form>
        </div>

        <?php if (empty($refunds)): ?>
            <div style="padding:40px 20px;text-align:center;color:var(--gray-dark);">✅ No pending refunds.</div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:14px;">
                    <thead style="background:var(--dark);color:var(--light);font-size:11px;text-transform:uppercase;">
                        <tr>
                            <th style="padding:12px 14px;text-align:left;">Refund #</th>
                            <th style="padding:12px 14px;text-align:left;">Customer</th>
                            <th style="padding:12px 14px;text-align:left;">Branch</th>
                            <th style="padding:12px 14px;text-align:left;">Order</th>
                            <th style="padding:12px 14px;text-align:left;">Amount</th>
                            <th style="padding:12px 14px;text-align:left;">Reason</th>
                            <th style="padding:12px 14px;text-align:left;min-width:260px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($refunds as $r): ?>
                        <tr style="border-bottom:1px solid var(--gray);">
                            <td style="padding:12px 14px;font-weight:700;">#<?= str_pad((string)$r['refund_id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td style="padding:12px 14px;">
                                <strong style="font-size:13px;"><?= htmlspecialchars($r['customer_name']) ?></strong><br>
                                <span style="font-size:11px;color:var(--gray-dark);"><?= htmlspecialchars($r['customer_email']) ?></span>
                            </td>
                            <td style="padding:12px 14px;"><?= htmlspecialchars($r['branch_name'] ?? '—') ?></td>
                            <td style="padding:12px 14px;">
                                <a href="orders.php?search=<?= (int)$r['order_id'] ?>" style="color:var(--green);font-weight:700;text-decoration:none;">#<?= str_pad((string)$r['order_id'], 5, '0', STR_PAD_LEFT) ?></a>
                            </td>
                            <td style="padding:12px 14px;font-weight:700;">₱ <?= number_format($r['amount'], 2) ?></td>
                            <td style="padding:12px 14px;max-width:220px;font-size:12px;" title="<?= htmlspecialchars($r['reason']) ?>">
                                <?= htmlspecialchars(mb_strimwidth($r['reason'], 0, 90, '…')) ?>
                            </td>
                            <td style="padding:10px 14px;">
                                <form method="POST" action="#refund-panel" style="display:flex;flex-direction:column;gap:6px;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="action" value="handle_refund">
                                    <input type="hidden" name="refund_id" value="<?= (int)$r['refund_id'] ?>">
                                    <textarea name="admin_note" placeholder="Optional note…" style="width:100%;height:40px;padding:6px 8px;border:1px solid var(--gray);border-radius:var(--radius);font-size:12px;resize:vertical;"></textarea>
                                    <div style="display:flex;gap:6px;">
                                        <button type="submit" name="refund_action" value="approved" class="btn btn--green"
                                                style="flex:1;height:30px;font-size:12px;padding:0;"
                                                onclick="return confirm('Approve this refund?');">✓ Approve</button>
                                        <button type="submit" name="refund_action" value="rejected"
                                                style="flex:1;height:30px;font-size:12px;padding:0;background:#ffebee;color:#c62828;border:1px solid #ef9a9a;border-radius:var(--radius);font-weight:700;cursor:pointer;"
                                                onclick="return confirm('Reject this refund?');">✕ Reject</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- MEMBERSHIP (HQ only) -->
    <?php if ($isAdmin): ?>
    <div id="membership" style="<?= $card ?>padding:24px;margin-bottom:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
            <h3 style="font-family:var(--font-heading);font-size:20px;text-transform:uppercase;margin:0;">💎 Membership Pricing</h3>
            <span style="font-size:13px;color:var(--gray-dark);">
                Current: <strong>₱ <?= number_format($memberPrice, 2) ?></strong> / <?= (int)$memberDuration ?> mo
                &bull; <?= number_format($stats['active_members']) ?> active members
            </span>
        </div>
        <form method="POST" action="#membership" style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="update_membership_settings">
            <div>
                <label style="display:block;font-size:12px;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Price (₱)</label>
                <input type="number" step="0.01" min="1" name="membership_price" required
                       value="<?= htmlspecialchars(number_format($memberPrice, 2, '.', '')) ?>"
                       style="width:100%;height:42px;padding:0 12px;border:2px solid var(--gray);border-radius:var(--radius);font-size:16px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Duration (months)</label>
                <input type="number" min="1" max="60" name="membership_duration_months" required
                       value="<?= (int)$memberDuration ?>"
                       style="width:100%;height:42px;padding:0 12px;border:2px solid var(--gray);border-radius:var(--radius);font-size:16px;">
            </div>
            <button type="submit" class="btn btn--green" style="height:42px;padding:0 24px;"
                    onclick="return confirm('Save new pricing settings?');">Save</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- ACTIVITY LOG -->
    <div id="activity" style="<?= $card ?>padding:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
            <h3 style="font-family:var(--font-heading);font-size:20px;text-transform:uppercase;margin:0;">📋 Inventory Activity</h3>
            <form method="GET" action="#activity" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="hidden" name="section" value="activity">
                <select name="filter_action" style="height:34px;padding:0 10px;border:1px solid var(--gray);border-radius:var(--radius);font-size:13px;background:#fff;">
                    <option value="">All Actions</option>
                    <option value="add"           <?= $afAction === 'add'           ? 'selected' : '' ?>>Add Stock</option>
                    <option value="update_status" <?= $afAction === 'update_status' ? 'selected' : '' ?>>Status Change</option>
                </select>
                <button type="submit" class="btn btn--green btn--small" style="height:34px;font-size:13px;padding:0 16px;">Filter</button>
                <a href="dashboard.php#activity" class="btn btn--outline btn--small" style="height:34px;font-size:13px;padding:0 12px;">Clear</a>
            </form>
        </div>
        <?php if (empty($activityLogs)): ?>
            <p style="color:var(--gray-dark);text-align:center;padding:20px 0;">No activity found.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:14px;">
                    <thead style="background:var(--dark);color:var(--light);">
                        <tr>
                            <th style="padding:10px 12px;text-align:left;">Date</th>
                            <th style="padding:10px 12px;text-align:left;">User</th>
                            <?php if ($isAdmin): ?><th style="padding:10px 12px;text-align:left;">Branch</th><?php endif; ?>
                            <th style="padding:10px 12px;text-align:left;">Product</th>
                            <th style="padding:10px 12px;text-align:left;">Action</th>
                            <th style="padding:10px 12px;text-align:left;">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activityLogs as $l):
                            $labels = ['add'=>'➕ Add Stock','update_status'=>'🔄 Status Change',
                                       'delete'=>'🗑️ Delete','reserve'=>'🔒 Reserve','release'=>'🔓 Release'];
                        ?>
                            <tr style="border-bottom:1px solid var(--gray);">
                                <td style="padding:8px 12px;"><?= date('M d, Y H:i', strtotime($l['created_at'])) ?></td>
                                <td style="padding:8px 12px;"><?= htmlspecialchars($l['user_name'] ?? '—') ?></td>
                                <?php if ($isAdmin): ?>
                                    <td style="padding:8px 12px;"><?= htmlspecialchars($l['branch_name'] ?? '—') ?></td>
                                <?php endif; ?>
                                <td style="padding:8px 12px;"><?= htmlspecialchars($l['product_name'] ?? '#' . $l['product_id']) ?></td>
                                <td style="padding:8px 12px;"><?= htmlspecialchars($labels[$l['action']] ?? ucfirst($l['action'])) ?></td>
                                <td style="padding:8px 12px;">
                                    <?php if ($l['action'] === 'add'): ?>
                                        Added <?= (int)$l['quantity'] ?> item(s)
                                    <?php elseif ($l['action'] === 'update_status'): ?>
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $l['old_status'] ?? ''))) ?>
                                        → <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $l['new_status'] ?? ''))) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($l['notes'] ?? '') ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <p style="margin-top:40px;">
        <a href="../../index.php" style="color:var(--green);font-weight:700;">← Back to Home</a>
    </p>

</div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>