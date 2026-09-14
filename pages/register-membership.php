<?php
// pages/register-membership.php - Membership Registration / Renewal

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to register for membership.');
    exit;
}

$userId        = $_SESSION['user_id'];
$user          = getCurrentUser();
$isRenewal     = isset($_GET['action']) && $_GET['action'] === 'renew';
$isActiveMember = isMembershipActive($userId);

// If already a member and not renewing, redirect to details
if ($isActiveMember && !$isRenewal) {
    header('Location: membership-details.php?message=You are already a member.');
    exit;
}

$error   = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

$membershipPrice = getMembershipPrice();
$membershipDurationMonths = getMembershipDurationMonths();

// Pre-compute renewal expiry info
$renewalInfo = null;
if ($isRenewal && $isActiveMember) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT membership_expiry FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentExpiry = $stmt->fetchColumn();

    $now        = new DateTime();
    $expiryDate = new DateTime($currentExpiry);

    if ($expiryDate > $now) {
        $newExpiry  = clone $expiryDate;
        $newExpiry->modify("+{$membershipDurationMonths} months");
        $renewalInfo = "Extends to: " . $newExpiry->format('F d, Y');
    } else {
        $newExpiry = new DateTime();
        $newExpiry->modify("+{$membershipDurationMonths} months");
        $renewalInfo = "New expiry: " . $newExpiry->format('F d, Y');
    }
}

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<style>
.payment-option {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border: 2px solid var(--gray);
    border-radius: var(--radius);
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    background: #fff;
}
.payment-option:hover {
    background: #f9f9f9;
}
</style>

<div style="padding: 60px 0; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 40px;">

        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            <?= $isRenewal ? 'Renew Membership' : 'Register for Membership' ?>
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            <?= $isRenewal ? 'Extend your membership for another year.' : 'Join SYNCRO LAB Membership today!' ?>
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

        <form method="POST" action="membership-checkout.php" id="membershipForm"
              style="background: #fff; padding: 32px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">

            <input type="hidden" name="action" value="<?= $isRenewal ? 'renew' : 'register' ?>">

            <div style="display: grid; gap: 24px;">

                <!-- User Info (Read-only) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="font-weight: 700; display: block; margin-bottom: 4px;">Full Name</label>
                        <p style="padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); background: var(--light); color: var(--gray-dark); margin: 0;">
                            <?= htmlspecialchars($user['full_name']) ?>
                        </p>
                    </div>
                    <div>
                        <label style="font-weight: 700; display: block; margin-bottom: 4px;">Email</label>
                        <p style="padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); background: var(--light); color: var(--gray-dark); margin: 0;">
                            <?= htmlspecialchars($user['email']) ?>
                        </p>
                    </div>
                </div>

                <!-- Membership Benefits Summary -->
                <div style="background: var(--light); padding: 16px 20px; border-radius: var(--radius);">
                    <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 8px;">Membership Benefits</h3>
                    <ul style="margin: 0; padding-left: 20px; color: var(--gray-dark); line-height: 1.8;">
                        <li>✅ Guaranteed 24-hour repair turnaround</li>
                        <li>✅ Annual free laser bike fit</li>
                        <li>✅ Free deep clean once per year</li>
                        <li>✅ 10% discount on service labor</li>
                    </ul>
                </div>

                <!-- Price -->
                <div style="background: var(--dark); color: var(--light); padding: 20px; border-radius: var(--radius); text-align: center;">
                    <p style="font-size: 14px; color: var(--gray); margin: 0 0 4px;">
                        <?= $isRenewal ? 'Renewal Fee' : 'Registration Fee' ?>
                    </p>
                    <p style="font-family: var(--font-heading); font-size: 36px; color: var(--green); margin: 0;">₱ <?= number_format($membershipPrice, 2) ?></p>
                    <p style="font-size: 14px; color: var(--gray); margin: 4px 0 0;">Valid for <?= $membershipDurationMonths ?> month<?= $membershipDurationMonths > 1 ? 's' : '' ?></p>
                    <?php if ($renewalInfo): ?>
                        <p style="font-size: 13px; color: var(--green); margin-top: 8px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 8px;">
                            📅 <?= $renewalInfo ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- ─── Payment Method Selection ─── -->
                <div>
                    <label style="font-weight: 700; display: block; margin-bottom: 12px; font-size: 16px;">
                        💳 Select Payment Method
                    </label>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;" id="paymentOptions">

                        <!-- GCash -->
                        <label class="payment-option" for="pay_gcash" id="label_gcash">
                            <input type="radio" id="pay_gcash" name="payment_method" value="gcash" required
                                   style="accent-color: #007bff; width: 18px; height: 18px; cursor: pointer;"
                                   onchange="selectPayment(this)">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 22px;">💙</span>
                                    <strong style="font-size: 15px; color: #0070BA;">GCash</strong>
                                </div>
                                <p style="font-size: 12px; color: var(--gray-dark); margin: 2px 0 0;">E-Wallet</p>
                            </div>
                        </label>

                        <!-- Maya -->
                        <label class="payment-option" for="pay_maya" id="label_maya">
                            <input type="radio" id="pay_maya" name="payment_method" value="maya" required
                                   style="accent-color: #00c070; width: 18px; height: 18px; cursor: pointer;"
                                   onchange="selectPayment(this)">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 22px;">💚</span>
                                    <strong style="font-size: 15px; color: #00a651;">Maya</strong>
                                </div>
                                <p style="font-size: 12px; color: var(--gray-dark); margin: 2px 0 0;">E-Wallet</p>
                            </div>
                        </label>

                        <!-- Card -->
                        <label class="payment-option" for="pay_card" id="label_card">
                            <input type="radio" id="pay_card" name="payment_method" value="card" required
                                   style="accent-color: #555; width: 18px; height: 18px; cursor: pointer;"
                                   onchange="selectPayment(this)">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 22px;">💳</span>
                                    <strong style="font-size: 15px; color: #333;">Card</strong>
                                </div>
                                <p style="font-size: 12px; color: var(--gray-dark); margin: 2px 0 0;">Debit / Credit</p>
                            </div>
                        </label>

                    </div>

                    <!-- E-Wallet Number Input (shown for GCash / Maya) -->
                    <div id="walletSection" style="display: none; margin-top: 14px;">
                        <label for="wallet_number" style="font-weight: 700; display: block; margin-bottom: 6px; font-size: 14px;">
                            <span id="walletLabel">Wallet</span> Mobile Number
                        </label>
                        <input type="tel" id="wallet_number" name="wallet_number"
                               placeholder="09XXXXXXXXX"
                               maxlength="11"
                               pattern="09[0-9]{9}"
                               style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; box-sizing: border-box;"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <small style="color: var(--gray-dark); font-size: 12px;">
                            Enter the mobile number linked to your <span class="wallet-name-hint">wallet</span> account.
                        </small>
                    </div>

                    <!-- Card Details (shown for Card) -->
                    <div id="cardSection" style="display: none; margin-top: 14px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div style="grid-column: 1 / -1;">
                                <label style="font-weight: 700; display: block; margin-bottom: 6px; font-size: 14px;">Cardholder Name</label>
                                <input type="text" id="card_name" name="card_name"
                                       placeholder="Name on card"
                                       style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 15px; box-sizing: border-box;">
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label style="font-weight: 700; display: block; margin-bottom: 6px; font-size: 14px;">Card Number</label>
                                <input type="text" id="card_number" name="card_number"
                                       placeholder="•••• •••• •••• ••••"
                                       maxlength="19"
                                       style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; letter-spacing: 2px; box-sizing: border-box;"
                                       oninput="formatCard(this)">
                            </div>
                            <div>
                                <label style="font-weight: 700; display: block; margin-bottom: 6px; font-size: 14px;">Expiry (MM/YY)</label>
                                <input type="text" id="card_expiry" name="card_expiry"
                                       placeholder="MM/YY"
                                       maxlength="5"
                                       style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 15px; box-sizing: border-box;"
                                       oninput="formatExpiry(this)">
                            </div>
                            <div>
                                <label style="font-weight: 700; display: block; margin-bottom: 6px; font-size: 14px;">CVV</label>
                                <input type="password" id="card_cvv" name="card_cvv"
                                       placeholder="•••"
                                       maxlength="4"
                                       style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 15px; box-sizing: border-box;"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                        </div>
                        <small style="color: var(--gray-dark); font-size: 12px; margin-top: 6px; display: block;">
                            🔒 Your card details are processed securely.
                        </small>
                    </div>

                </div>

                <!-- Terms & Conditions -->
                <div>
                    <details style="margin: 0 0 12px; cursor: pointer;">
                        <summary style="font-weight: 600; color: var(--green);">📜 View Terms &amp; Conditions</summary>
                        <div style="padding: 16px; background: var(--light); border-radius: var(--radius); margin-top: 8px; max-height: 200px; overflow-y: auto; font-size: 14px; color: var(--gray-dark); line-height: 1.6;">
                            <p><strong>1. Membership Duration</strong><br>
                            Membership is valid for 12 months from the date of registration.</p>

                            <p><strong>2. Benefits</strong><br>
                            Members receive: guaranteed 24-hour repair turnaround, annual free laser bike fit, free deep clean once per year, and 10% discount on service labor.</p>

                            <p><strong>3. Renewal</strong><br>
                            Membership can be renewed before expiry. Renewal extends membership by 12 months from the current expiry date.</p>

                            <p><strong>4. Cancellation</strong><br>
                            Membership fees are non-refundable. Members may cancel but will not receive a refund.</p>

                            <p><strong>5. Changes</strong><br>
                            SYNCRO LAB reserves the right to modify benefits with prior notice to members.</p>
                        </div>
                    </details>

                    <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="terms" required>
                        I agree to the membership terms and conditions
                    </label>
                </div>

                <!-- Buttons -->
                <div style="display: flex; gap: 16px; margin-top: 8px;">
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;"
                            onclick="return validateForm(event)">
                        PROCEED TO CHECKOUT &rarr;
                    </button>
                    <a href="membership-details.php" class="btn btn--outline" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        CANCEL
                    </a>
                </div>

            </div>
        </form>

    </div>
</div>

<script>
const COLORS = {
    gcash: { border: '#0070BA', bg: '#e8f4ff' },
    maya:  { border: '#00a651', bg: '#e6f9f0' },
    card:  { border: '#555',    bg: '#f5f5f5' },
};

function selectPayment(radio) {
    // Reset all labels
    document.querySelectorAll('.payment-option').forEach(el => {
        el.style.borderColor = 'var(--gray)';
        el.style.background  = '#fff';
    });

    const method = radio.value;
    const label  = radio.closest('.payment-option');
    label.style.borderColor = COLORS[method].border;
    label.style.background  = COLORS[method].bg;

    // Toggle sections
    const walletSection = document.getElementById('walletSection');
    const cardSection   = document.getElementById('cardSection');
    const walletLabel   = document.getElementById('walletLabel');
    const walletHints   = document.querySelectorAll('.wallet-name-hint');

    if (method === 'gcash' || method === 'maya') {
        walletSection.style.display = 'block';
        cardSection.style.display   = 'none';
        const name = method === 'gcash' ? 'GCash' : 'Maya';
        walletLabel.textContent = name;
        walletHints.forEach(h => h.textContent = name);
        document.getElementById('wallet_number').style.borderColor = COLORS[method].border;
        // clear card required
        setCardRequired(false);
    } else if (method === 'card') {
        walletSection.style.display = 'none';
        cardSection.style.display   = 'block';
        setCardRequired(true);
    }
}

function setCardRequired(req) {
    ['card_name','card_number','card_expiry','card_cvv'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.required = req;
    });
}

function formatCard(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 16);
    input.value = v.replace(/(.{4})/g, '$1 ').trim();
}

function formatExpiry(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 4);
    if (v.length >= 3) v = v.slice(0,2) + '/' + v.slice(2);
    input.value = v;
}

function validateForm(e) {
    const method = document.querySelector('input[name="payment_method"]:checked');
    if (!method) {
        e.preventDefault();
        alert('Please select a payment method.');
        return false;
    }

    if (method.value === 'gcash' || method.value === 'maya') {
        const phone = document.getElementById('wallet_number').value;
        if (!phone || !/^09\d{9}$/.test(phone)) {
            e.preventDefault();
            alert('Please enter a valid 11-digit mobile number starting with 09.');
            document.getElementById('wallet_number').focus();
            return false;
        }
    }

    if (method.value === 'card') {
        const num  = document.getElementById('card_number').value.replace(/\s/g, '');
        const name = document.getElementById('card_name').value.trim();
        const exp  = document.getElementById('card_expiry').value.trim();
        const cvv  = document.getElementById('card_cvv').value.trim();

        if (!name) {
            e.preventDefault();
            alert('Please enter the cardholder name.');
            return false;
        }
        if (num.length < 13 || num.length > 19) {
            e.preventDefault();
            alert('Please enter a valid card number.');
            return false;
        }
        if (!/^\d{2}\/\d{2}$/.test(exp)) {
            e.preventDefault();
            alert('Please enter a valid expiry date (MM/YY).');
            return false;
        }
        if (cvv.length < 3) {
            e.preventDefault();
            alert('Please enter the CVV.');
            return false;
        }
    }

    return true;
}
</script>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>