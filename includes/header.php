<?php
/**
 * YoursSpeciallyJewellery - Header Component
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? getSetting('site_name', 'YoursSpeciallyJewellery') . ' | Handcrafted Luxury & Timeless Elegance';
$metaDescription = $metaDescription ?? 'Discover luxury handcrafted jewellery, bespoke necklaces, radiant rings, earrings and bridal trousseau at YoursSpeciallyJewellery.';
$cartCount = getCartItemCount();
$announcement = getSetting('announcement_bar', '✨ Handcrafted Luxury Jewellery • Free Shipping on Orders Above ₹999 Across India ✨');
$customer = currentCustomer();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    
    <!-- Open Graph / Social Meta -->
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= BASE_URL ?>assets/images/logo.jpeg">
    <meta property="og:url" content="<?= BASE_URL . ltrim($_SERVER['REQUEST_URI'] ?? '', '/') ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>assets/images/logo.jpeg">

    <!-- CSS Master Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- Announcement Bar -->
<?php if (!empty($announcement)): ?>
<div class="announcement-bar">
    <div class="container">
        <span><?= e($announcement) ?></span>
    </div>
</div>
<?php endif; ?>

<!-- Main Sticky Header -->
<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <!-- Mobile Menu Toggle Button -->
            <button class="mobile-menu-btn action-icon-btn" aria-label="Open Navigation Menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <!-- Brand Logo -->
            <a href="<?= BASE_URL ?>index.php" class="logo-wrapper">
                <img src="<?= BASE_URL ?>assets/images/logo.jpeg" alt="YoursSpeciallyJewellery Logo" class="brand-logo-img">
                <div>
                    <span class="brand-logo-text">YoursSpecially</span>
                    <span class="brand-logo-sub">JEWELLERY</span>
                </div>
            </a>

            <!-- Desktop Navigation Menu -->
            <nav class="main-nav" aria-label="Main Navigation">
                <a href="<?= BASE_URL ?>index.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">Home</a>
                <a href="<?= BASE_URL ?>products.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'products.php' && empty($_GET['category'])) ? 'active' : '' ?>">All Jewellery</a>
                <a href="<?= BASE_URL ?>products.php?category=necklaces" class="nav-link <?= (($_GET['category'] ?? '') == 'necklaces') ? 'active' : '' ?>">Necklaces</a>
                <a href="<?= BASE_URL ?>products.php?category=earrings" class="nav-link <?= (($_GET['category'] ?? '') == 'earrings') ? 'active' : '' ?>">Earrings</a>
                <a href="<?= BASE_URL ?>products.php?category=rings" class="nav-link <?= (($_GET['category'] ?? '') == 'rings') ? 'active' : '' ?>">Rings</a>
                <a href="<?= BASE_URL ?>products.php?category=jewellery-sets" class="nav-link <?= (($_GET['category'] ?? '') == 'jewellery-sets') ? 'active' : '' ?>">Bridal Sets</a>
                <a href="<?= BASE_URL ?>about.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : '' ?>">Our Story</a>
                <a href="<?= BASE_URL ?>contact.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : '' ?>">Boutique</a>
            </nav>

            <!-- Header Utilities / Actions -->
            <div class="header-actions">
                <!-- Search Toggle -->
                <button class="action-icon-btn" id="searchTriggerBtn" title="Search Jewellery" onclick="toggleSearchModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>

                <!-- Wishlist Action -->
                <a href="<?= BASE_URL ?>wishlist.php" class="action-icon-btn wishlist-header-btn" title="View Wishlist" style="position: relative;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span class="badge-count wishlist-count-badge" style="display: none;">0</span>
                </a>

                <!-- Shopping Bag -->
                <a href="<?= BASE_URL ?>cart.php" class="action-icon-btn" title="View Shopping Bag" style="position: relative;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    <span class="badge-count cart-count-badge" style="display: <?= $cartCount > 0 ? 'flex' : 'none' ?>;"><?= $cartCount ?></span>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Search Overlay Modal -->
<div id="searchModal" style="display:none; position:fixed; inset:0; background:rgba(44, 16, 30, 0.8); backdrop-filter:blur(8px); z-index:1000; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; width:100%; max-width:640px; border-radius:8px; padding:2rem; position:relative; box-shadow:0 20px 50px rgba(0,0,0,0.3);">
        <button onclick="toggleSearchModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; cursor:pointer; color:var(--primary);">&times;</button>
        <h3 style="margin-bottom:1rem; color:var(--primary);">Search Our Luxury Collection</h3>
        <form action="<?= BASE_URL ?>products.php" method="GET" style="display:flex; gap:8px;">
            <input type="text" name="search" placeholder="Search for necklaces, rings, earrings, bangles..." style="flex-grow:1; padding:0.85rem 1.25rem; border:1px solid var(--border-subtle); border-radius:4px; font-size:1rem;" required autofocus>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
</div>

<script>
function toggleSearchModal() {
    const modal = document.getElementById('searchModal');
    if (modal.style.display === 'none' || modal.style.display === '') {
        modal.style.display = 'flex';
        const input = modal.querySelector('input');
        if (input) setTimeout(() => input.focus(), 50);
    } else {
        modal.style.display = 'none';
    }
}
</script>

<!-- Mobile Navigation Drawer -->
<div class="mobile-nav-drawer" id="mobileNavDrawer" aria-label="Mobile Navigation Menu">
    <div class="drawer-header">
        <div class="drawer-brand">
            <img src="<?= BASE_URL ?>assets/images/logo.jpeg" alt="YoursSpecially Logo" class="drawer-logo-img">
            <div>
                <span class="drawer-brand-name">YoursSpecially</span>
                <span class="drawer-brand-sub">JEWELLERY</span>
            </div>
        </div>
        <button class="drawer-close-btn" aria-label="Close Navigation Menu">&times;</button>
    </div>

    <nav class="drawer-nav">
        <ul class="drawer-menu">
            <li class="drawer-item">
                <a href="<?= BASE_URL ?>index.php" class="drawer-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">Home</a>
            </li>
            <li class="drawer-item drawer-item-has-children">
                <div class="drawer-accordion-header">
                    <a href="<?= BASE_URL ?>products.php" class="drawer-link <?= (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : '' ?>">Collections</a>
                    <button type="button" class="drawer-accordion-toggle" aria-label="Toggle Collections Submenu" aria-expanded="false">
                        <svg class="accordion-arrow-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                </div>
                <ul class="drawer-submenu" id="collectionsSubmenu">
                    <li><a href="<?= BASE_URL ?>products.php" class="drawer-sublink">All Jewellery</a></li>
                    <li><a href="<?= BASE_URL ?>products.php?category=necklaces" class="drawer-sublink">Necklaces</a></li>
                    <li><a href="<?= BASE_URL ?>products.php?category=earrings" class="drawer-sublink">Earrings</a></li>
                    <li><a href="<?= BASE_URL ?>products.php?category=rings" class="drawer-sublink">Rings</a></li>
                    <li><a href="<?= BASE_URL ?>products.php?category=bracelets" class="drawer-sublink">Bracelets</a></li>
                    <li><a href="<?= BASE_URL ?>products.php?category=bangles" class="drawer-sublink">Bangles</a></li>
                    <li><a href="<?= BASE_URL ?>products.php?category=jewellery-sets" class="drawer-sublink">Jewellery Sets</a></li>
                </ul>
            </li>
            <li class="drawer-item">
                <a href="<?= BASE_URL ?>about.php" class="drawer-link <?= (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : '' ?>">About</a>
            </li>
            <li class="drawer-item">
                <a href="<?= BASE_URL ?>contact.php" class="drawer-link <?= (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : '' ?>">Contact</a>
            </li>
            <li class="drawer-item">
                <a href="<?= BASE_URL ?>wishlist.php" class="drawer-link drawer-link-flex">
                    <span>Wishlist</span>
                    <span class="badge-count wishlist-count-badge" style="position:static; display:none;">0</span>
                </a>
            </li>
            <li class="drawer-item">
                <a href="<?= BASE_URL ?>cart.php" class="drawer-link drawer-link-flex">
                    <span>Cart</span>
                    <span class="badge-count cart-count-badge" style="position:static; display: <?= $cartCount > 0 ? 'inline-flex' : 'none' ?>;"><?= $cartCount ?></span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="drawer-footer">
        <a href="<?= BASE_URL ?>orders.php" class="btn btn-outline btn-block btn-sm">Track My Order</a>
    </div>
</div>
<div class="drawer-backdrop" id="drawerBackdrop"></div>

<!-- Flash Message Notifications -->
<?php $flash = getFlash(); if ($flash): ?>
<div class="container" style="margin-top: 1.5rem;">
    <div class="flash-alert <?= e($flash['type']) ?>">
        <span><?= e($flash['message']) ?></span>
        <button style="background:none; border:none; cursor:pointer; font-weight:bold;" onclick="this.parentElement.remove()">&times;</button>
    </div>
</div>
<?php endif; ?>
