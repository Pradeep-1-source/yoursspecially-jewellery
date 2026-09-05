<?php
/**
 * YoursSpeciallyJewellery - Admin Executive Dashboard
 */

$adminPageTitle = 'Dashboard Overview';
require_once __DIR__ . '/includes/header.php';

try {
    $db = getDBConnection();

    // 1. Total Orders
    $orderCountStmt = $db->query("SELECT COUNT(*) FROM orders");
    $totalOrders = (int)$orderCountStmt->fetchColumn();

    // 2. Total Sales
    $salesStmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'Paid' OR order_status IN ('Confirmed', 'Processing', 'Shipped', 'Delivered')");
    $totalSales = (float)$salesStmt->fetchColumn();

    // 3. Total Customers
    $custStmt = $db->query("SELECT COUNT(*) FROM customers");
    $totalCustomers = (int)$custStmt->fetchColumn();

    // 4. Total Products
    $prodStmt = $db->query("SELECT COUNT(*) FROM products WHERE status = 1");
    $totalProducts = (int)$prodStmt->fetchColumn();

    // 5. Pending Orders
    $pendingStmt = $db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'");
    $pendingOrders = (int)$pendingStmt->fetchColumn();

    // 6. Low Stock Products (<= 5)
    $lowStockStmt = $db->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND status = 1");
    $lowStockCount = (int)$lowStockStmt->fetchColumn();

    // Fetch 6 Recent Orders
    $recentOrdersStmt = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 6");
    $recentOrders = $recentOrdersStmt->fetchAll();

    // Fetch Low Stock Pieces
    $lowStockItemsStmt = $db->query("SELECT id, name, sku, stock, price, main_image FROM products WHERE stock <= 5 AND status = 1 ORDER BY stock ASC LIMIT 5");
    $lowStockItems = $lowStockItemsStmt->fetchAll();

} catch (Exception $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $totalOrders = $totalSales = $totalCustomers = $totalProducts = $pendingOrders = $lowStockCount = 0;
    $recentOrders = [];
    $lowStockItems = [];
}
?>

<!-- Quick Action Shortcuts -->
<div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
    <a href="<?= BASE_URL ?>admin/add-product.php" class="btn-admin btn-admin-primary">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        <span>Add New Product</span>
    </a>
    <a href="<?= BASE_URL ?>admin/banners.php" class="btn-admin btn-admin-outline">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
        <span>Manage Hero Offer Banners</span>
    </a>
    <a href="<?= BASE_URL ?>admin/orders.php" class="btn-admin btn-admin-outline">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
        <span>Process Orders</span>
    </a>
</div>

<!-- 6 Core KPI Stat Cards -->
<div class="admin-stats-grid">
    <div class="stat-card">
        <div class="stat-icon sales">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
        <div class="stat-content">
            <h3><?= formatPrice($totalSales) ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orders">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
        </div>
        <div class="stat-content">
            <h3><?= $totalOrders ?></h3>
            <p>Total Orders</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon customers">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="stat-content">
            <h3><?= $totalCustomers ?></h3>
            <p>Registered Clients</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon products">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="12 6 12 12 14 14"></polygon></svg>
        </div>
        <div class="stat-content">
            <h3><?= $totalProducts ?></h3>
            <p>Active Creations</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon pending">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div class="stat-content">
            <h3><?= $pendingOrders ?></h3>
            <p>Pending Orders</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stock">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        </div>
        <div class="stat-content">
            <h3><?= $lowStockCount ?></h3>
            <p>Low Stock Items</p>
        </div>
    </div>
</div>

<!-- Dashboard Dual Column (Recent Orders & Low Stock Alert) -->
<div style="display: grid; grid-template-columns: 1.8fr 1fr; gap: 2rem; align-items: start;">
    <!-- Recent Orders Table Card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Recent Orders</h3>
            <a href="<?= BASE_URL ?>admin/orders.php" style="font-size: 0.85rem; color: var(--admin-secondary); font-weight: 600;">View All &rarr;</a>
        </div>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Order Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="6" style="text-align:center; color: var(--admin-muted); padding: 2rem;">No orders registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--admin-primary);"><?= e($ro['order_number']) ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--admin-muted);"><?= date('d M, h:i A', strtotime($ro['created_at'])) ?></div>
                                </td>
                                <td><?= e($ro['customer_name']) ?></td>
                                <td style="font-weight: 600;"><?= formatPrice($ro['total_amount']) ?></td>
                                <td>
                                    <span class="badge-status <?= strtolower(e($ro['payment_status'])) ?>"><?= e($ro['payment_status']) ?></span>
                                </td>
                                <td>
                                    <span class="badge-status <?= strtolower(e($ro['order_status'])) ?>"><?= e($ro['order_status']) ?></span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>admin/order-details.php?id=<?= $ro['id'] ?>" class="btn-admin btn-admin-outline btn-admin-sm">Manage</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Alert Card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Low Stock Alerts</h3>
            <span class="badge-status cancelled"><?= $lowStockCount ?> Low</span>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <?php if (empty($lowStockItems)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--admin-muted); font-size: 0.88rem;">
                    All jewellery designs have sufficient inventory!
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column;">
                    <?php foreach ($lowStockItems as $lsi): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid var(--admin-border);">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <img src="<?= BASE_URL . e($lsi['main_image'] ?: 'assets/images/prod-neck-1.jpg') ?>" alt="<?= e($lsi['name']) ?>" style="width: 42px; height: 42px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                                <div>
                                    <div style="font-size: 0.88rem; font-weight: 600; color: var(--admin-primary);"><?= e($lsi['name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--admin-muted);">SKU: <?= e($lsi['sku']) ?></div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-weight: 700; color: #DC2626; font-size: 0.92rem;"><?= $lsi['stock'] ?> Left</span><br>
                                <a href="<?= BASE_URL ?>admin/edit-product.php?id=<?= $lsi['id'] ?>" style="font-size: 0.78rem; color: var(--admin-secondary); text-decoration: underline;">Restock</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media (max-width: 992px) {
    div[style*="grid-template-columns: 1.8fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
