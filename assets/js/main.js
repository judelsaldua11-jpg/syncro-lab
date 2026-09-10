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

/* ============================================================
   SYNCRO LAB CUSTOM SYSTEM MODAL SYSTEM (Alert, Confirm, Prompt)
   ============================================================ */
window.SyncroModal = (function() {
    let activeModal = null;

    function createBackdrop() {
        const existing = document.getElementById('syncro-system-modal');
        if (existing) return existing;

        const backdrop = document.createElement('div');
        backdrop.id = 'syncro-system-modal';
        backdrop.className = 'syncro-modal-backdrop';
        backdrop.innerHTML = `
            <div class="syncro-modal-dialog" role="dialog" aria-modal="true">
                <div class="syncro-modal-header">
                    <div>
                        <div class="syncro-modal-eyebrow" id="syncro-modal-eyebrow">SYNCRO LAB</div>
                        <h3 class="syncro-modal-title" id="syncro-modal-title">NOTIFICATION</h3>
                    </div>
                    <button type="button" class="syncro-modal-close" data-syncro-close aria-label="Close">&times;</button>
                </div>
                <div class="syncro-modal-body">
                    <div id="syncro-modal-icon"></div>
                    <div id="syncro-modal-message"></div>
                    <div id="syncro-modal-input-container"></div>
                </div>
                <div class="syncro-modal-footer" id="syncro-modal-footer"></div>
            </div>
        `;
        document.body.appendChild(backdrop);
        return backdrop;
    }

    function close() {
        const backdrop = document.getElementById('syncro-system-modal');
        if (backdrop) {
            backdrop.classList.remove('is-active');
        }
        if (activeModal && activeModal.reject) {
            activeModal.reject();
        }
        activeModal = null;
    }

    function show({
        title = 'NOTIFICATION',
        eyebrow = 'SYNCRO LAB',
        message = '',
        type = 'info', // 'danger', 'success', 'info'
        showInput = false,
        inputPlaceholder = '',
        inputValue = '',
        confirmText = 'OK',
        cancelText = 'Cancel',
        isConfirm = false
    }) {
        return new Promise((resolve) => {
            const backdrop = createBackdrop();
            const titleEl = backdrop.querySelector('#syncro-modal-title');
            const eyebrowEl = backdrop.querySelector('#syncro-modal-eyebrow');
            const iconEl = backdrop.querySelector('#syncro-modal-icon');
            const messageEl = backdrop.querySelector('#syncro-modal-message');
            const inputContainer = backdrop.querySelector('#syncro-modal-input-container');
            const footerEl = backdrop.querySelector('#syncro-modal-footer');
            const closeBtn = backdrop.querySelector('[data-syncro-close]');

            titleEl.textContent = title;
            eyebrowEl.textContent = eyebrow;
            messageEl.innerHTML = message;

            // Icon
            let iconSymbol = 'ℹ️';
            if (type === 'danger') iconSymbol = '⚠️';
            else if (type === 'success') iconSymbol = '✓';
            iconEl.className = 'syncro-modal-icon-wrap ' + type;
            iconEl.innerHTML = `<span>${iconSymbol}</span>`;

            // Input (if prompt)
            inputContainer.innerHTML = '';
            let inputField = null;
            if (showInput) {
                inputField = document.createElement('input');
                inputField.type = 'text';
                inputField.className = 'syncro-modal-input';
                inputField.placeholder = inputPlaceholder;
                inputField.value = inputValue;
                inputContainer.appendChild(inputField);
            }

            // Buttons
            footerEl.innerHTML = '';
            if (isConfirm || showInput) {
                const cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.className = 'syncro-modal-btn btn-cancel';
                cancelBtn.textContent = cancelText;
                cancelBtn.onclick = () => {
                    backdrop.classList.remove('is-active');
                    resolve(showInput ? null : false);
                };
                footerEl.appendChild(cancelBtn);
            }

            const confirmBtn = document.createElement('button');
            confirmBtn.type = 'button';
            confirmBtn.className = 'syncro-modal-btn ' + (type === 'danger' ? 'btn-confirm-danger' : 'btn-confirm-primary');
            confirmBtn.textContent = confirmText;
            confirmBtn.onclick = () => {
                backdrop.classList.remove('is-active');
                if (showInput) {
                    resolve(inputField ? inputField.value : '');
                } else if (isConfirm) {
                    resolve(true);
                } else {
                    resolve(true);
                }
            };
            footerEl.appendChild(confirmBtn);

            closeBtn.onclick = () => {
                backdrop.classList.remove('is-active');
                resolve(showInput ? null : false);
            };

            backdrop.onclick = (e) => {
                if (e.target === backdrop) {
                    backdrop.classList.remove('is-active');
                    resolve(showInput ? null : false);
                }
            };

            // Keyboard Enter and Escape
            const keyHandler = (e) => {
                if (e.key === 'Escape') {
                    document.removeEventListener('keydown', keyHandler);
                    backdrop.classList.remove('is-active');
                    resolve(showInput ? null : false);
                } else if (e.key === 'Enter' && showInput && document.activeElement === inputField) {
                    document.removeEventListener('keydown', keyHandler);
                    backdrop.classList.remove('is-active');
                    resolve(inputField.value);
                }
            };
            document.addEventListener('keydown', keyHandler);

            backdrop.classList.add('is-active');

            if (inputField) {
                setTimeout(() => inputField.focus(), 100);
            } else {
                confirmBtn.focus();
            }
        });
    }

    return {
        alert: function(message, title = 'NOTIFICATION', type = 'info') {
            return show({
                title,
                message,
                type,
                isConfirm: false,
                confirmText: 'CONTINUE'
            });
        },
        confirm: function(message, title = 'CONFIRM ACTION', type = 'danger', confirmText = 'CONFIRM', cancelText = 'KEEP ORDER') {
            return show({
                title,
                message,
                type,
                isConfirm: true,
                confirmText,
                cancelText
            });
        },
        prompt: function(message, placeholder = '', title = 'REASON FOR CANCELLATION', type = 'info') {
            return show({
                title,
                message,
                type,
                showInput: true,
                inputPlaceholder: placeholder,
                confirmText: 'PROCEED',
                cancelText: 'CANCEL'
            });
        }
    };
})();