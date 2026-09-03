document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.site-header');
    const navLinks = document.querySelectorAll('.main-nav .nav-link');
    const cartButton = document.querySelector('.cart-btn');
    const cartDrawer = document.querySelector('#cart-drawer');
    const cartCloseButtons = document.querySelectorAll('[data-cart-close]');
    if (!header) return;

    function setCartOpen(isOpen) {
        document.body.classList.toggle('cart-open', isOpen);
        cartDrawer?.setAttribute('aria-hidden', String(!isOpen));
        cartButton?.setAttribute('aria-expanded', String(isOpen));
    }

    cartButton?.addEventListener('click', () => setCartOpen(true));
    cartCloseButtons.forEach(button => {
        button.addEventListener('click', () => setCartOpen(false));
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') setCartOpen(false);
    });

    let lastScrollY = window.scrollY;
    let hideTimeout = null;
    let isNavClicking = false;
    let navClickTimeout = null;

    const HIDE_DELAY = 2500;
    const THRESHOLD = 5;

    // Helper to update active class on links
    function setActiveLink(targetId) {
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === `#${targetId}`) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    // Auto-hide header timer
    function startHideTimer() {
        clearTimeout(hideTimeout);
        if (window.scrollY > 100) {
            hideTimeout = setTimeout(() => {
                header.classList.add('header-hidden');
            }, HIDE_DELAY);
        }
    }

    // Scroll listener for header auto-hide
    function handleScroll() {
        if (isNavClicking) return;

        const currentScrollY = Math.max(0, window.scrollY);
        const diff = currentScrollY - lastScrollY;

        if (currentScrollY <= 80) {
            header.classList.remove('header-hidden');
            clearTimeout(hideTimeout);
            lastScrollY = currentScrollY;
            return;
        }

        if (diff > THRESHOLD) {
            header.classList.add('header-hidden');
            clearTimeout(hideTimeout);
            lastScrollY = currentScrollY;
        } else if (diff < -THRESHOLD) {
            header.classList.remove('header-hidden');
            startHideTimer();
            lastScrollY = currentScrollY;
        }
    }

    // Handle Link Clicks
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (href && href.startsWith('#')) {
                const targetId = href.replace('#', '');
                setActiveLink(targetId);
            }

            // Keep header visible while page scrolls to target section
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

    // Auto-update active link based on section in view
    const sections = document.querySelectorAll('section[id]');
    if (sections.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !isNavClicking) {
                    setActiveLink(entry.target.id);
                }
            });
        }, {
            rootMargin: '-20% 0px -60% 0px',
            threshold: 0
        });

        sections.forEach(section => observer.observe(section));
    }

    // Event listeners
    window.addEventListener('scroll', handleScroll, { passive: true });
    header.addEventListener('mouseenter', () => clearTimeout(hideTimeout));
    header.addEventListener('mouseleave', startHideTimer);
});