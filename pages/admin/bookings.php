<?php
// pages/admin/bookings.php - Manage Bookings + Membership Claims
//
//   • HQ Admin        → all branches, can claim on any booking
//   • Branch Manager  → own branch only, can claim only own-branch bookings

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
    header('Location: ../../index.php?error=You do not have permission.');
    exit;
}

$user     = getCurrentUser();
$isAdmin  = ($role === 'hq_admin');
$branchId = (int)($_SESSION['branch_id'] ?? 0);
$pdo      = getConnection();
$message  = '';
$error    = '';

/* ═══════════════════════════════════════════════════════════
   CONFIG
   ═══════════════════════════════════════════════════════════ */
$STATUS_META = [
    'pending'     => ['label' => 'Pending Review', 'color' => '#f0ad4e'],
    'confirmed'   => ['label' => 'Confirmed',      'color' => '#0275d8'],
    'in_progress' => ['label' => 'In Workshop',    'color' => '#6f42c1'],
    'completed'   => ['label' => 'Completed',      'color' => 'var(--green)'],
    'cancelled'   => ['label' => 'Cancelled',      'color' => '#d9534f'],
];

$BENEFIT_LABELS = [
    'discount_10'      => '10% Discount',
    'annual_bike_fit'  => 'Annual Bike Fit',
    'deep_clean'       => 'Deep Clean',
    'priority_booking' => 'Priority Booking',
    'other'            => 'Other',
];
$UNLIMITED_BENEFITS = ['discount_10', 'priority_booking'];
$LIMITED_BENEFITS   = ['annual_bike_fit', 'deep_clean'];

/* ═══════════════════════════════════════════════════════════
   HELPERS
   ═══════════════════════════════════════════════════════════ */

/** Cycle start date — falls back to one year before expiry. */
function membershipCycleStart(?string $start, ?string $expiry): string
{
    if (!empty($start))  return date('Y-m-d', strtotime($start));
    if (!empty($expiry)) return date('Y-m-d', strtotime($expiry . ' -1 year'));
    return date('Y-m-d');
}

/** Which benefits are still claimable this cycle. */
function availableBenefits(array $claimsByType, string $cycleStart,
                           array $unlimited, array $limited, array $labels): array
{
    $out = [];
    foreach ($unlimited as $k) $out[$k] = $labels[$k] ?? ucfirst($k);

    foreach ($limited as $k) {
        $last = $claimsByType[$k][0] ?? null;
        $used = $last && strtotime($last['claim_date']) >= strtotime($cycleStart);
        if (!$used) $out[$k] = $labels[$k] ?? ucfirst($k);
    }
    return $out;
}

/** Is this claim already used this cycle? */
function benefitUsedThisCycle(array $claimsByType, string $benefitKey, string $cycleStart): bool
{
    $last = $claimsByType[$benefitKey][0] ?? null;
    return $last && strtotime($last['claim_date']) >= strtotime($cycleStart);
}

/** Load all claims for a set of user IDs, grouped by user → benefit. */
function loadClaimsByUser(PDO $pdo, array $userIds): array
{
    if (empty($userIds)) return [];

    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = $pdo->prepare("
        SELECT user_id, benefit_type, claim_date, notes, branch_id
        FROM membership_claims
        WHERE user_id IN ($placeholders)
        ORDER BY claim_date DESC
    ");
    $stmt->execute($userIds);

    $byUser = [];
    foreach ($stmt->fetchAll() as $c) {
        $byUser[$c['user_id']][$c['benefit_type']][] = $c;
    }
    return $byUser;
}

/* ═══════════════════════════════════════════════════════════
   POST HANDLERS
   ═══════════════════════════════════════════════════════════ */
$selfUrl = strtok($_SERVER['REQUEST_URI'] ?? 'bookings.php', '#');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    /* ─── Update booking status ─── */
    if ($postAction === 'update_status') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';

        if ($bookingId > 0 && isset($STATUS_META[$newStatus])) {
            try {
                // Role scope check for BM
                if (!$isAdmin) {
                    $chk = $pdo->prepare("SELECT branch_id FROM service_bookings WHERE id = ?");
                    $chk->execute([$bookingId]);
                    $b = $chk->fetch();
                    if (!$b || (int)$b['branch_id'] !== $branchId) {
                        throw new Exception('You do not have permission.');
                    }
                }
                $pdo->prepare("UPDATE service_bookings SET status = ?, updated_at = NOW() WHERE id = ?")
                    ->execute([$newStatus, $bookingId]);
                $message = 'Booking status updated to ' . $STATUS_META[$newStatus]['label'] . '.';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }

    /* ─── Claim a membership benefit ─── */
    if ($postAction === 'claim_benefit') {
        $bookingId   = (int)($_POST['booking_id'] ?? 0);
        $benefitType = $_POST['benefit_type'] ?? '';
        $note        = trim($_POST['claim_note'] ?? '');

        if ($bookingId > 0 && array_key_exists($benefitType, $BENEFIT_LABELS)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT sb.user_id, sb.branch_id,
                           u.full_name AS customer_name,
                           u.membership_start, u.membership_expiry
                    FROM service_bookings sb
                    JOIN users u ON sb.user_id = u.id
                    WHERE sb.id = ?
                ");
                $stmt->execute([$bookingId]);
                $row = $stmt->fetch();

                if (!$row) throw new Exception('Booking not found.');
                if (!$isAdmin && (int)$row['branch_id'] !== $branchId) {
                    throw new Exception('You do not have permission to claim for this branch.');
                }
                if (empty($row['membership_expiry']) || strtotime($row['membership_expiry']) <= time()) {
                    throw new Exception('Customer is not an active member.');
                }

                if (in_array($benefitType, $LIMITED_BENEFITS, true)) {
                    $cycleStart = membershipCycleStart($row['membership_start'], $row['membership_expiry']);
                    $chk = $pdo->prepare("
                        SELECT COUNT(*) FROM membership_claims
                        WHERE user_id = ? AND benefit_type = ? AND claim_date >= ?
                    ");
                    $chk->execute([$row['user_id'], $benefitType, $cycleStart . ' 00:00:00']);
                    if ((int)$chk->fetchColumn() > 0) {
                        throw new Exception($BENEFIT_LABELS[$benefitType] . ' has already been claimed this cycle.');
                    }
                }

                $pdo->prepare("
                    INSERT INTO membership_claims
                        (user_id, benefit_type, branch_id, processed_by, related_booking_id, notes)
                    VALUES (?, ?, ?, ?, ?, ?)
                ")->execute([
                    $row['user_id'],
                    $benefitType,
                    $row['branch_id'] ?: null,
                    $user['id'],
                    $bookingId,
                    $note !== '' ? $note : null,
                ]);

                $message = 'Benefit claimed — ' . $BENEFIT_LABELS[$benefitType] . ' for ' . $row['customer_name'] . '.';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Invalid claim request.';
        }
    }
}

/* ═══════════════════════════════════════════════════════════
   LOAD BOOKINGS
   ═══════════════════════════════════════════════════════════ */
$statusFilter = $_GET['status'] ?? '';

$sql = "
    SELECT sb.*,
           u.full_name  AS customer_name,
           u.email      AS customer_email,
           u.phone      AS customer_phone,
           u.membership_start,
           u.membership_expiry,
           b.name       AS branch_name
    FROM service_bookings sb
    JOIN users u    ON sb.user_id  = u.id
    JOIN branches b ON sb.branch_id = b.id
    WHERE 1=1
";
$params = [];

if (!$isAdmin) {
    $sql .= " AND sb.branch_id = ?";
    $params[] = $branchId;
}
if ($statusFilter !== '' && isset($STATUS_META[$statusFilter])) {
    $sql .= " AND sb.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY sb.scheduled_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

/* ── Load claims for all visible customers (one query) ── */
$claimsByUser = loadClaimsByUser($pdo, array_values(array_unique(array_column($bookings, 'user_id'))));

/* ═══════════════════════════════════════════════════════════
   REUSABLE STYLE SNIPPETS
   ═══════════════════════════════════════════════════════════ */
$inputStyle = 'height:38px;padding:0 10px;border:1px solid var(--gray);border-radius:var(--radius);background:#fff;font-size:13px;';
$panelStyle = 'background:#fafafa;border:1px solid var(--gray);border-radius:var(--radius);padding:14px;';
$memberPanelStyle = 'background:#f9fcf3;border:1px solid #dcedc8;border-radius:var(--radius);padding:14px;';

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">

        <!-- PAGE HEADER -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <div>
                <a href="dashboard.php" style="color: var(--gray-dark); font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                    &larr; Back to Dashboard
                </a>
                <h1 style="font-family: var(--font-heading); font-size: 38px; text-transform: uppercase; margin: 0;">
                    Service Bookings
                </h1>
                <p style="color: var(--gray-dark); font-size: 16px; margin-top: 4px;">
                    Manage workshop appointments, fit sessions, and repair jobs.
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="dashboard.php" class="btn btn--outline btn--small" style="height: 38px; padding: 0 16px;">📊 Dashboard</a>
                <a href="orders.php"    class="btn btn--outline btn--small" style="height: 38px; padding: 0 16px;">🛒 Orders</a>
            </div>
        </div>

        <!-- FLASH -->
        <?php if ($message): ?>
            <div style="background:#e8f5e9;color:#2e7d32;padding:14px 18px;border-radius:var(--radius);margin-bottom:20px;border-left:4px solid var(--green);font-weight:500;">
                ✓ <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background:#ffebee;color:#c62828;padding:14px 18px;border-radius:var(--radius);margin-bottom:20px;border-left:4px solid #d32f2f;font-weight:500;">
                ✕ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- FILTER TABS -->
        <div style="display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;">
            <?php
            $filters = [
                ''            => 'All Bookings',
                'pending'     => 'Pending Review',
                'confirmed'   => 'Confirmed',
                'in_progress' => 'In Progress (Workshop)',
                'completed'   => 'Completed',
                'cancelled'   => 'Cancelled',
            ];
            foreach ($filters as $key => $label):
                $active = ($statusFilter === $key);
                $href   = $key === '' ? 'bookings.php' : '?status=' . urlencode($key);
            ?>
                <a href="<?= $href ?>" class="btn btn--small <?= $active ? 'btn--green' : 'btn--outline' ?>"
                   style="height: 36px; font-size: 13px; padding: 0 16px;">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- BOOKING LIST -->
        <?php if (empty($bookings)): ?>
            <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--gray);padding:50px 20px;text-align:center;box-shadow:var(--shadow);">
                <p style="font-size:18px;color:var(--gray-dark);margin-bottom:12px;">No bookings found matching this filter.</p>
                <a href="bookings.php" class="btn btn--small btn--outline">Reset Filter</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($bookings as $booking):
                $bStatus      = $booking['status'];
                $meta         = $STATUS_META[$bStatus] ?? ['label' => ucfirst($bStatus), 'color' => '#68747b'];
                $bColor       = $meta['color'];
                $serviceLabel = ucwords(str_replace('_', ' ', $booking['service_type']));

                /* Membership snapshot */
                $memStart  = $booking['membership_start'];
                $memExpiry = $booking['membership_expiry'];
                $isMember  = !empty($memExpiry) && strtotime($memExpiry) > time();

                $cycleStart    = $isMember ? membershipCycleStart($memStart, $memExpiry) : null;
                $userClaims    = $claimsByUser[$booking['user_id']] ?? [];
                $availBenefits = $isMember
                    ? availableBenefits($userClaims, $cycleStart, $UNLIMITED_BENEFITS, $LIMITED_BENEFITS, $BENEFIT_LABELS)
                    : [];
            ?>
                <div id="booking-<?= $booking['id'] ?>"
                     style="background:#fff;border-radius:var(--radius);border:1px solid var(--gray);padding:20px 24px;box-shadow:var(--shadow);display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:20px;scroll-margin-top:100px;">

                    <!-- LEFT: booking info -->
                    <div style="flex:1;min-width:320px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
                            <h3 style="font-family:var(--font-heading);font-size:20px;margin:0;">
                                <?= htmlspecialchars($booking['customer_name']) ?>
                            </h3>
                            <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;background:<?= $bColor ?>1a;color:<?= $bColor ?>;border:1px solid <?= $bColor ?>;">
                                <?= $meta['label'] ?>
                            </span>
                            <?php if ($isMember): ?>
                                <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;">
                                    💎 Active Member
                                </span>
                            <?php endif; ?>
                        </div>

                        <p style="color:var(--dark);font-weight:600;font-size:15px;margin-bottom:4px;">
                            🛠️ <?= htmlspecialchars($serviceLabel) ?>
                        </p>
                        <p style="color:var(--gray-dark);font-size:13px;margin-bottom:4px;">
                            📅 <strong>Scheduled:</strong> <?= date('l, M d, Y @ h:i A', strtotime($booking['scheduled_date'])) ?>
                        </p>
                        <p style="color:var(--gray-dark);font-size:13px;margin-bottom:4px;">
                            📍 <strong>Branch:</strong> <?= htmlspecialchars($booking['branch_name']) ?> |
                            ✉️ <?= htmlspecialchars($booking['customer_email']) ?>
                            <?= !empty($booking['customer_phone']) ? ' | 📞 ' . htmlspecialchars($booking['customer_phone']) : '' ?>
                        </p>

                        <?php if (!empty($booking['notes'])): ?>
                            <div style="background:var(--light);padding:8px 12px;border-radius:var(--radius);font-size:13px;color:var(--dark);margin-top:8px;border-left:3px solid var(--gray);">
                                <strong>Customer Notes:</strong> <?= htmlspecialchars($booking['notes']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($isMember): ?>
                            <!-- Benefit mini-list -->
                            <div style="<?= $memberPanelStyle ?> margin-top:14px;">
                                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#2e7d32;margin:0 0 8px;">
                                    💎 Benefits This Cycle (since <?= date('M d, Y', strtotime($cycleStart)) ?>)
                                </p>
                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    <?php foreach (array_merge($UNLIMITED_BENEFITS, $LIMITED_BENEFITS) as $key):
                                        $label       = $BENEFIT_LABELS[$key] ?? ucfirst($key);
                                        $isUnlimited = in_array($key, $UNLIMITED_BENEFITS, true);
                                        $used        = !$isUnlimited && benefitUsedThisCycle($userClaims, $key, $cycleStart);
                                        $lastUsed    = $used ? $userClaims[$key][0]['claim_date'] : null;
                                    ?>
                                        <div style="font-size:12px;display:flex;align-items:center;gap:8px;">
                                            <?php if ($isUnlimited): ?>
                                                <span style="color:#2e7d32;">✓</span>
                                                <span><?= $label ?></span>
                                                <span style="color:var(--gray-dark);font-size:11px;">— Unlimited</span>
                                            <?php elseif ($used): ?>
                                                <span style="color:#9e9e9e;">☑</span>
                                                <span style="color:#9e9e9e;"><?= $label ?></span>
                                                <span style="color:var(--gray-dark);font-size:11px;">— Used <?= date('M d, Y', strtotime($lastUsed)) ?></span>
                                            <?php else: ?>
                                                <span style="color:#2e7d32;">✓</span>
                                                <span><?= $label ?></span>
                                                <span style="color:#2e7d32;font-size:11px;font-weight:700;">— Available</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- RIGHT: actions -->
                    <div style="display:flex;flex-direction:column;gap:12px;min-width:280px;">

                        <!-- Update status -->
                        <div style="<?= $panelStyle ?>">
                            <span style="display:block;font-size:12px;font-weight:700;text-transform:uppercase;color:var(--gray-dark);margin-bottom:8px;">
                                Change Service Status
                            </span>
                            <form method="POST" action="<?= htmlspecialchars($selfUrl) ?>#booking-<?= $booking['id'] ?>"
                                  style="display:flex;gap:8px;align-items:center;">
                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                <input type="hidden" name="action" value="update_status">
                                <select name="status" style="<?= $inputStyle ?> flex:1;">
                                    <?php foreach ($STATUS_META as $key => $m): ?>
                                        <option value="<?= $key ?>" <?= $bStatus === $key ? 'selected' : '' ?>>
                                            <?= $m['label'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn--green btn--small" style="height:38px;font-size:13px;padding:0 14px;">Update</button>
                            </form>
                        </div>

                        <!-- Claim benefit -->
                        <?php if ($isMember && $bStatus !== 'cancelled' && !empty($availBenefits)): ?>
                            <div style="<?= $memberPanelStyle ?>">
                                <span style="display:block;font-size:12px;font-weight:700;text-transform:uppercase;color:#2e7d32;margin-bottom:8px;">
                                    💎 Claim Membership Benefit
                                </span>
                                <form method="POST" action="<?= htmlspecialchars($selfUrl) ?>#booking-<?= $booking['id'] ?>"
                                      style="display:flex;flex-direction:column;gap:8px;">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <input type="hidden" name="action" value="claim_benefit">

                                    <select name="benefit_type" required style="<?= $inputStyle ?>">
                                        <option value="">— Select benefit —</option>
                                        <?php foreach ($availBenefits as $key => $label): ?>
                                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <input type="text" name="claim_note" maxlength="255"
                                           placeholder="Optional note (e.g. serial #, remarks)"
                                           style="height:34px;padding:0 10px;border:1px solid var(--gray);border-radius:var(--radius);font-size:12px;">

                                    <button type="submit" class="btn btn--green btn--small"
                                            style="height:38px;font-size:13px;padding:0 14px;"
                                            onclick="return confirm('Mark this benefit as claimed for <?= htmlspecialchars(addslashes($booking['customer_name'])) ?>?');">
                                        ✓ Mark as Claimed
                                    </button>
                                </form>
                            </div>

                        <?php elseif ($isMember && $bStatus !== 'cancelled'): ?>
                            <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:var(--radius);padding:12px 14px;font-size:12px;color:#7d5a00;">
                                ⓘ All cycle-limited benefits already claimed. Only unlimited benefits remain.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p style="margin-top: 32px;">
            <a href="dashboard.php" style="color: var(--green); font-weight: 700;">&larr; Back to Dashboard</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>