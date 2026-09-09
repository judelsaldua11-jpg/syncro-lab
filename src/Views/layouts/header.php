<?php
// src/Views/layouts/header.php
// Session, database, and functions are already loaded by index.php

// Get cart data using the already-loaded functions
$pdo = getConnection();
$userId = $_SESSION['user_id'] ?? null;
$cartId = getOrCreateCart($pdo, $userId);
$cartItems = getCartItems($pdo, $cartId);
$cartTotal = getCartTotal($pdo, $cartId);
$cartCount = getCartCount($pdo, $cartId);

// Determine if we are on the homepage
$isHomepage = (basename($_SERVER['SCRIPT_NAME']) == 'index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SYNCRO LAB</title>
    <link rel="stylesheet" href="/syncro lab/assets/css/style.css">
</head>
<body>

<!-- SECTION 1: HEADER NAVIGATION -->
<header class="site-header">
    <!-- Brand Logo -->
    <a href="/syncro lab/index.php" class="brand-logo">
        <img src="/syncro lab/assets/images/syncro-lab-light.svg" alt="SYNCRO LAB Logo" class="logo-img">
    </a>

    <!-- Navigation Links (only on homepage) -->
    <?php if ($isHomepage): ?>
    <nav class="main-nav">
        <ul>
            <li><a href="#home" class="nav-link active">HOME</a></li>
            <li><a href="#services" class="nav-link">SERVICES</a></li>
            <li><a href="#catalog" class="nav-link">CATALOG</a></li>
            <li><a href="#about" class="nav-link">ABOUT</a></li>
            <li><a href="#locations" class="nav-link">LOCATIONS</a></li>
            <li class="nav-search-item">
                <!-- Search Icon (links to shop page) -->
                <a href="/syncro lab/pages/shop.php" class="icon-btn nav-search-btn" aria-label="Search">
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M35 35L27.75 27.75M31.6667 18.3333C31.6667 25.6971 25.6971 31.6667 18.3333 31.6667C10.9695 31.6667 5 25.6971 5 18.3333C5 10.9695 10.9695 5 18.3333 5C25.6971 5 31.6667 10.9695 31.6667 18.3333Z" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

    <!-- Header Right Actions -->
    <div class="header-actions">
        <a href="/syncro lab/pages/booking.php" class="btn btn--green btn--small">BOOK SERVICE</a>

        <!-- Cart Button -->
        <button type="button" class="icon-btn cart-btn" aria-label="Open cart" aria-controls="cart-drawer" aria-expanded="false">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_172_18)">
                    <path d="M1.66663 1.66667H8.33329L12.8 23.9833C12.9524 24.7507 13.3698 25.4399 13.9792 25.9305C14.5886 26.4211 15.3511 26.6817 16.1333 26.6667H32.3333C33.1155 26.6817 33.878 26.4211 34.4874 25.9305C35.0968 25.4399 35.5142 24.7507 35.6666 23.9833L38.3333 10H9.99996M16.6666 35C16.6666 35.9205 15.9204 36.6667 15 36.6667C14.0795 36.6667 13.3333 35.9205 13.3333 35C13.3333 34.0795 14.0795 33.3333 15 33.3333C15.9204 33.3333 16.6666 34.0795 16.6666 35ZM35 35C35 35.9205 34.2538 36.6667 33.3333 36.6667C32.4128 36.6667 31.6666 35.9205 31.6666 35C31.6666 34.0795 32.4128 33.3333 33.3333 33.3333C34.2538 33.3333 35 34.0795 35 35Z" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <defs>
                    <clipPath id="clip0_172_18">
                        <rect width="40" height="40" fill="white"/>
                    </clipPath>
                </defs>
            </svg>
            <span class="cart-count">[<?= $cartCount ?>]</span>
        </button>

        <!-- Account Icon (Triggers Auth Drawer) -->
        <button type="button" class="icon-btn account-btn" data-auth-trigger aria-label="Account">
            <svg width="45" height="45" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M40 42V38C40 35.8783 39.1571 33.8434 37.6569 32.3431C36.1566 30.8429 34.1217 30 32 30H16C13.8783 30 11.8434 30.8429 10.3431 32.3431C8.84285 33.8434 8 35.8783 8 38V42M32 14C32 18.4183 28.4183 22 24 22C19.5817 22 16 18.4183 16 14C16 9.58172 19.5817 6 24 6C28.4183 6 32 9.58172 32 14Z" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
</header>

<!-- Cart Drawer -->
<div class="cart-drawer-backdrop" data-cart-close></div>
<aside id="cart-drawer" class="cart-drawer" aria-label="Shopping cart" aria-hidden="true">
    <div class="cart-drawer-header">
        <div>
            <p class="cart-drawer-eyebrow">YOUR SELECTION</p>
            <h2>SHOPPING CART</h2>
        </div>
        <button type="button" class="cart-drawer-close" data-cart-close aria-label="Close cart">&times;</button>
    </div>
    <div class="cart-drawer-content">
        <?php if (empty($cartItems)): ?>
            <div class="cart-empty-state">
                <span class="cart-empty-icon" aria-hidden="true">+</span>
                <h3>Your cart is empty</h3>
                <p>Add precision parts, rider gear, or a service to get started.</p>
            </div>
        <?php else: ?>
            <?php foreach ($cartItems as $item): ?>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                    <span><?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?></span>
                    <span>₱ <?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                </div>
            <?php endforeach; ?>
            <div style="margin-top: 12px; padding-top: 12px; border-top: 2px solid var(--green); font-weight: 700;">
                Total: ₱ <?= number_format($cartTotal, 2) ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="cart-drawer-footer">
        <div class="cart-drawer-total"><span>SUBTOTAL</span><strong>PHP <?= number_format($cartTotal, 2) ?></strong></div>
        <a href="/syncro lab/pages/cart.php" class="cart-full-link">EXPLORE CART <span aria-hidden="true">&#8594;</span></a>
    </div>
</aside>