<?php
// pages/profile.php - User Profile, Settings, Preseted Addresses & Service Appointments

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php?error=' . urlencode('Please log in to view your profile.'));
    exit;
}

$user = getCurrentUser();
$pdo = getConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'customer';

// Fetch customer bookings
$stmt = $pdo->prepare("
    SELECT sb.*, b.name AS branch_name
    FROM service_bookings sb
    JOIN branches b ON sb.branch_id = b.id
    WHERE sb.user_id = ?
    ORDER BY sb.scheduled_date DESC
");
$stmt->execute([$userId]);
$userBookings = $stmt->fetchAll();

// Fetch customer order count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$orderCount = (int)$stmt->fetchColumn();

// Fetch customer saved addresses
$addresses = getUserAddresses($userId);

$activeTab = $_GET['tab'] ?? 'overview';
$successMessage = $_GET['success'] ?? '';
$errorMessage = $_GET['error'] ?? '';

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<style>
    .profile-nav {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid var(--gray);
        margin-bottom: 28px;
        overflow-x: auto;
    }
    .profile-nav-btn {
        background: none;
        border: none;
        padding: 12px 20px;
        font-family: var(--font-heading);
        font-size: 15px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--gray-dark);
        cursor: pointer;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all var(--transition);
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .profile-nav-btn:hover {
        color: var(--dark);
    }
    .profile-nav-btn.active {
        color: var(--dark);
        border-bottom-color: var(--green);
    }
    .tab-pane {
        display: none;
    }
    .tab-pane.active {
        display: block;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--dark);
        margin-bottom: 6px;
    }
    .form-control {
        width: 100%;
        padding: 12px 14px;
        border: 2px solid var(--gray);
        border-radius: var(--radius);
        font-size: 15px;
        font-family: inherit;
        background-color: #fff;
        color: var(--dark);
        transition: border-color var(--transition);
    }
    .form-control:focus {
        outline: none;
        border-color: var(--green);
    }
    .address-card {
        background: #fff;
        border: 2px solid var(--gray);
        border-radius: var(--radius);
        padding: 20px;
        position: relative;
        transition: all var(--transition);
    }
    .address-card.is-default {
        border-color: var(--green);
        box-shadow: 0 4px 14px rgba(166, 206, 57, 0.2);
    }
    .address-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 2px 8px;
        border-radius: 12px;
        background: var(--light);
        color: var(--dark);
        border: 1px solid var(--gray);
    }
    .address-badge.default-badge {
        background: var(--green);
        color: var(--dark);
        border-color: var(--green);
    }
    .modal-backdrop-custom {
        position: fixed;
        inset: 0;
        background: rgba(18, 23, 27, 0.6);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .modal-backdrop-custom.show {
        display: flex;
    }
    .modal-box {
        background: #fff;
        max-width: 540px;
        width: 100%;
        border-radius: var(--radius);
        padding: 32px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        max-height: 90vh;
        overflow-y: auto;
    }
</style>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1100px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            My Profile & Settings
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Manage your personal information, saved delivery addresses, security, and bookings.
        </p>

        <?php if (!empty($successMessage)): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 16px 20px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid var(--green); font-weight: 600;">
                ✓ <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px 20px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f; font-weight: 600;">
                ✕ <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <nav class="profile-nav" aria-label="Profile Sections">
            <button type="button" class="profile-nav-btn <?= $activeTab === 'overview' ? 'active' : '' ?>" onclick="switchTab('overview')">
                👤 Overview
            </button>
            <button type="button" class="profile-nav-btn <?= $activeTab === 'info' ? 'active' : '' ?>" onclick="switchTab('info')">
                ✏️ Edit Personal Info
            </button>
            <button type="button" class="profile-nav-btn <?= $activeTab === 'addresses' ? 'active' : '' ?>" onclick="switchTab('addresses')">
                📍 Preseted Addresses (<?= count($addresses) ?>)
            </button>
            <button type="button" class="profile-nav-btn <?= $activeTab === 'security' ? 'active' : '' ?>" onclick="switchTab('security')">
                🔒 Password & Security
            </button>
            <button type="button" class="profile-nav-btn <?= $activeTab === 'appointments' ? 'active' : '' ?>" onclick="switchTab('appointments')">
                🛠️ Service Bookings (<?= count($userBookings) ?>)
            </button>
        </nav>

        <!-- =================================================================== -->
        <!-- TAB 1: OVERVIEW -->
        <!-- =================================================================== -->
        <div id="tab-overview" class="tab-pane <?= $activeTab === 'overview' ? 'active' : '' ?>">
            <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 32px; align-items: start;">
                
                <!-- Account Summary Box -->
                <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow);">
                    <h2 style="font-family: var(--font-heading); font-size: 24px; margin-bottom: 20px; text-transform: uppercase;">
                        Account Summary
                    </h2>
                    
                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 15px;">
                        <p><strong>Full Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not set') ?></p>
                        <p><strong>Account Role:</strong> <?= ucfirst($user['role']) ?></p>
                        <p><strong>Member Since:</strong> <?= date('F d, Y', strtotime($user['created_at'])) ?></p>
                        <?php if (!empty($user['membership_expiry'])): ?>
                            <p><strong>Membership Status:</strong> 
                                <span style="color: var(--green); font-weight: 700;">Active</span> (expires <?= date('M d, Y', strtotime($user['membership_expiry'])) ?>)
                            </p>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 10px;">
                        <button type="button" class="btn btn--green btn--small" style="justify-content: center;" onclick="switchTab('info')">
                            ✏️ Edit Profile Details
                        </button>
                        <a href="orders.php" class="btn btn--outline btn--small" style="justify-content: center;">
                            📦 View My Orders (<?= $orderCount ?>)
                        </a>
                        <a href="booking.php" class="btn btn--outline btn--small" style="justify-content: center;">
                            🛠️ Book New Service
                        </a>
                        <?php if ($userRole === 'hq_admin' || $userRole === 'branch_manager'): ?>
                            <a href="admin/dashboard.php" class="btn btn--outline btn--small" style="justify-content: center;">
                                📊 Control Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Primary Delivery Address & Fast Stats -->
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h3 style="font-family: var(--font-heading); font-size: 20px; text-transform: uppercase;">
                                Default Delivery Address
                            </h3>
                            <button type="button" class="btn btn--outline btn--small" onclick="switchTab('addresses')">
                                Manage (<?= count($addresses) ?>)
                            </button>
                        </div>

                        <?php 
                        $defaultAddr = getDefaultUserAddress($userId);
                        if ($defaultAddr): 
                        ?>
                            <div style="background: var(--light); padding: 18px; border-radius: var(--radius); border-left: 4px solid var(--green);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <strong><?= htmlspecialchars($defaultAddr['label']) ?></strong>
                                    <span class="address-badge default-badge">Primary Delivery</span>
                                </div>
                                <p style="font-size: 14px; margin: 2px 0;"><strong>Recipient:</strong> <?= htmlspecialchars($defaultAddr['recipient_name']) ?> (<?= htmlspecialchars($defaultAddr['phone']) ?>)</p>
                                <p style="font-size: 14px; color: var(--gray-dark); margin-top: 4px;"><?= nl2br(htmlspecialchars($defaultAddr['address_line'])) ?></p>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--gray-dark); font-size: 14px; margin-bottom: 16px;">
                                No default delivery address saved yet. Save addresses to enjoy one-click checkout.
                            </p>
                            <button type="button" class="btn btn--green btn--small" onclick="openAddAddressModal()">
                                + Add First Address
                            </button>
                        <?php endif; ?>
                    </div>

                    <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 24px 32px; box-shadow: var(--shadow); display: flex; justify-content: space-around; text-align: center;">
                        <div>
                            <span style="font-family: var(--font-heading); font-size: 32px; font-weight: 900; color: var(--dark); display: block;">
                                <?= $orderCount ?>
                            </span>
                            <span style="font-size: 13px; text-transform: uppercase; color: var(--gray-dark); font-weight: 700;">Total Orders</span>
                        </div>
                        <div style="width: 1px; background: var(--gray);"></div>
                        <div>
                            <span style="font-family: var(--font-heading); font-size: 32px; font-weight: 900; color: var(--dark); display: block;">
                                <?= count($userBookings) ?>
                            </span>
                            <span style="font-size: 13px; text-transform: uppercase; color: var(--gray-dark); font-weight: 700;">Bookings</span>
                        </div>
                        <div style="width: 1px; background: var(--gray);"></div>
                        <div>
                            <span style="font-family: var(--font-heading); font-size: 32px; font-weight: 900; color: var(--dark); display: block;">
                                <?= count($addresses) ?>
                            </span>
                            <span style="font-size: 13px; text-transform: uppercase; color: var(--gray-dark); font-weight: 700;">Saved Addresses</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 2: EDIT PERSONAL INFORMATION -->
        <!-- =================================================================== -->
        <div id="tab-info" class="tab-pane <?= $activeTab === 'info' ? 'active' : '' ?>">
            <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow); max-width: 680px;">
                <h2 style="font-family: var(--font-heading); font-size: 24px; margin-bottom: 8px; text-transform: uppercase;">
                    Edit Personal Information
                </h2>
                <p style="color: var(--gray-dark); font-size: 14px; margin-bottom: 24px;">
                    Keep your contact information up to date. Previous transaction records and historical orders remain safely intact.
                </p>

                <form method="POST" action="profile-handler.php">
                    <input type="hidden" name="action" value="update_info">

                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required value="<?= htmlspecialchars($user['full_name']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email']) ?>">
                        <small style="color: var(--gray-dark); font-size: 12px; margin-top: 4px; display: block;">
                            This is also used as your login credential.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="09171234567 or +639171234567" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        <small style="color: var(--gray-dark); font-size: 12px; margin-top: 4px; display: block;">
                            Used for delivery updates and appointment reminders.
                        </small>
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 28px;">
                        <button type="submit" class="btn btn--green" style="height: 44px; padding: 0 28px;">
                            Save Profile Changes
                        </button>
                        <button type="button" class="btn btn--outline" style="height: 44px; padding: 0 20px;" onclick="switchTab('overview')">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 3: PRESETED ADDRESSES -->
        <!-- =================================================================== -->
        <div id="tab-addresses" class="tab-pane <?= $activeTab === 'addresses' ? 'active' : '' ?>">
            <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
                    <div>
                        <h2 style="font-family: var(--font-heading); font-size: 24px; text-transform: uppercase; margin-bottom: 4px;">
                            Preseted Delivery Addresses
                        </h2>
                        <p style="color: var(--gray-dark); font-size: 14px;">
                            Preset multiple addresses for convenience. Choose which address is your primary default.
                        </p>
                    </div>
                    <button type="button" class="btn btn--green btn--small" onclick="openAddAddressModal()">
                        + Add New Address
                    </button>
                </div>

                <?php if (empty($addresses)): ?>
                    <div style="text-align: center; padding: 48px 20px; background: var(--light); border-radius: var(--radius); border: 1px dashed var(--gray);">
                        <p style="font-size: 18px; color: var(--gray-dark); margin-bottom: 16px;">
                            You don't have any preset addresses yet.
                        </p>
                        <button type="button" class="btn btn--green btn--small" onclick="openAddAddressModal()">
                            + Add Your First Address
                        </button>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                        <?php foreach ($addresses as $addr): 
                            $isDef = (int)$addr['is_default'] === 1;
                        ?>
                            <div class="address-card <?= $isDef ? 'is-default' : '' ?>">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <h3 style="font-family: var(--font-heading); font-size: 18px; margin: 0; text-transform: uppercase;">
                                            <?= htmlspecialchars($addr['label']) ?>
                                        </h3>
                                        <?php if ($isDef): ?>
                                            <span class="address-badge default-badge">Default</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display: flex; gap: 6px;">
                                        <button type="button" class="btn btn--outline btn--small" style="padding: 4px 10px; font-size: 12px;"
                                                onclick='openEditAddressModal(<?= json_encode($addr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            Edit
                                        </button>
                                        <form method="POST" action="profile-handler.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this preset address?');">
                                            <input type="hidden" name="action" value="delete_address">
                                            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                            <button type="submit" class="btn btn--outline btn--small" style="padding: 4px 10px; font-size: 12px; color: #d32f2f; border-color: #d32f2f;">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div style="font-size: 14px; line-height: 1.5; color: var(--dark); margin-bottom: 16px;">
                                    <p><strong>Recipient:</strong> <?= htmlspecialchars($addr['recipient_name']) ?></p>
                                    <p><strong>Phone:</strong> <?= htmlspecialchars($addr['phone']) ?></p>
                                    <p style="color: var(--gray-dark); margin-top: 6px;"><?= nl2br(htmlspecialchars($addr['address_line'])) ?></p>
                                </div>

                                <div style="border-top: 1px solid rgba(159, 164, 168, 0.3); padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <?php if (!$isDef): ?>
                                        <form method="POST" action="profile-handler.php" style="width: 100%;">
                                            <input type="hidden" name="action" value="set_default_address">
                                            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                            <button type="submit" class="btn btn--outline btn--small" style="width: 100%; justify-content: center; font-size: 12px;">
                                                ⭐ Set as Default Address
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #2e7d32; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                            ✓ Active Default for Checkout
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 4: PASSWORD & SECURITY -->
        <!-- =================================================================== -->
        <div id="tab-security" class="tab-pane <?= $activeTab === 'security' ? 'active' : '' ?>">
            <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow); max-width: 600px;">
                <h2 style="font-family: var(--font-heading); font-size: 24px; margin-bottom: 8px; text-transform: uppercase;">
                    Change Password
                </h2>
                <p style="color: var(--gray-dark); font-size: 14px; margin-bottom: 24px;">
                    Ensure your account is using a strong password. You will need to provide your current password to authorize this change.
                </p>

                <form method="POST" action="profile-handler.php">
                    <input type="hidden" name="action" value="update_password">

                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password *</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required autocomplete="new-password">
                        <small style="color: var(--gray-dark); font-size: 12px; margin-top: 4px; display: block;">
                            Must be at least 8 characters with 1 uppercase letter, 1 number, and 1 special character.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="new-password">
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 28px;">
                        <button type="submit" class="btn btn--green" style="height: 44px; padding: 0 28px;">
                            Update Password
                        </button>
                        <button type="button" class="btn btn--outline" style="height: 44px; padding: 0 20px;" onclick="switchTab('overview')">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 5: SERVICE APPOINTMENTS -->
        <!-- =================================================================== -->
        <div id="tab-appointments" class="tab-pane <?= $activeTab === 'appointments' ? 'active' : '' ?>">
            <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
                    <div>
                        <h2 style="font-family: var(--font-heading); font-size: 24px; text-transform: uppercase; margin-bottom: 4px;">
                            My Service Appointments
                        </h2>
                        <p style="color: var(--gray-dark); font-size: 14px;">
                            Track workshop appointments, bike fits, tune-ups, and custom builds.
                        </p>
                    </div>
                    <a href="booking.php" class="btn btn--green btn--small">
                        + Book New Service
                    </a>
                </div>
                
                <?php if (empty($userBookings)): ?>
                    <p style="color: var(--gray-dark); padding: 40px 0; text-align: center;">
                        You have no active or previous service bookings.
                    </p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <?php foreach ($userBookings as $ub): 
                            $bStatus = $ub['status'];
                            $bColor = '#68747b';
                            if ($bStatus === 'pending') $bColor = '#f0ad4e';
                            elseif ($bStatus === 'confirmed') $bColor = '#0275d8';
                            elseif ($bStatus === 'in_progress') $bColor = '#6f42c1';
                            elseif ($bStatus === 'completed') $bColor = 'var(--green)';
                            elseif ($bStatus === 'cancelled') $bColor = '#d9534f';
                        ?>
                            <div style="background: var(--light); padding: 18px 20px; border-radius: var(--radius); border-left: 5px solid <?= $bColor ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <strong style="font-size: 17px; text-transform: capitalize;">
                                        <?= str_replace('_', ' ', $ub['service_type']) ?>
                                    </strong>
                                    <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: <?= $bColor ?>; border: 1px solid <?= $bColor ?>; padding: 2px 10px; border-radius: 12px;">
                                        <?= ucfirst(str_replace('_', ' ', $bStatus)) ?>
                                    </span>
                                </div>
                                <p style="font-size: 14px; color: var(--gray-dark); margin: 2px 0;">
                                    📅 <?= date('l, F d, Y @ h:i A', strtotime($ub['scheduled_date'])) ?>
                                </p>
                                <p style="font-size: 14px; color: var(--gray-dark); margin: 2px 0;">
                                    📍 <?= htmlspecialchars($ub['branch_name']) ?>
                                </p>
                                <?php if (!empty($ub['notes'])): ?>
                                    <p style="font-size: 13px; color: var(--dark); margin-top: 8px; background: #fff; padding: 8px 12px; border-radius: var(--radius); border: 1px solid var(--gray);">
                                        <strong>Notes:</strong> <?= htmlspecialchars($ub['notes']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <p style="margin-top: 32px;">
            <a href="../index.php" style="color: var(--green); font-weight: 700;">&larr; Back to Home</a>
        </p>
    </div>
</div>

<!-- =================================================================== -->
<!-- MODAL: ADD / EDIT PRESET ADDRESS -->
<!-- =================================================================== -->
<div id="address-modal" class="modal-backdrop-custom" onclick="handleBackdropClick(event)">
    <div class="modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="modal-address-title" style="font-family: var(--font-heading); font-size: 22px; text-transform: uppercase; margin: 0;">
                Add Preset Address
            </h3>
            <button type="button" onclick="closeAddressModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--gray-dark);">&times;</button>
        </div>

        <form id="address-form" method="POST" action="profile-handler.php">
            <input type="hidden" name="action" id="modal-action" value="add_address">
            <input type="hidden" name="address_id" id="modal-address-id" value="">

            <div class="form-group">
                <label class="form-label" for="addr_label">Address Label *</label>
                <input type="text" id="addr_label" name="label" class="form-control" placeholder="e.g. Home, Office, Bike Workshop" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="addr_recipient">Recipient Name *</label>
                <input type="text" id="addr_recipient" name="recipient_name" class="form-control" placeholder="Full name of recipient" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="addr_phone">Contact Phone Number *</label>
                <input type="text" id="addr_phone" name="phone" class="form-control" placeholder="e.g. 09171234567" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="addr_line">Delivery Address *</label>
                <textarea id="addr_line" name="address_line" rows="3" class="form-control" placeholder="House/Unit #, Street, Barangay, City, Postal Code" required></textarea>
            </div>

            <div class="form-group">
                <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px;">
                    <input type="checkbox" id="addr_is_default" name="is_default" value="1">
                    Set as my primary default delivery address
                </label>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                <button type="button" class="btn btn--outline" onclick="closeAddressModal()">Cancel</button>
                <button type="submit" class="btn btn--green" id="modal-submit-btn">Save Address</button>
            </div>
        </form>
    </div>
</div>

<script>
// Tab Switching
function switchTab(tabId) {
    document.querySelectorAll('.profile-nav-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));

    const activeBtn = Array.from(document.querySelectorAll('.profile-nav-btn')).find(btn => {
        return btn.getAttribute('onclick') && btn.getAttribute('onclick').includes("'" + tabId + "'");
    });
    if (activeBtn) activeBtn.classList.add('active');

    const activePane = document.getElementById('tab-' + tabId);
    if (activePane) activePane.classList.add('active');

    // Update query param in browser history without reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.replaceState({}, '', url);
}

// Modal handling
function openAddAddressModal() {
    document.getElementById('modal-address-title').textContent = 'Add Preset Address';
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
    document.getElementById('modal-address-title').textContent = 'Edit Preset Address';
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
    if (e.target.id === 'address-modal') {
        closeAddressModal();
    }
}
</script>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>