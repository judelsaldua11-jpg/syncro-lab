<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? 'User') : '';
$userRole = $isLoggedIn ? ($_SESSION['role'] ?? 'customer') : '';
?>

<!-- FOOTER SECTION -->
<footer class="site-footer" aria-label="Site Footer">
    <div class="footer-container">
        <div class="footer-content">
            <!-- Column 1: Brand -->
            <div class="footer-brand-col">
                <div class="footer-logo">
                    <img src="/syncro lab/assets/images/syncro-lab-dark.svg" alt="SYNCRO LAB Logo" class="footer-logo-img">
                </div>
                <p class="footer-address">
                    Dumaguete City,<br>
                    6200 Negros Oriental
                </p>
                <a href="mailto:contact@syncrolab.com" class="footer-email">contact@syncrolab.com</a>
            </div>

            <!-- Column 2: SHOP (Category Filters) -->
            <div class="footer-links-col">
                <h4 class="footer-column-title">SHOP</h4>
                <ul class="footer-links">
                    <li><a href="/syncro lab/pages/shop.php?category=bikes">Bikes</a></li>
                    <li><a href="/syncro lab/pages/shop.php?category=components">Components</a></li>
                    <li><a href="/syncro lab/pages/shop.php?category=wheels">Wheels</a></li>
                    <li><a href="/syncro lab/pages/shop.php?category=apparel">Apparel</a></li>
                    <li><a href="/syncro lab/pages/shop.php?category=frames">Bike frames</a></li>
                </ul>
            </div>

            <!-- Column 3: SERVICES -->
            <div class="footer-links-col">
                <h4 class="footer-column-title">SERVICES</h4>
                <ul class="footer-links">
                    <li><a href="/syncro lab/pages/booking.php?service=repair_maintenance">Tune-ups</a></li>
                    <li><a href="/syncro lab/pages/service-detail.php?service=custom_build">Custom Builds</a></li>
                    <li><a href="/syncro lab/pages/booking.php?service=repair_maintenance">Diagnostics</a></li>
                    <li><a href="/syncro lab/pages/warranty.php">Warranty</a></li>
                    <li><a href="/syncro lab/pages/about.php">FAQs</a></li>
                </ul>
            </div>

            <!-- Column 4: SOCIALS -->
            <div class="footer-links-col">
                <h4 class="footer-column-title">SOCIALS</h4>
                <ul class="footer-social-links">
                    <li>
                        <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                            <span>Instagram</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg>
                            <span>YouTube</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.strava.com/" target="_blank" rel="noopener noreferrer">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7.925 15.599h4.173"/></svg>
                            <span>Strava</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.komoot.com/" target="_blank" rel="noopener noreferrer">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v10M8 10l4-3 4 3M8 14l4 3 4-3"></path></svg>
                            <span>Komoot</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div><!-- /footer-content -->

        <!-- Copyright (Centered) -->
        <div style="text-align: center; width: 100%; padding-top: 16px; color: var(--gray); font-size: 16px;">
            <p style="margin: 0;">© 2026 SYNCRO LAB. All rights reserved</p>
        </div>
    </div><!-- /footer-container -->
</footer>

<!-- Auth Drawer (Side Drawer) -->
<div class="auth-drawer-backdrop" data-auth-close></div>
<aside id="auth-drawer" class="auth-drawer" aria-label="Account" aria-hidden="true">
    <div class="auth-drawer-header">
        <div>
            <?php if ($isLoggedIn): ?>
                <p class="auth-drawer-eyebrow">WELCOME BACK</p>
                <h2><?= htmlspecialchars($userName) ?></h2>
                <p style="color: var(--gray-dark); font-size: 14px; margin-top: 4px;">
                    <?php
                    $roleLabels = [
                        'customer' => 'Customer',
                        'branch_manager' => 'Branch Manager',
                        'hq_admin' => 'HQ Admin'
                    ];
                    echo $roleLabels[$userRole] ?? 'User';
                    ?>
                </p>
            <?php else: ?>
                <p class="auth-drawer-eyebrow">SYNCRO LAB ACCOUNT</p>
                <h2>ACCOUNT REQUIRED</h2>
            <?php endif; ?>
        </div>
        <button type="button" class="auth-drawer-close" data-auth-close aria-label="Close account drawer">&times;</button>
    </div>
    <div class="auth-drawer-body">
        <?php if ($isLoggedIn): ?>
            <!-- LOGGED IN: User Actions -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="/syncro lab/pages/profile.php" class="btn btn--green" style="width: 100%; justify-content: center;">
                    👤 MY PROFILE
                </a>
                <a href="/syncro lab/pages/orders.php" class="btn btn--outline" style="width: 100%; justify-content: center;">
                    📦 MY ORDERS
                </a>
                <a href="/syncro lab/pages/chat.php" class="btn btn--outline" style="width: 100%; justify-content: center;">
                    💬 CHAT
                </a>
                <?php if ($userRole === 'hq_admin' || $userRole === 'branch_manager'): ?>
                    <a href="/syncro lab/pages/admin/dashboard.php" class="btn btn--outline" style="width: 100%; justify-content: center;">
                        📊 DASHBOARD
                    </a>
                <?php endif; ?>
            </div>

            <!-- Divider -->
            <div style="margin: 20px 0; border-top: 1px solid var(--gray);"></div>

            <!-- Logout -->
            <a href="/syncro lab/pages/auth/logout.php" class="btn btn--dark" style="width: 100%; justify-content: center; border-color: var(--dark);">
                🚪 LOGOUT
            </a>

        <?php else: ?>
            <!-- LOGGED OUT: Login / Signup -->
            <p style="color: var(--gray-dark); margin-bottom: 24px; line-height: 1.5;">
                Log in or create an account to book a service, register, or continue with your cart.
            </p>
            <a href="/syncro lab/pages/auth/login.php" class="btn btn--green" style="width: 100%; justify-content: center;">LOG IN</a>
            <a href="/syncro lab/pages/auth/register.php" class="btn btn--outline" style="width: 100%; justify-content: center; margin-top: 8px;">SIGN UP</a>
        <?php endif; ?>
    </div>
</aside>

<script src="/syncro lab/assets/js/main.js"></script>
</body>
</html>