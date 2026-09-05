<?php
/**
 * YoursSpeciallyJewellery - Admin Orders Management
 */

$adminPageTitle = 'Orders Management';
require_once __DIR__ . '/includes/header.php';

$search = trim($_GET['search'] ?? '');
$orderStatus = trim($_GET['order_status'] ?? '');
$paymentStatus = trim($_GET['payment_status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($orderStatus)) {
    $where[] = "order_status = ?";
    $params[] = $orderStatus;
}

if (!empty($paymentStatus)) {
    $where[] = "payment_status = ?";
    $params[] = $paymentStatus;
}

try {
    $db = getDBConnection();
    $whereSql = implode(" AND ", $where);
    $stmt = $db->prepare("SELECT * FROM orders WHERE {$whereSql} ORDER BY id DESC");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Admin orders error: " . $e->getMessage());
    $orders = [];
}
?>

<!-- Orders Filter Toolbar -->
<div class="admin-card" style="margin-bottom: 1.5rem;">
    <div class="admin-card-body" style="padding: 1.25rem;">
        <form method="GET" action="<?= BASE_URL ?>admin/orders.php" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <input type="text" name="search" placeholder="Order #, client name, phone..." value="<?= e($search) ?>" class="form-control" style="width: 260px; padding: 0.5rem 0.85rem;">

            <select name="order_status" class="form-control" style="width: 170px; padding: 0.5rem 0.85rem;">
                <option value="">All Order Statuses</option>
                <option value="Pending" <?= ($orderStatus === 'Pending') ? 'selected' : '' ?>>Pending</option>
                <option value="Confirmed" <?= ($orderStatus === 'Confirmed') ? 'selected' : '' ?>>Confirmed</option>
                <option value="Processing" <?= ($orderStatus === 'Processing') ? 'selected' : '' ?>>Processing</option>
                <option value="Shipped" <?= ($orderStatus === 'Shipped') ? 'selected' : '' ?>>Shipped</option>
                <option value="Delivered" <?= ($orderStatus === 'Delivered') ? 'selected' : '' ?>>Delivered</option>
                <option value="Cancelled" <?= ($orderStatus === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
            </select>

            <select name="payment_status" class="form-control" style="width: 170px; padding: 0.5rem 0.85rem;">
                <option value="">All Payment Statuses</option>
                <option value="Pending" <?= ($paymentStatus === 'Pending') ? 'selected' : '' ?>>Pending</option>
                <option value="Paid" <?= ($paymentStatus === 'Paid') ? 'selected' : '' ?>>Paid</option>
                <option value="Failed" <?= ($paymentStatus === 'Failed') ? 'selected' : '' ?>>Failed</option>
                <option value="Refunded" <?= ($paymentStatus === 'Refunded') ? 'selected' : '' ?>>Refunded</option>
            </select>

            <button type="submit" class="btn-admin btn-admin-primary">Filter Orders</button>
            <?php if (!empty($search) || !empty($orderStatus) || !empty($paymentStatus)): ?>
                <a href="<?= BASE_URL ?>admin/orders.php" class="btn-admin btn-admin-outline">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Orders Table Card -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Orders List (<?= count($orders) ?>)</h3>
    </div>
    <div class="admin-table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Client Coordinates</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Payment</th>
                    <th>Order Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 3rem; color: var(--admin-muted);">No orders match the selected filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--admin-primary);"><?= e($o['order_number']) ?></strong>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?= e($o['customer_name']) ?></div>
                                <div style="font-size: 0.78rem; color: var(--admin-muted);"><?= e($o['customer_phone']) ?> &bull; <?= e($o['customer_email']) ?></div>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--admin-muted);">
                                <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                            </td>
                            <td style="font-weight: 700; color: var(--admin-primary);">
                                <?= formatPrice($o['total_amount']) ?>
                            </td>
                            <td style="text-transform: uppercase; font-size: 0.8rem; font-weight: 600;">
                                <?= e($o['payment_method']) ?>
                            </td>
                            <td>
                                <span class="badge-status <?= strtolower(e($o['payment_status'])) ?>">
                                    <?= e($o['payment_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-status <?= strtolower(e($o['order_status'])) ?>">
                                    <?= e($o['order_status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>admin/order-details.php?id=<?= $o['id'] ?>" class="btn-admin btn-admin-primary btn-admin-sm">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
