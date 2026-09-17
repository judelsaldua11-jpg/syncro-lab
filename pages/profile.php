<?php
// pages/profile.php - Profile, Addresses, Membership & Bookings

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php?error=' . urlencode('Please log in.'));
    exit;
}

$user     = getCurrentUser();
$pdo      = getConnection();
$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'customer';

/* ── Data ──────────────────────────────────────────────── */
$stmt = $pdo->prepare("SELECT sb.*, b.name AS branch_name FROM service_bookings sb
                       JOIN branches b ON sb.branch_id = b.id
                       WHERE sb.user_id = ? ORDER BY sb.scheduled_date DESC");
$stmt->execute([$userId]);
$userBookings = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$orderCount = (int)$stmt->fetchColumn();

$addresses   = getUserAddresses($userId);
$defaultAddr = getDefaultUserAddress($userId);

/* ── Booking status workflow ───────────────────────────── */
$BOOKING_STEPS = [
    'pending'     => ['label' => 'Requested',   'color' => '#f0ad4e'],
    'confirmed'   => ['label' => 'Confirmed',   'color' => '#0275d8'],
    'in_progress' => ['label' => 'In Workshop', 'color' => '#6f42c1'],
    'completed'   => ['label' => 'Completed',   'color' => '#2e7d32'],
    'cancelled'   => ['label' => 'Cancelled',   'color' => '#d9534f'],
];

/* ── Membership state ──────────────────────────────────── */
$memStart  = $user['membership_start']  ?? null;
$memExpiry = $user['membership_expiry'] ?? null;
$isActive  = $memExpiry && strtotime($memExpiry) > time();
$isExpired = $memExpiry && !$isActive;
$everMember = $memStart || $memExpiry;

$cycleStart = $isActive ? date('Y-m-d', strtotime($memStart ?: "$memExpiry -1 year")) : null;

/* ── Benefits ──────────────────────────────────────────── */
$BENEFITS = [
    'discount_10'      => ['label' => '10% Service Discount', 'unlimited' => true],
    'priority_booking' => ['label' => 'Priority Booking',     'unlimited' => true],
    'annual_bike_fit'  => ['label' => 'Annual Bike Fit',      'unlimited' => false],
    'deep_clean'       => ['label' => 'Deep Clean',           'unlimited' => false],
];

$claims = $byType = $states = [];
$limitedUsed = 0;

if ($everMember) {
    $stmt = $pdo->prepare("SELECT benefit_type, claim_date, notes, related_order_id, related_booking_id
                           FROM membership_claims WHERE user_id = ? ORDER BY claim_date DESC");
    $stmt->execute([$userId]);
    $claims = $stmt->fetchAll();
    foreach ($claims as $c) $byType[$c['benefit_type']][] = $c;

    foreach ($BENEFITS as $key => $b) {
        $last = $byType[$key][0] ?? null;
        $used = !$b['unlimited'] && $last && $cycleStart
                && strtotime($last['claim_date']) >= strtotime($cycleStart);
        if ($used) $limitedUsed++;
        $states[$key] = ['last' => $last, 'used' => $used, 'count' => count($byType[$key] ?? [])];
    }
}
$limitedTotal = count(array_filter($BENEFITS, fn($b) => !$b['unlimited']));

$activeTab = $_GET['tab'] ?? 'overview';
$success   = $_GET['success'] ?? '';
$error     = $_GET['error'] ?? '';

include __DIR__ . '/../src/Views/layouts/header.php';

$card  = 'background:#fff;border:1px solid var(--gray);border-radius:var(--radius);padding:32px;box-shadow:var(--shadow);';
$label = 'display:block;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;';
$input = 'width:100%;padding:12px 14px;border:2px solid var(--gray);border-radius:var(--radius);font-size:15px;background:#fff;';
$btnRow = 'display:flex;gap:12px;margin-top:28px;';
?>

<style>
    .profile-nav { display:flex; gap:8px; border-bottom:2px solid var(--gray); margin-bottom:28px; overflow-x:auto; }
    .profile-nav-btn { background:none; border:none; padding:12px 20px; font-family:var(--font-heading); font-size:15px; font-weight:700;
        text-transform:uppercase; color:var(--gray-dark); cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px;
        transition:all var(--transition); white-space:nowrap; display:inline-flex; align-items:center; gap:8px; }
    .profile-nav-btn:hover { color:var(--dark); }
    .profile-nav-btn.active { color:var(--dark); border-bottom-color:var(--green); }
    .tab-pane { display:none; }
    .tab-pane.active { display:block; }
    .form-group { margin-bottom:20px; }
    .modal-backdrop-custom { position:fixed; inset:0; background:rgba(18,23,27,0.6); z-index:9999; display:none;
        align-items:center; justify-content:center; padding:20px; }
    .modal-backdrop-custom.show { display:flex; }
    .modal-box { background:#fff; max-width:540px; width:100%; border-radius:var(--radius); padding:32px;
        box-shadow:0 20px 50px rgba(0,0,0,0.25); max-height:90vh; overflow-y:auto; }
    .address-card { background:#fff; border:2px solid var(--gray); border-radius:var(--radius); padding:20px; }
    .address-card.is-default { border-color:var(--green); box-shadow:0 4px 14px rgba(166,206,57,0.2); }
    .address-badge { display:inline-block; font-size:11px; font-weight:700; text-transform:uppercase; padding:2px 8px;
        border-radius:12px; background:var(--light); color:var(--dark); border:1px solid var(--gray); }
    .address-badge.default-badge { background:var(--green); color:var(--dark); border-color:var(--green); }
    .benefit-card { border:2px solid var(--gray); border-radius:var(--radius); padding:18px 20px; background:#fff; }
    .benefit-card.available { border-color:#a5d6a7; background:#f9fcf3; }
    .benefit-card.used { background:var(--light); opacity:0.9; }
    .benefit-card.unlimited { border-color:#b3d4f5; background:#f4f9ff; }
    .booking-timeline { display:flex; align-items:center; gap:6px; margin:14px 0 4px; flex-wrap:wrap; }
    .booking-timeline .tl-step { display:flex; align-items:center; gap:5px; font-size:12px; color:var(--gray-dark); white-space:nowrap; }
    .booking-timeline .tl-step.done    { color:#2e7d32; font-weight:700; }
    .booking-timeline .tl-step.current { color:var(--dark); font-weight:700; }
    .booking-timeline .tl-step.pending { opacity:0.6; }
    .booking-timeline .tl-line { width:18px; height:2px; background:var(--gray); }
    .booking-timeline .tl-line.done { background:#2e7d32; }
    .benefits-link-block { display:block; text-decoration:none; color:inherit; margin-top:12px;
        padding:12px 14px; background:#fff; border-radius:var(--radius); border:1px solid var(--gray);
        transition:border-color 0.15s; cursor:pointer; }
    .benefits-link-block:hover { border-color:var(--green); }
</style>

<div style="padding:40px 0 60px;background:var(--light);min-height:60vh;color:var(--dark);">
<div style="max-width:1100px;margin:0 auto;padding:0 40px;">

    <h1 style="font-family:var(--font-heading);font-size:48px;text-transform:uppercase;margin:0 0 8px;">My Profile &amp; Settings</h1>
    <p style="color:var(--gray-dark);font-size:18px;margin-bottom:32px;">Manage your info, addresses, security, and bookings.</p>

    <?php if ($success): ?>
        <div style="background:#e8f5e9;color:#2e7d32;padding:16px 20px;border-radius:var(--radius);margin-bottom:24px;border-left:4px solid var(--green);font-weight:600;">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:#ffebee;color:#c62828;padding:16px 20px;border-radius:var(--radius);margin-bottom:24px;border-left:4px solid #d32f2f;font-weight:600;">✕ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- TABS -->
    <nav class="profile-nav">
        <?php
        $tabs = [
            'overview'     => '👤 Overview',
            'membership'   => '💎 Membership',
            'info'         => '✏️ Edit Personal Info',
            'addresses'    => '📍 Preseted Addresses (' . count($addresses) . ')',
            'security'     => '🔒 Password &amp; Security',
            'appointments' => '🛠️ Service Bookings (' . count($userBookings) . ')',
        ];
        foreach ($tabs as $key => $labelText):
            $cls = $activeTab === $key ? 'active' : '';
        ?>
            <button type="button" class="profile-nav-btn <?= $cls ?>" onclick="switchTab('<?= $key ?>')"><?= $labelText ?></button>
        <?php endforeach; ?>
    </nav>

    <!-- ═══════════════════════════════════════════════════════
         OVERVIEW
         ═══════════════════════════════════════════════════════ -->
    <div id="tab-overview" class="tab-pane <?= $activeTab === 'overview' ? 'active' : '' ?>">
        <div style="display:grid;grid-template-columns:1fr 1.2fr;gap:32px;align-items:start;">

            <div style="<?= $card ?>">
                <h2 style="font-family:var(--font-heading);font-size:24px;margin:0 0 20px;text-transform:uppercase;">Account Summary</h2>

                <div style="display:flex;flex-direction:column;gap:12px;font-size:15px;">
                    <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not set') ?></p>
                    <p><strong>Role:</strong> <?= ucfirst($user['role']) ?></p>
                    <p style="color:var(--gray-dark);font-size:13px;"><strong>Created:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                </div>

                <!-- Membership block -->
                <div style="margin-top:20px;padding:16px 18px;border:1px solid var(--gray);border-radius:var(--radius);background:var(--light);">
                    <p style="font-size:12px;font-weight:700;text-transform:uppercase;color:var(--gray-dark);margin:0 0 8px;">Membership</p>

                    <?php if ($isActive): ?>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                            <span style="width:10px;height:10px;border-radius:50%;background:#2e7d32;"></span>
                            <strong style="color:#2e7d32;">Active Member</strong>
                        </div>
                        <p style="margin:4px 0;font-size:14px;"><strong>Since:</strong> <?= $memStart ? date('M d, Y', strtotime($memStart)) : '—' ?></p>
                        <p style="margin:4px 0;font-size:14px;"><strong>Until:</strong> <?= date('M d, Y', strtotime($memExpiry)) ?></p>

                        <!-- Clickable progress block -->
                        <a href="#" onclick="switchTab('membership');return false;" class="benefits-link-block">
                            <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:700;color:var(--gray-dark);margin-bottom:6px;">
                                <span>BENEFITS USED</span>
                                <span style="color:var(--dark);"><?= $limitedUsed ?> / <?= $limitedTotal ?></span>
                            </div>
                            <div style="height:6px;background:var(--light);border-radius:3px;overflow:hidden;margin-bottom:8px;">
                                <div style="height:100%;width:<?= $limitedTotal ? round($limitedUsed / $limitedTotal * 100) : 0 ?>%;background:var(--green);"></div>
                            </div>
                            <span style="font-size:12px;color:var(--green);font-weight:700;">View All Benefits →</span>
                        </a>

                    <?php elseif ($isExpired): ?>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                            <span style="width:10px;height:10px;border-radius:50%;background:#c0392b;"></span>
                            <strong style="color:#c0392b;">Expired</strong>
                        </div>
                        <p style="margin:4px 0;font-size:14px;"><strong>Since:</strong> <?= $memStart ? date('M d, Y', strtotime($memStart)) : '—' ?></p>
                        <p style="margin:4px 0;font-size:14px;"><strong>Expired:</strong> <?= date('M d, Y', strtotime($memExpiry)) ?></p>
                        <a href="membership-details.php" class="btn btn--green btn--small" style="margin-top:10px;width:100%;justify-content:center;">🔄 Renew</a>

                    <?php else: ?>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                            <span style="width:10px;height:10px;border-radius:50%;background:#9fa4a8;"></span>
                            <strong style="color:var(--gray-dark);">Not a Member</strong>
                        </div>
                        <p style="margin:4px 0 10px;font-size:13px;color:var(--gray-dark);">Join to unlock discounts, priority bookings &amp; more.</p>
                        <a href="membership-details.php" class="btn btn--green btn--small" style="width:100%;justify-content:center;">✨ Learn More</a>
                    <?php endif; ?>
                </div>

                <div style="margin-top:24px;display:flex;flex-direction:column;gap:10px;">
                    <button type="button" class="btn btn--green btn--small" style="justify-content:center;" onclick="switchTab('info')">✏️ Edit Details</button>
                    <a href="orders.php" class="btn btn--outline btn--small" style="justify-content:center;">📦 My Orders (<?= $orderCount ?>)</a>
                    <a href="booking.php" class="btn btn--outline btn--small" style="justify-content:center;">🛠️ Book Service</a>
                    <?php if (in_array($userRole, ['hq_admin', 'branch_manager'], true)): ?>
                        <a href="admin/dashboard.php" class="btn btn--outline btn--small" style="justify-content:center;">📊 Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right column: Address + Bookings + Stats -->
            <div style="display:flex;flex-direction:column;gap:24px;">

                <!-- 1. Default Address -->
                <div style="<?= $card ?>">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <h3 style="font-family:var(--font-heading);font-size:20px;text-transform:uppercase;margin:0;">Default Delivery Address</h3>
                        <button type="button" class="btn btn--outline btn--small" onclick="switchTab('addresses')">Manage (<?= count($addresses) ?>)</button>
                    </div>

                    <?php if ($defaultAddr): ?>
                        <div style="background:var(--light);padding:18px;border-radius:var(--radius);border-left:4px solid var(--green);">
                            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                                <strong><?= htmlspecialchars($defaultAddr['label']) ?></strong>
                                <span class="address-badge default-badge">Primary Delivery</span>
                            </div>
                            <p style="font-size:14px;margin:2px 0;"><strong><?= htmlspecialchars($defaultAddr['recipient_name']) ?></strong> · <?= htmlspecialchars($defaultAddr['phone']) ?></p>
                            <p style="font-size:14px;color:var(--gray-dark);margin-top:4px;"><?= nl2br(htmlspecialchars($defaultAddr['address_line'])) ?></p>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--gray-dark);font-size:14px;margin-bottom:16px;">No default address saved yet.</p>
                        <button type="button" class="btn btn--green btn--small" onclick="openAddAddressModal()">+ Add First Address</button>
                    <?php endif; ?>
                </div>

                <!-- 2. Service Bookings (under Default Delivery Address) -->
                <div style="<?= $card ?>">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <h3 style="font-family:var(--font-heading);font-size:20px;text-transform:uppercase;margin:0;">Service Bookings</h3>
                        <button type="button" class="btn btn--outline btn--small" onclick="switchTab('appointments')">View All (<?= count($userBookings) ?>)</button>
                    </div>

                    <?php if (empty($userBookings)): ?>
                        <p style="color:var(--gray-dark);font-size:14px;margin-bottom:16px;">No service bookings yet.</p>
                        <a href="booking.php" class="btn btn--green btn--small" style="width:100%;justify-content:center;">+ Book Service</a>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <?php foreach (array_slice($userBookings, 0, 3) as $ub):
                                $meta   = $BOOKING_STEPS[$ub['status']] ?? ['label' => ucfirst($ub['status']), 'color' => '#68747b'];
                                $bColor = $meta['color'];
                            ?>
                                <div style="background:var(--light);padding:12px 14px;border-radius:var(--radius);border-left:3px solid <?= $bColor ?>;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:4px;">
                                        <strong style="font-size:13px;text-transform:capitalize;"><?= str_replace('_', ' ', $ub['service_type']) ?></strong>
                                        <span style="font-size:10px;font-weight:700;text-transform:uppercase;color:#fff;background:<?= $bColor ?>;padding:3px 8px;border-radius:12px;white-space:nowrap;">
                                            <?= $meta['label'] ?>
                                        </span>
                                    </div>
                                    <p style="font-size:11px;color:var(--gray-dark);margin:2px 0;">📅 <?= date('M d, Y @ h:i A', strtotime($ub['scheduled_date'])) ?></p>
                                    <p style="font-size:11px;color:var(--gray-dark);margin:2px 0;">📍 <?= htmlspecialchars($ub['branch_name']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($userBookings) > 3): ?>
                            <button type="button" class="btn btn--outline btn--small"
                                    style="width:100%;justify-content:center;margin-top:12px;font-size:12px;"
                                    onclick="switchTab('appointments')">
                                View all <?= count($userBookings) ?> bookings →
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- 3. Stats -->
                <div style="<?= $card ?>padding:24px 32px;display:flex;justify-content:space-around;text-align:center;">
                    <?php
                    $statList = [
                        ['value' => $orderCount,          'label' => 'Orders'],
                        ['value' => count($userBookings), 'label' => 'Bookings'],
                        ['value' => count($addresses),    'label' => 'Addresses'],
                    ];
                    foreach ($statList as $i => $s):
                        if ($i > 0) echo '<div style="width:1px;background:var(--gray);"></div>';
                    ?>
                        <div>
                            <span style="font-family:var(--font-heading);font-size:32px;font-weight:900;display:block;"><?= $s['value'] ?></span>
                            <span style="font-size:13px;text-transform:uppercase;color:var(--gray-dark);font-weight:700;"><?= $s['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         MEMBERSHIP
         ═══════════════════════════════════════════════════════ -->
    <div id="tab-membership" class="tab-pane <?= $activeTab === 'membership' ? 'active' : '' ?>">
        <div style="<?= $card ?>">

            <?php if (!$everMember): ?>
                <div style="text-align:center;padding:48px 20px;">
                    <div style="font-size:56px;margin-bottom:12px;">💎</div>
                    <h2 style="font-family:var(--font-heading);font-size:24px;text-transform:uppercase;margin:0 0 8px;">Unlock Membership Benefits</h2>
                    <p style="color:var(--gray-dark);max-width:520px;margin:0 auto 20px;">Service discounts, priority bookings, annual bike fit, and a free deep clean.</p>
                    <a href="membership-details.php" class="btn btn--green btn--small">✨ Learn About Membership</a>
                </div>

            <?php elseif ($isExpired): ?>
                <div style="text-align:center;padding:48px 20px;">
                    <div style="font-size:56px;margin-bottom:12px;">⏰</div>
                    <h2 style="font-family:var(--font-heading);font-size:24px;text-transform:uppercase;margin:0 0 8px;color:#c0392b;">Membership Expired</h2>
                    <p style="color:var(--gray-dark);max-width:520px;margin:0 auto 20px;">
                        Expired on <strong><?= date('F d, Y', strtotime($memExpiry)) ?></strong>. Renew to keep your benefits.
                    </p>
                    <a href="membership-details.php" class="btn btn--green btn--small">🔄 Renew Membership</a>
                </div>

            <?php else: ?>
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:20px;">
                    <div>
                        <h2 style="font-family:var(--font-heading);font-size:24px;text-transform:uppercase;margin:0 0 4px;">💎 Membership Benefits</h2>
                        <p style="color:var(--gray-dark);font-size:14px;margin:0;">
                            Active until <strong><?= date('M d, Y', strtotime($memExpiry)) ?></strong>
                            &bull; Cycle started <strong><?= date('M d, Y', strtotime($cycleStart)) ?></strong>
                        </p>
                    </div>
                    <div style="background:#f9fcf3;border:1px solid #a5d6a7;border-radius:var(--radius);padding:10px 16px;">
                        <p style="font-size:11px;font-weight:700;color:var(--gray-dark);text-transform:uppercase;margin:0 0 4px;">Progress</p>
                        <p style="font-family:var(--font-heading);font-size:22px;color:#2e7d32;margin:0;"><?= $limitedUsed ?> / <?= $limitedTotal ?></p>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:20px;">
                    <?php foreach ($BENEFITS as $key => $b):
                        $s = $states[$key];
                        if ($b['unlimited'])          $cls = 'unlimited';
                        elseif ($s['used'])           $cls = 'used';
                        else                          $cls = 'available';
                    ?>
                        <div class="benefit-card <?= $cls ?>">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                                <strong style="font-size:15px;"><?= htmlspecialchars($b['label']) ?></strong>
                                <?php if ($b['unlimited']): ?>
                                    <span style="font-size:11px;font-weight:700;color:#0275d8;background:#e3f2fd;border:1px solid #90caf9;border-radius:12px;padding:2px 8px;">♾️ Unlimited</span>
                                <?php elseif ($s['used']): ?>
                                    <span style="font-size:11px;font-weight:700;color:var(--gray-dark);background:#f5f5f5;border:1px solid var(--gray);border-radius:12px;padding:2px 8px;">☑ Used</span>
                                <?php else: ?>
                                    <span style="font-size:11px;font-weight:700;color:#2e7d32;background:#e8f5e9;border:1px solid #a5d6a7;border-radius:12px;padding:2px 8px;">✓ Available</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($s['last']): ?>
                                <p style="font-size:12px;color:var(--gray-dark);margin:0;">
                                    Last used <strong><?= date('M d, Y', strtotime($s['last']['claim_date'])) ?></strong>
                                    <?= $b['unlimited'] ? ' · ' . $s['count'] . ' total' : '' ?>
                                </p>
                            <?php else: ?>
                                <p style="font-size:12px;color:#2e7d32;margin:0;font-weight:600;">Ready to use</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="background:#f4f9ff;border:1px solid #b3d4f5;border-radius:var(--radius);padding:14px 18px;margin-bottom:20px;">
                    <p style="margin:0;font-size:13px;color:var(--dark);">
                        💡 <strong>How to claim:</strong> Visit any SYNCRO LAB branch and present your account.
                        Our staff will record the benefit for you in person.
                    </p>
                </div>

                <h3 style="font-family:var(--font-heading);font-size:18px;text-transform:uppercase;margin:0 0 12px;">📜 Claim History</h3>

                <?php if (empty($claims)): ?>
                    <p style="color:var(--gray-dark);text-align:center;padding:20px;background:var(--light);border-radius:var(--radius);">No claims yet.</p>
                <?php else: ?>
                    <div style="border:1px solid var(--gray);border-radius:var(--radius);overflow:hidden;">
                        <table style="width:100%;border-collapse:collapse;font-size:14px;">
                            <thead style="background:var(--light);">
                                <tr>
                                    <?php foreach (['Date','Benefit','Applied To','Notes'] as $h): ?>
                                        <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--gray-dark);"><?= $h ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($claims, 0, 10) as $c):
                                    $applied = !empty($c['related_order_id'])
                                        ? 'Order #' . str_pad($c['related_order_id'], 5, '0', STR_PAD_LEFT)
                                        : (!empty($c['related_booking_id'])
                                            ? 'Booking #' . str_pad($c['related_booking_id'], 5, '0', STR_PAD_LEFT)
                                            : '—');
                                ?>
                                    <tr style="border-top:1px solid var(--gray);">
                                        <td style="padding:10px 14px;white-space:nowrap;"><?= date('M d, Y', strtotime($c['claim_date'])) ?></td>
                                        <td style="padding:10px 14px;">
                                            <span style="font-size:11px;font-weight:700;background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;border-radius:20px;padding:2px 9px;">
                                                <?= htmlspecialchars($BENEFITS[$c['benefit_type']]['label'] ?? $c['benefit_type']) ?>
                                            </span>
                                        </td>
                                        <td style="padding:10px 14px;color:var(--gray-dark);"><?= $applied ?></td>
                                        <td style="padding:10px 14px;font-size:12px;color:var(--gray-dark);"><?= htmlspecialchars(mb_strimwidth($c['notes'] ?? '', 0, 50, '…')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         EDIT INFO
         ═══════════════════════════════════════════════════════ -->
    <div id="tab-info" class="tab-pane <?= $activeTab === 'info' ? 'active' : '' ?>">
        <div style="<?= $card ?>max-width:680px;">
            <h2 style="font-family:var(--font-heading);font-size:24px;margin:0 0 8px;text-transform:uppercase;">Edit Personal Information</h2>
            <p style="color:var(--gray-dark);font-size:14px;margin-bottom:24px;">Keep your contact info up to date.</p>

            <form method="POST" action="profile-handler.php">
                <input type="hidden" name="action" value="update_info">
                <div class="form-group">
                    <label style="<?= $label ?>" for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>" style="<?= $input ?>">
                </div>
                <div class="form-group">
                    <label style="<?= $label ?>" for="email">Email *</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>" style="<?= $input ?>">
                </div>
                <div class="form-group">
                    <label style="<?= $label ?>" for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" placeholder="09171234567" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" style="<?= $input ?>">
                </div>
                <div style="<?= $btnRow ?>">
                    <button type="submit" class="btn btn--green" style="height:44px;padding:0 28px;">Save Changes</button>
                    <button type="button" class="btn btn--outline" style="height:44px;padding:0 20px;" onclick="switchTab('overview')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ADDRESSES
         ═══════════════════════════════════════════════════════ -->
    <div id="tab-addresses" class="tab-pane <?= $activeTab === 'addresses' ? 'active' : '' ?>">
        <div style="<?= $card ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
                <div>
                    <h2 style="font-family:var(--font-heading);font-size:24px;text-transform:uppercase;margin:0 0 4px;">Preseted Addresses</h2>
                    <p style="color:var(--gray-dark);font-size:14px;margin:0;">Choose your primary default for checkout.</p>
                </div>
                <button type="button" class="btn btn--green btn--small" onclick="openAddAddressModal()">+ Add Address</button>
            </div>

            <?php if (empty($addresses)): ?>
                <div style="text-align:center;padding:48px 20px;background:var(--light);border-radius:var(--radius);border:1px dashed var(--gray);">
                    <p style="font-size:18px;color:var(--gray-dark);margin-bottom:16px;">No addresses saved yet.</p>
                    <button type="button" class="btn btn--green btn--small" onclick="openAddAddressModal()">+ Add Your First</button>
                </div>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;">
                    <?php foreach ($addresses as $addr):
                        $isDef = (int)$addr['is_default'] === 1;
                    ?>
                        <div class="address-card <?= $isDef ? 'is-default' : '' ?>">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <h3 style="font-family:var(--font-heading);font-size:18px;margin:0;text-transform:uppercase;"><?= htmlspecialchars($addr['label']) ?></h3>
                                    <?php if ($isDef): ?>
                                        <span class="address-badge default-badge">Default</span>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex;gap:6px;">
                                    <button type="button" class="btn btn--outline btn--small" style="padding:4px 10px;font-size:12px;"
                                            onclick='openEditAddressModal(<?= json_encode($addr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>Edit</button>
                                    <form method="POST" action="profile-handler.php" style="display:inline;" onsubmit="return confirm('Delete this address?');">
                                        <input type="hidden" name="action" value="delete_address">
                                        <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                        <button type="submit" class="btn btn--outline btn--small" style="padding:4px 10px;font-size:12px;color:#d32f2f;border-color:#d32f2f;">Delete</button>
                                    </form>
                                </div>
                            </div>

                            <div style="font-size:14px;line-height:1.5;margin-bottom:16px;">
                                <p><strong>Recipient:</strong> <?= htmlspecialchars($addr['recipient_name']) ?></p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($addr['phone']) ?></p>
                                <p style="color:var(--gray-dark);margin-top:6px;"><?= nl2br(htmlspecialchars($addr['address_line'])) ?></p>
                            </div>

                            <div style="border-top:1px solid rgba(159,164,168,0.3);padding-top:12px;">
                                <?php if (!$isDef): ?>
                                    <form method="POST" action="profile-handler.php">
                                        <input type="hidden" name="action" value="set_default_address">
                                        <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                        <button type="submit" class="btn btn--outline btn--small" style="width:100%;justify-content:center;font-size:12px;">⭐ Set as Default</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:12px;color:#2e7d32;font-weight:700;">✓ Active Default for Checkout</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         SECURITY
         ═══════════════════════════════════════════════════════ -->
    <div id="tab-security" class="tab-pane <?= $activeTab === 'security' ? 'active' : '' ?>">
        <div style="<?= $card ?>max-width:600px;">
            <h2 style="font-family:var(--font-heading);font-size:24px;margin:0 0 8px;text-transform:uppercase;">Change Password</h2>
            <p style="color:var(--gray-dark);font-size:14px;margin-bottom:24px;">You'll need your current password to authorize this change.</p>

            <form method="POST" action="profile-handler.php">
                <input type="hidden" name="action" value="update_password">
                <div class="form-group">
                    <label style="<?= $label ?>" for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password" style="<?= $input ?>">
                </div>
                <div class="form-group">
                    <label style="<?= $label ?>" for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required autocomplete="new-password" style="<?= $input ?>">
                    <small style="color:var(--gray-dark);font-size:12px;">Min 8 chars, 1 uppercase, 1 number, 1 special.</small>
                </div>
                <div class="form-group">
                    <label style="<?= $label ?>" for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" style="<?= $input ?>">
                </div>
                <div style="<?= $btnRow ?>">
                    <button type="submit" class="btn btn--green" style="height:44px;padding:0 28px;">Update Password</button>
                    <button type="button" class="btn btn--outline" style="height:44px;padding:0 20px;" onclick="switchTab('overview')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         APPOINTMENTS
         ═══════════════════════════════════════════════════════ -->
    <div id="tab-appointments" class="tab-pane <?= $activeTab === 'appointments' ? 'active' : '' ?>">
        <div style="<?= $card ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
                <div>
                    <h2 style="font-family:var(--font-heading);font-size:24px;text-transform:uppercase;margin:0 0 4px;">My Service Appointments</h2>
                    <p style="color:var(--gray-dark);font-size:14px;margin:0;">Track workshop appointments, fits, tune-ups, custom builds.</p>
                </div>
                <a href="booking.php" class="btn btn--green btn--small">+ Book Service</a>
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap;padding:12px 16px;background:var(--light);border-radius:var(--radius);margin-bottom:24px;font-size:12px;">
                <span style="color:var(--gray-dark);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Status guide:</span>
                <?php foreach ($BOOKING_STEPS as $key => $meta): ?>
                    <span style="display:inline-flex;align-items:center;gap:6px;">
                        <span style="width:8px;height:8px;border-radius:50%;background:<?= $meta['color'] ?>;"></span>
                        <span style="color:var(--dark);font-weight:600;"><?= $meta['label'] ?></span>
                    </span>
                <?php endforeach; ?>
            </div>

            <?php if (empty($userBookings)): ?>
                <div style="text-align:center;padding:40px 20px;background:var(--light);border-radius:var(--radius);">
                    <p style="font-size:17px;color:var(--gray-dark);margin-bottom:12px;">No bookings yet.</p>
                    <a href="booking.php" class="btn btn--green btn--small">+ Book Your First Service</a>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <?php
                    $TIMELINE = ['Requested', 'Confirmed', 'In Workshop', 'Completed'];
                    $stepIndex = ['pending' => 0, 'confirmed' => 1, 'in_progress' => 2, 'completed' => 3];

                    foreach ($userBookings as $ub):
                        $isCancelled = ($ub['status'] === 'cancelled');
                        $meta        = $BOOKING_STEPS[$ub['status']] ?? ['label' => ucfirst($ub['status']), 'color' => '#68747b'];
                        $bColor      = $meta['color'];
                        $currentStep = $stepIndex[$ub['status']] ?? -1;
                    ?>
                        <div style="background:#fff;border:1px solid var(--gray);border-left:5px solid <?= $bColor ?>;border-radius:var(--radius);padding:20px 24px;box-shadow:var(--shadow);">

                            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
                                <div>
                                    <strong style="font-size:17px;text-transform:capitalize;display:block;">🛠️ <?= str_replace('_', ' ', $ub['service_type']) ?></strong>
                                    <span style="font-size:12px;color:var(--gray-dark);">Booking #<?= str_pad($ub['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                </div>
                                <span style="font-size:12px;font-weight:700;text-transform:uppercase;color:#fff;background:<?= $bColor ?>;padding:6px 14px;border-radius:20px;white-space:nowrap;">
                                    <?= $isCancelled ? '✕ Cancelled' : $meta['label'] ?>
                                </span>
                            </div>

                            <?php if (!$isCancelled): ?>
                                <div class="booking-timeline">
                                    <?php foreach ($TIMELINE as $i => $stepLabel):
                                        $isDone    = ($currentStep > $i);
                                        $isCurrent = ($currentStep === $i);
                                        $cls       = $isDone ? 'done' : ($isCurrent ? 'current' : 'pending');
                                    ?>
                                        <?php if ($i > 0): ?>
                                            <div class="tl-line <?= $isDone || $isCurrent ? 'done' : '' ?>"></div>
                                        <?php endif; ?>
                                        <div class="tl-step <?= $cls ?>">
                                            <?= $isDone ? '✅' : ($isCurrent ? '🔄' : '⏳') ?> <?= $stepLabel ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div style="margin:12px 0;padding:10px 14px;background:#ffebee;border-left:3px solid #d9534f;border-radius:var(--radius);font-size:13px;color:#c62828;">
                                    This booking has been cancelled.
                                </div>
                            <?php endif; ?>

                            <div style="margin-top:14px;display:flex;flex-direction:column;gap:6px;font-size:14px;">
                                <p style="margin:0;color:var(--gray-dark);">📅 <strong>Scheduled:</strong> <span style="color:var(--dark);"><?= date('l, F d, Y @ h:i A', strtotime($ub['scheduled_date'])) ?></span></p>
                                <p style="margin:0;color:var(--gray-dark);">📍 <strong>Branch:</strong> <span style="color:var(--dark);"><?= htmlspecialchars($ub['branch_name']) ?></span></p>
                            </div>

                            <?php if (!empty($ub['notes'])): ?>
                                <div style="margin-top:12px;padding:10px 14px;background:var(--light);border-radius:var(--radius);border-left:3px solid var(--gray);font-size:13px;">
                                    <strong>Notes:</strong> <?= htmlspecialchars($ub['notes']) ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            $helperText = [
                                'pending'     => 'Your booking is waiting for confirmation from the branch.',
                                'confirmed'   => 'Your slot is reserved. Please arrive on time.',
                                'in_progress' => 'Your bike is currently being worked on.',
                                'completed'   => 'All done! Thanks for choosing SYNCRO LAB.',
                            ][$ub['status']] ?? '';
                            if ($helperText):
                            ?>
                                <p style="margin:12px 0 0;font-size:12px;color:var(--gray-dark);font-style:italic;">ℹ️ <?= $helperText ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <p style="margin-top:32px;"><a href="../index.php" style="color:var(--green);font-weight:700;">&larr; Back to Home</a></p>
</div>
</div>

<!-- ADDRESS MODAL -->
<div id="address-modal" class="modal-backdrop-custom" onclick="handleBackdropClick(event)">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="modal-address-title" style="font-family:var(--font-heading);font-size:22px;text-transform:uppercase;margin:0;">Add Address</h3>
            <button type="button" onclick="closeAddressModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:var(--gray-dark);">&times;</button>
        </div>

        <form id="address-form" method="POST" action="profile-handler.php">
            <input type="hidden" name="action" id="modal-action" value="add_address">
            <input type="hidden" name="address_id" id="modal-address-id" value="">

            <?php
            $fields = [
                ['addr_label',     'label',          'Address Label *',  'text', 'e.g. Home, Office'],
                ['addr_recipient', 'recipient_name', 'Recipient Name *', 'text', 'Full name'],
                ['addr_phone',     'phone',          'Contact Phone *',  'text', 'e.g. 09171234567'],
            ];
            foreach ($fields as [$id, $name, $lbl, $type, $ph]):
            ?>
                <div class="form-group">
                    <label style="<?= $label ?>" for="<?= $id ?>"><?= $lbl ?></label>
                    <input type="<?= $type ?>" id="<?= $id ?>" name="<?= $name ?>" placeholder="<?= $ph ?>" required style="<?= $input ?>">
                </div>
            <?php endforeach; ?>

            <div class="form-group">
                <label style="<?= $label ?>" for="addr_line">Delivery Address *</label>
                <textarea id="addr_line" name="address_line" rows="3" required placeholder="House/Unit #, Street, Barangay, City, Postal Code" style="<?= $input ?>"></textarea>
            </div>

            <div class="form-group">
                <label style="font-weight:700;display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
                    <input type="checkbox" id="addr_is_default" name="is_default" value="1">
                    Set as primary delivery address
                </label>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;justify-content:flex-end;">
                <button type="button" class="btn btn--outline" onclick="closeAddressModal()">Cancel</button>
                <button type="submit" class="btn btn--green" id="modal-submit-btn">Save Address</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.profile-nav-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

    const btn = Array.from(document.querySelectorAll('.profile-nav-btn'))
        .find(b => b.getAttribute('onclick')?.includes("'" + tabId + "'"));
    btn?.classList.add('active');
    document.getElementById('tab-' + tabId)?.classList.add('active');

    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.replaceState({}, '', url);
}

function openAddAddressModal() {
    document.getElementById('modal-address-title').textContent = 'Add Address';
    document.getElementById('modal-action').value = 'add_address';
    document.getElementById('modal-address-id').value = '';
    document.getElementById('addr_label').value = '';
    document.getElementById('addr_recipient').value = '<?= htmlspecialchars(addslashes($user['full_name'])) ?>';
    document.getElementById('addr_phone').value = '<?= htmlspecialchars(addslashes($user['phone'] ?? '')) ?>';
    document.getElementById('addr_line').value = '';
    document.getElementById('addr_is_default').checked = <?= empty($addresses) ? 'true' : 'false' ?>;
    document.getElementById('modal-submit-btn').textContent = 'Save Address';
    document.getElementById('address-modal').classList.add('show');
}

function openEditAddressModal(addr) {
    document.getElementById('modal-address-title').textContent = 'Edit Address';
    document.getElementById('modal-action').value = 'edit_address';
    document.getElementById('modal-address-id').value = addr.id;
    document.getElementById('addr_label').value = addr.label || '';
    document.getElementById('addr_recipient').value = addr.recipient_name || '';
    document.getElementById('addr_phone').value = addr.phone || '';
    document.getElementById('addr_line').value = addr.address_line || '';
    document.getElementById('addr_is_default').checked = parseInt(addr.is_default, 10) === 1;
    document.getElementById('modal-submit-btn').textContent = 'Update Address';
    document.getElementById('address-modal').classList.add('show');
}

function closeAddressModal() {
    document.getElementById('address-modal').classList.remove('show');
}

function handleBackdropClick(e) {
    if (e.target.id === 'address-modal') closeAddressModal();
}
</script>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>