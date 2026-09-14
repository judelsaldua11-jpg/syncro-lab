<?php
// pages/checkout.php - Checkout with Manual Branch Selection

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to checkout.');
    exit;
}

$pdo = getConnection();
$userId = $_SESSION['user_id'];
$cartId = getOrCreateCart($pdo, $userId);

// Get cart items
$cartItems = getCartItems($pdo, $cartId);
$cartTotal = getCartTotal($pdo, $cartId);
$cartCount = getCartCount($pdo, $cartId);

if (empty($cartItems)) {
    header('Location: cart.php?error=Your cart is empty.');
    exit;
}

// Get all branches (active)
$branches = getBranches();

// Get saved user addresses
$userAddresses = getUserAddresses($userId);
$defaultAddress = getDefaultUserAddress($userId);
$initialAddressText = $defaultAddress ? $defaultAddress['address_line'] : ($_SESSION['user_address'] ?? '');


// For each product, get stock per branch
$productStock = [];
foreach ($cartItems as $item) {
    $stmt = $pdo->prepare("
        SELECT b.id AS branch_id, b.name AS branch_name, 
               COUNT(i.id) AS stock_count
        FROM inventory i
        JOIN branches b ON i.branch_id = b.id
        WHERE i.product_id = ? AND i.status = 'in_stock' AND b.is_active = 1
        GROUP BY b.id
        HAVING stock_count > 0
        ORDER BY stock_count DESC
    ");
    $stmt->execute([$item['product_id']]);
    $productStock[$item['product_id']] = $stmt->fetchAll();
}

// If any product has zero stock across all branches, show error
$zeroStock = false;
foreach ($cartItems as $item) {
    if (empty($productStock[$item['product_id']])) {
        $zeroStock = true;
        $_SESSION['checkout_error'] = "Product '{$item['product_name']}' is out of stock. Please remove it from your cart.";
    }
}
if ($zeroStock) {
    header('Location: cart.php');
    exit;
}

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 900px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            Checkout
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Select branches to fulfill each product. You can split quantities across multiple branches.
        </p>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="checkout-handler.php">
            <div style="display: grid; gap: 32px;">
                
                <?php foreach ($cartItems as $index => $item): ?>
                    <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 24px; box-shadow: var(--shadow);">
                        <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 8px;">
                            <?= htmlspecialchars($item['product_name']) ?>
                        </h3>
                        <p style="color: var(--gray-dark); font-size: 14px;">
                            Quantity needed: <?= $item['quantity'] ?>
                        </p>

                        <div style="margin-top: 16px;">
                            <p style="font-weight: 700; font-size: 14px; margin-bottom: 8px;">Select branches and quantities:</p>
                            <?php foreach ($productStock[$item['product_id']] as $branchStock): ?>
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                    <input type="number" 
                                           name="branch_quantity[<?= $item['product_id'] ?>][<?= $branchStock['branch_id'] ?>]" 
                                           min="0" max="<?= $branchStock['stock_count'] ?>" 
                                           value="0" 
                                           style="width: 70px; padding: 6px; border: 2px solid var(--gray); border-radius: var(--radius); text-align: center;">
                                    <span style="font-weight: 600;"><?= htmlspecialchars($branchStock['branch_name']) ?></span>
                                    <span style="color: var(--gray-dark); font-size: 14px;">(<?= $branchStock['stock_count'] ?> available)</span>
                                </div>
                            <?php endforeach; ?>
                            <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                                The total quantity must equal <?= $item['quantity'] ?>.
                            </p>
                        </div>
                        <input type="hidden" name="product_needed[<?= $item['product_id'] ?>]" value="<?= $item['quantity'] ?>">
                    </div>
                <?php endforeach; ?>

                <!-- Shipping Address & Payment -->
                <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 24px; box-shadow: var(--shadow);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                        <h2 style="font-family: var(--font-heading); font-size: 20px; margin: 0; text-transform: uppercase;">Shipping Details</h2>
                        <a href="profile.php?tab=addresses" target="_blank" style="font-size: 13px; color: var(--green); font-weight: 700;">
                            ⚙️ Manage Preseted Addresses
                        </a>
                    </div>

                    <?php if (!empty($userAddresses)): ?>
                        <div style="margin-bottom: 20px;">
                            <label style="font-weight: 700; display: block; margin-bottom: 8px; font-size: 14px;">Select from your Preseted Addresses:</label>
                            <div style="display: grid; gap: 10px;">
                                <?php foreach ($userAddresses as $idx => $uAddr): 
                                    $isSelected = ($defaultAddress && $defaultAddress['id'] == $uAddr['id']) || (!$defaultAddress && $idx === 0);
                                ?>
                                    <label style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 16px; border: 2px solid <?= $isSelected ? 'var(--green)' : 'var(--gray)' ?>; border-radius: var(--radius); cursor: pointer; background: <?= $isSelected ? '#f9fdf2' : '#fff' ?>; transition: all 0.2s ease;" class="preset-address-option">
                                        <input type="radio" name="selected_preset_address" value="<?= $uAddr['id'] ?>" <?= $isSelected ? 'checked' : '' ?>
                                               data-address="<?= htmlspecialchars($uAddr['address_line'], ENT_QUOTES) ?>"
                                               data-recipient="<?= htmlspecialchars($uAddr['recipient_name'], ENT_QUOTES) ?>"
                                               data-phone="<?= htmlspecialchars($uAddr['phone'], ENT_QUOTES) ?>"
                                               onchange="selectPresetAddress(this)"
                                               style="margin-top: 3px; accent-color: var(--green);">
                                        <div style="flex: 1;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <strong style="font-size: 15px;"><?= htmlspecialchars($uAddr['label']) ?></strong>
                                                <?php if ((int)$uAddr['is_default'] === 1): ?>
                                                    <span style="background: var(--green); color: var(--dark); font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 10px; text-transform: uppercase;">Default</span>
                                                <?php endif; ?>
                                            </div>
                                            <p style="font-size: 13px; color: var(--dark); margin: 2px 0;">
                                                <strong>Recipient:</strong> <?= htmlspecialchars($uAddr['recipient_name']) ?> (<?= htmlspecialchars($uAddr['phone']) ?>)
                                            </p>
                                            <p style="font-size: 13px; color: var(--gray-dark); margin: 2px 0;">
                                                <?= htmlspecialchars($uAddr['address_line']) ?>
                                            </p>
                                        </div>
                                    </label>
                                <?php endforeach; ?>

                                <label style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 16px; border: 2px solid var(--gray); border-radius: var(--radius); cursor: pointer; background: #fff; transition: all 0.2s ease;" class="preset-address-option">
                                    <input type="radio" name="selected_preset_address" value="custom" onchange="selectPresetAddress(this)" style="margin-top: 3px; accent-color: var(--green);">
                                    <div style="flex: 1;">
                                        <strong style="font-size: 15px;">Enter a different shipping address</strong>
                                        <p style="font-size: 13px; color: var(--gray-dark); margin: 2px 0;">Use a one-time custom delivery address</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div style="display: grid; gap: 16px;">
                        <div>
                            <label for="shipping_address" style="font-weight: 700; display: block; margin-bottom: 4px;">Delivery Address *</label>
                            <textarea id="shipping_address" name="shipping_address" rows="3" required
                                      style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; font-family: inherit;"><?= htmlspecialchars($initialAddressText) ?></textarea>
                            <small style="color: var(--gray-dark); font-size: 12px;">This exact address will be permanently preserved on your order record.</small>
                        </div>
                        <div>
                            <label for="payment_method" style="font-weight: 700; display: block; margin-bottom: 4px;">Payment Method *</label>
                            <select id="payment_method" name="payment_method" required
                                    style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;"
                                    onchange="onPaymentMethodChange(this.value)">
                                <option value="cash_on_delivery">Cash on Delivery</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="gcash">GCash</option>
                                <option value="paymaya">PayMaya</option>
                            </select>
                        </div>

                        <!-- COD Notice -->
                        <div id="codNotice" style="background: #fff8e1; border: 1px solid #f9a825; border-radius: var(--radius); padding: 14px 18px; font-size: 14px; color: #6d4c00;">
                            💵 <strong>Cash on Delivery:</strong> Pay in cash when your order arrives at your delivery address. No payment details needed now.
                        </div>

                        <!-- E-Wallet Section (GCash / PayMaya) -->
                        <div id="walletSection" style="display: none;">
                            <label id="walletLabel" for="wallet_number" style="font-weight: 700; display: block; margin-bottom: 6px; font-size: 14px;">
                                GCash Mobile Number
                            </label>
                            <input type="tel" id="wallet_number" name="wallet_number"
                                   placeholder="09XXXXXXXXX" maxlength="11"
                                   style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; box-sizing: border-box;"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            <small style="color: var(--gray-dark); font-size: 12px;">Enter the 11-digit mobile number linked to your e-wallet.</small>
                        </div>

                        <!-- Credit Card Section -->
                        <div id="cardSection" style="display: none;">
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
                                           placeholder="•••• •••• •••• ••••" maxlength="19"
                                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; letter-spacing: 2px; box-sizing: border-box;"
                                           oninput="formatCardNumber(this)">
                                </div>
                                <div>
                                    <label style="font-weight: 700; display: block; margin-bottom: 6px; font-size: 14px;">Expiry (MM/YY)</label>
                                    <input type="text" id="card_expiry" name="card_expiry"
                                           placeholder="MM/YY" maxlength="5"
                                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 15px; box-sizing: border-box;"
                                           oninput="formatCardExpiry(this)">
                                </div>
                                <div>
                                    <label style="font-weight: 700; display: block; margin-bottom: 6px; font-size: 14px;">CVV</label>
                                    <input type="password" id="card_cvv" name="card_cvv"
                                           placeholder="•••" maxlength="4"
                                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 15px; box-sizing: border-box;"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                            </div>
                            <small style="color: var(--gray-dark); font-size: 12px; margin-top: 6px; display: block;">🔒 Your card details are processed securely.</small>
                        </div>
                        <div>
                            <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="terms" required>
                                I agree to the terms and conditions
                            </label>
                        </div>
                    </div>
                </div>

                <script>
                function selectPresetAddress(radio) {
                    // Update visual highlight on option cards
                    document.querySelectorAll('.preset-address-option').forEach(card => {
                        card.style.borderColor = 'var(--gray)';
                        card.style.backgroundColor = '#fff';
                    });
                    const parentCard = radio.closest('.preset-address-option');
                    if (parentCard) {
                        parentCard.style.borderColor = 'var(--green)';
                        parentCard.style.backgroundColor = '#f9fdf2';
                    }

                    const textarea = document.getElementById('shipping_address');
                    if (radio.value === 'custom') {
                        textarea.value = '';
                        textarea.focus();
                    } else {
                        const addrText = radio.getAttribute('data-address') || '';
                        textarea.value = addrText;
                    }
                }

                // ── Payment method show/hide logic ──
                function onPaymentMethodChange(method) {
                    const codNotice     = document.getElementById('codNotice');
                    const walletSection = document.getElementById('walletSection');
                    const cardSection   = document.getElementById('cardSection');
                    const walletLabel   = document.getElementById('walletLabel');
                    const walletInput   = document.getElementById('wallet_number');

                    // Hide all panels first
                    codNotice.style.display     = 'none';
                    walletSection.style.display  = 'none';
                    cardSection.style.display    = 'none';

                    // Clear required attributes
                    setCardRequired(false);
                    if (walletInput) walletInput.required = false;

                    if (method === 'cash_on_delivery') {
                        codNotice.style.display = 'block';
                    } else if (method === 'gcash' || method === 'paymaya') {
                        walletSection.style.display = 'block';
                        walletInput.required = true;
                        walletLabel.textContent = (method === 'gcash' ? 'GCash' : 'PayMaya') + ' Mobile Number';
                    } else if (method === 'credit_card') {
                        cardSection.style.display = 'block';
                        setCardRequired(true);
                    }
                }

                function setCardRequired(req) {
                    ['card_name','card_number','card_expiry','card_cvv'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.required = req;
                    });
                }

                function formatCardNumber(input) {
                    let v = input.value.replace(/\D/g, '').slice(0, 16);
                    input.value = v.replace(/(.{4})/g, '$1 ').trim();
                }

                function formatCardExpiry(input) {
                    let v = input.value.replace(/\D/g, '').slice(0, 4);
                    if (v.length >= 3) v = v.slice(0,2) + '/' + v.slice(2);
                    input.value = v;
                }

                function validateCheckoutPayment(e) {
                    const method = document.getElementById('payment_method').value;

                    if (method === 'gcash' || method === 'paymaya') {
                        const phone = document.getElementById('wallet_number').value;
                        if (!phone || !/^09\d{9}$/.test(phone)) {
                            e.preventDefault();
                            alert('Please enter a valid 11-digit mobile number starting with 09.');
                            document.getElementById('wallet_number').focus();
                            return false;
                        }
                    } else if (method === 'credit_card') {
                        const num  = (document.getElementById('card_number').value || '').replace(/\s/g, '');
                        const name = (document.getElementById('card_name').value || '').trim();
                        const exp  = (document.getElementById('card_expiry').value || '').trim();
                        const cvv  = (document.getElementById('card_cvv').value || '').trim();

                        if (!name)                             { e.preventDefault(); alert('Please enter the cardholder name.'); return false; }
                        if (num.length < 13 || num.length > 19){ e.preventDefault(); alert('Please enter a valid card number.'); return false; }
                        if (!/^\d{2}\/\d{2}$/.test(exp))       { e.preventDefault(); alert('Please enter a valid card expiry (MM/YY).'); return false; }
                        if (cvv.length < 3)                    { e.preventDefault(); alert('Please enter a valid CVV (3–4 digits).'); return false; }
                    }
                    return true;
                }

                // Attach payment validation to form submit
                document.querySelector('form').addEventListener('submit', validateCheckoutPayment);

                // Initialize on page load — show COD notice by default
                onPaymentMethodChange('cash_on_delivery');
                </script>


                <div style="display: flex; gap: 16px; justify-content: flex-end;">
                    <a href="cart.php" class="btn btn--outline" style="height: 48px; font-size: 16px; padding: 0 32px;">Back to Cart</a>
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        PLACE ORDER
                    </button>
                </div>

            </div>
        </form>

    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>