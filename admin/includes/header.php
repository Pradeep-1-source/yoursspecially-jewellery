<?php
/**
 * YoursSpeciallyJewellery - Admin Header & Navigation Component
 */

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireAdmin();
$currentAdmin = currentAdmin();
$currentPage = basename($_SERVER['PHP_SELF']);
$adminPageTitle = $adminPageTitle ?? 'Boutique Management Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminPageTitle) ?> | YoursSpecially Admin</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>assets/images/logo.jpeg">

    <!-- CSS Master & Admin Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
</head>
<body class="admin-body">

<!-- Sidebar Navigation -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>assets/images/logo.jpeg" alt="YoursSpecially">
        <div>
            <div class="sidebar-brand-text">YoursSpecially</div>
            <div style="font-size: 0.65rem; color: var(--admin-secondary); letter-spacing: 1.5px; text-transform: uppercase;">Admin Portal</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="sidebar-nav-item <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            <span>Dashboard</span>
        </a>

        <a href="<?= BASE_URL ?>admin/orders.php" class="sidebar-nav-item <?= (in_array($currentPage, ['orders.php', 'order-details.php'])) ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            <span>Orders</span>
        </a>

        <a href="<?= BASE_URL ?>admin/products.php" class="sidebar-nav-item <?= (in_array($currentPage, ['products.php', 'add-product.php', 'edit-product.php'])) ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="12 6 12 12 14 14"></polygon></svg>
            <span>Products</span>
        </a>

        <a href="<?= BASE_URL ?>admin/categories.php" class="sidebar-nav-item <?= ($currentPage == 'categories.php') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            <span>Categories</span>
        </a>

        <a href="<?= BASE_URL ?>admin/banners.php" class="sidebar-nav-item <?= ($currentPage == 'banners.php') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            <span>Hero Banners</span>
        </a>

        <a href="<?= BASE_URL ?>admin/customers.php" class="sidebar-nav-item <?= ($currentPage == 'customers.php') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span>Customers</span>
        </a>

        <a href="<?= BASE_URL ?>admin/settings.php" class="sidebar-nav-item <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            <span>Site Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>index.php" target="_blank" class="sidebar-nav-item" style="padding-left: 0; color: var(--admin-accent);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            <span>View Live Store &rarr;</span>
        </a>
        <a href="<?= BASE_URL ?>admin/logout.php" class="sidebar-nav-item" style="padding-left: 0; color: #EF4444;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Sign Out</span>
        </a>
    </div>
</aside>

<!-- Main Admin Layout Wrapper -->
<div class="admin-main">
    <!-- Top Bar -->
    <header class="admin-header">
        <h2 class="admin-page-title"><?= e($adminPageTitle) ?></h2>

        <div class="admin-header-actions">
            <div class="admin-user-pill">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span><?= e($currentAdmin['name'] ?? 'Administrator') ?></span>
            </div>
        </div>
    </header>

    <div class="admin-body-content">
        <!-- Flash Alert Messages -->
        <?php $flash = getFlash(); if ($flash): ?>
            <div class="flash-alert <?= e($flash['type']) ?>" style="margin-bottom: 1.5rem;">
                <span><?= e($flash['message']) ?></span>
                <button style="background:none; border:none; cursor:pointer; font-weight:bold;" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>
