<?php
/**
 * YoursSpeciallyJewellery - Customer Complete Order History
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

requireCustomerLogin();
$customer = currentCustomer();

try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC");
    $stmt->execute([$customer['id']]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Orders query error: " . $e->getMessage());
    $orders = [];
}

$pageTitle = 'My Order History | YoursSpeciallyJewellery';
$metaDescription = 'Track all your fine jewellery orders, check real-time shipment statuses, and view past receipts.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding" style="padding-top: 3rem; padding-bottom: 5rem;">
    <!-- Breadcrumb & Header -->
    <nav style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
        <a href="<?= BASE_URL ?>index.php" style="color: inherit;">Home</a> &rarr; 
        <a href="<?= BASE_URL ?>account.php" style="color: inherit;">My Account</a> &rarr; 
        <span style="color: var(--primary); font-weight: 500;">Order History</span>
    </nav>

    <h1 style="font-size: 2.2rem; color: var(--primary); margin-bottom: 0.5rem;">Your Order History</h1>
    <p style="color: var(--text-secondary); margin-bottom: 2.5rem;">All your precious acquisitions and their delivery milestones.</p>

    <?php if (empty($orders)): ?>
        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 4rem 2rem; text-align: center; max-width: 600px; margin: 0 auto;">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="var(--primary)" stroke-width="1.5" style="margin-bottom: 1rem;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <h3 style="margin-bottom: 0.5rem; color: var(--primary);">No Orders Yet</h3>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">You haven't placed any jewellery orders yet. Start your journey with our signature collections.</p>
            <a href="<?= BASE_URL ?>products.php" class="btn btn-primary">Browse Collections</a>
        </div>
    <?php else: ?>
        <div style="background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); overflow: hidden; box-shadow: var(--shadow-soft);">
            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order Reference</th>
                            <th>Date</th>
                            <th>Recipient</th>
                            <th>Total Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--primary);"><?= e($o['order_number']) ?></strong>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text-muted);">
                                    <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                                </td>
                                <td style="font-size: 0.88rem;">
                                    <?= e($o['customer_name']) ?>
                                </td>
                                <td style="font-weight: 700; color: var(--primary);">
                                    <?= formatPrice($o['total_amount']) ?>
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
                                    <a href="<?= BASE_URL ?>order-success.php?order=<?= urlencode($o['order_number']) ?>" class="btn btn-outline btn-sm">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
