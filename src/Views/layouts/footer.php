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
            <div class="footer-links-col">
                <h4 class="footer-column-title">SHOP</h4>
                <ul class="footer-links">
                    <li><a href="#bikes">Bikes</a></li>
                    <li><a href="#components">Components</a></li>
                    <li><a href="#wheels">Wheels</a></li>
                    <li><a href="#apparel">Apparel</a></li>
                    <li><a href="#frames">Bike frames</a></li>
                </ul>
            </div>
            <div class="footer-links-col">
                <h4 class="footer-column-title">SERVICES</h4>
                <ul class="footer-links">
                    <li><a href="#tune-ups">Tune-ups</a></li>
                    <li><a href="#custom-builds">Custom Builds</a></li>
                    <li><a href="#diagnostics">Diagnostics</a></li>
                    <li><a href="#warranty">Warranty</a></li>
                    <li><a href="#faqs">FAQs</a></li>
                </ul>
            </div>
            <div class="footer-links-col">
                <h4 class="footer-column-title">SOCIALS</h4>
                <ul class="footer-social-links">
                    <li>
                        <a href="#instagram">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                            <span>Instagram</span>
                        </a>
                    </li>
                    <li>
                        <a href="#youtube">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg>
                            <span>YouTube</span>
                        </a>
                    </li>
                    <li>
                        <a href="#strava">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7.925 15.599h4.173"/></svg>
                            <span>Strava</span>
                        </a>
                    </li>
                    <li>
                        <a href="#komoot">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v10M8 10l4-3 4 3M8 14l4 3 4-3"></path></svg>
                            <span>Komoot</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer-copyright">
            <p>© 2026 SYNCRO LAB. All rights reserved</p>
        </div>
    </div>
</footer>

<!-- Auth Modal -->
<div id="auth-modal" class="auth-modal" aria-hidden="true">
    <div class="auth-modal-backdrop" data-auth-close></div>
    <section class="auth-modal-panel" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title">
        <button type="button" class="auth-modal-close" data-auth-close aria-label="Close account prompt">&times;</button>
        
        <?php if ($isLoggedIn): ?>
            <!-- LOGGED IN: Show User Info -->
            <p class="auth-modal-eyebrow">WELCOME BACK</p>
            <h2 id="auth-modal-title"><?= htmlspecialchars($userName) ?></h2>
            <p style="color: var(--gray-dark); margin-top: 8px; font-size: 14px;">
                <?php
                $roleLabels = [
                    'customer' => 'Customer',
                    'branch_manager' => 'Branch Manager',
                    'hq_admin' => 'HQ Admin'
                ];
                echo $roleLabels[$userRole] ?? 'User';
                ?>
            </p>
            <div class="auth-modal-actions" style="flex-direction: column; gap: 8px; margin-top: 24px;">
                <a href="pages/profile.php" class="btn btn--green" style="width: 100%; justify-content: center;">MY PROFILE</a>
                <?php if ($userRole === 'hq_admin' || $userRole === 'branch_manager'): ?>
                    <a href="pages/admin/dashboard.php" class="btn btn--outline" style="width: 100%; justify-content: center;">DASHBOARD</a>
                <?php endif; ?>
                <a href="pages/auth/logout.php" class="btn btn--dark" style="width: 100%; justify-content: center; border-color: var(--dark);">LOGOUT</a>
            </div>
        <?php else: ?>
            <!-- LOGGED OUT: Show Login/Signup -->
            <p class="auth-modal-eyebrow">SYNCRO LAB ACCOUNT</p>
            <h2 id="auth-modal-title">ACCOUNT REQUIRED</h2>
            <p class="auth-modal-copy">Log in or create an account to book a service, register, or continue with your cart.</p>
            <div class="auth-modal-actions">
                <a class="btn btn--green" href="pages/auth/login.php" style="flex: 1; justify-content: center;">LOG IN</a>
                <a class="btn btn--outline" href="pages/auth/register.php" style="flex: 1; justify-content: center;">SIGN UP</a>
            </div>
        <?php endif; ?>
    </section>
</div>

<script src="/syncro lab/assets/js/main.js"></script>
</body>
</html>