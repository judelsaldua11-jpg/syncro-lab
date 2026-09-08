/* Handles: Auth Modal, Cart Drawer, Auto-hiding Header & Active Navigation Spy*/

document.addEventListener('DOMContentLoaded', () => {
    /* -------------------------------------------------------------------------- AUTHENTICATION MODAL ----------------------------------------------------------------------- */
const authDrawer = document.querySelector('#auth-drawer');
const authCloseButtons = document.querySelectorAll('[data-auth-close]');

function setAuthOpen(isOpen) {
    document.body.classList.toggle('auth-open', isOpen);
    authDrawer?.setAttribute('aria-hidden', String(!isOpen));
}

// Intercept clicks on auth triggers
document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-auth-trigger]');
    const link = event.target.closest('a[href]');

    if (trigger) {
        event.preventDefault();
        setAuthOpen(true);
        return;
    }

    if (link && ['#book', '#register', '#account'].includes(link.getAttribute('href'))) {
        event.preventDefault();
        setAuthOpen(true);
    }
});

authCloseButtons.forEach((button) => {
    button.addEventListener('click', () => setAuthOpen(false));
});

// ESC key handler (already exists for cart drawer)
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setAuthOpen(false);
        setCartOpen(false);
    }
});

    /* -------------------------------------------------------------------------- SHOPPING CART DRAWER ----------------------------------------------------------------------- */
    const cartButton = document.querySelector('.cart-btn');
    const cartDrawer = document.querySelector('#cart-drawer');
    const cartCloseButtons = document.querySelectorAll('[data-cart-close]');

    function setCartOpen(isOpen) {
        document.body.classList.toggle('cart-open', isOpen);
        cartDrawer?.setAttribute('aria-hidden', String(!isOpen));
        cartButton?.setAttribute('aria-expanded', String(isOpen));
    }

    cartButton?.addEventListener('click', () => setCartOpen(true));

    cartCloseButtons.forEach((button) => {
        button.addEventListener('click', () => setCartOpen(false));
    });

    // Unified ESC key handler for overlay components
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setAuthOpen(false);
            setCartOpen(false);
        }
    });

    /* -------------------------------------------------------------------------- HEADER SCROLL BEHAVIOR & NAVIGATION SPY ----------------------------------------------------------------------- */
    const header = document.querySelector('.site-header');
    const navLinks = document.querySelectorAll('.main-nav .nav-link');

    if (!header) return;

    let lastScrollY = window.scrollY;
    let hideTimeout = null;
    let isNavClicking = false;
    let navClickTimeout = null;

    const HIDE_DELAY = 2500;
    const THRESHOLD = 5;

    // Helper: Update active class on nav links
    function setActiveLink(targetId) {
        navLinks.forEach((link) => {
            const href = link.getAttribute('href');
            if (href === `#${targetId}`) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    // Auto-hide header countdown
    function startHideTimer() {
        clearTimeout(hideTimeout);
        if (window.scrollY > 100) {
            hideTimeout = setTimeout(() => {
                header.classList.add('header-hidden');
            }, HIDE_DELAY);
        }
    }

    // Scroll handler for smart auto-hiding header
    function handleScroll() {
        if (isNavClicking) return;

        const currentScrollY = Math.max(0, window.scrollY);
        const diff = currentScrollY - lastScrollY;

        // Keep header visible at the top of the page
        if (currentScrollY <= 80) {
            header.classList.remove('header-hidden');
            clearTimeout(hideTimeout);
            lastScrollY = currentScrollY;
            return;
        }

        // Scrolling down: hide header
        if (diff > THRESHOLD) {
            header.classList.add('header-hidden');
            clearTimeout(hideTimeout);
            lastScrollY = currentScrollY;
        }
        // Scrolling up: reveal header and schedule re-hide
        else if (diff < -THRESHOLD) {
            header.classList.remove('header-hidden');
            startHideTimer();
            lastScrollY = currentScrollY;
        }
    }

    // Smooth nav link click handler
    navLinks.forEach((link) => {
        link.addEventListener('click', () => {
            const href = link.getAttribute('href');
            if (href && href.startsWith('#')) {
                const targetId = href.replace('#', '');
                setActiveLink(targetId);
            }

            // Keep header visible while page smoothly scrolls to target
            header.classList.remove('header-hidden');
            isNavClicking = true;
            clearTimeout(hideTimeout);
            clearTimeout(navClickTimeout);

            navClickTimeout = setTimeout(() => {
                isNavClicking = false;
                lastScrollY = window.scrollY;
                startHideTimer();
            }, 800);
        });
    });

    // Auto-update active nav link based on section in viewport
    const sections = document.querySelectorAll('section[id]');
    if (sections.length > 0 && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting && !isNavClicking) {
                        setActiveLink(entry.target.id);
                    }
                });
            },
            {
                rootMargin: '-20% 0px -60% 0px',
                threshold: 0,
            }
        );

        sections.forEach((section) => observer.observe(section));
    }

    // Event listeners for header
    window.addEventListener('scroll', handleScroll, { passive: true });
    header.addEventListener('mouseenter', () => clearTimeout(hideTimeout));
    header.addEventListener('mouseleave', startHideTimer);
});