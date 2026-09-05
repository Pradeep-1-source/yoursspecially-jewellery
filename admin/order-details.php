<?php
/**
 * YoursSpeciallyJewellery - Admin Order Details & Status Processor
 */

$adminPageTitle = 'Order Fulfillment';
require_once __DIR__ . '/includes/header.php';

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header("Location: " . BASE_URL . "admin/orders.php");
    exit;
}

$db = getDBConnection();

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $newOrderStatus = $_POST['order_status'] ?? '';
        $newPaymentStatus = $_POST['payment_status'] ?? '';

        $upd = $db->prepare("UPDATE orders SET order_status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$newOrderStatus, $newPaymentStatus, $orderId]);

        setFlash('success', 'Order and payment status successfully updated.');
        header("Location: " . BASE_URL . "admin/order-details.php?id=" . $orderId);
        exit;
    }
}

// Fetch order
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    header("Location: " . BASE_URL . "admin/orders.php");
    exit;
}

// Fetch order items with product SKU/image
$itemStmt = $db->prepare("SELECT oi.*, p.sku, p.main_image, p.slug 
                         FROM order_items oi 
                         LEFT JOIN products p ON oi.product_id = p.id 
                         WHERE oi.order_id = ?");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

// WhatsApp customer coordinate helper
$customerWaNumber = preg_replace('/\D/', '', $order['customer_phone']);
$waMessage = urlencode("Hello " . $order['customer_name'] . ", this is YoursSpeciallyJewellery regarding your order " . $order['order_number'] . ". Status: " . $order['order_status'] . ".");
$waLink = "https://wa.me/91" . $customerWaNumber . "?text=" . $waMessage;
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>admin/orders.php" class="btn-admin btn-admin-outline">&larr; Back to All Orders</a>
    <div style="display: flex; gap: 8px;">
        <a href="<?= $waLink ?>" target="_blank" class="btn-admin btn-admin-primary" style="background: #25D366;">
            Message Client on WhatsApp
        </a>
        <button type="button" onclick="window.print()" class="btn-admin btn-admin-outline">
            Print Packing Slip
        </button>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
    <!-- Left: Order Items & Breakdown -->
    <div>
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Order Items (<?= count($items) ?>)</h3>
                <span style="font-weight: 700; color: var(--admin-primary); font-size: 1.1rem;"><?= e($order['order_number']) ?></span>
            </div>
            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Piece</th>
                            <th>SKU</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?= BASE_URL . e($it['main_image'] ?: 'assets/images/prod-neck-1.jpg') ?>" alt="<?= e($it['product_name']) ?>" style="width: 44px; height: 44px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                                    <strong style="color: var(--admin-primary); font-size: 0.92rem;"><?= e($it['product_name']) ?></strong>
                                </td>
                                <td><code><?= e($it['sku'] ?? 'N/A') ?></code></td>
                                <td><?= formatPrice($it['price']) ?></td>
                                <td><strong><?= $it['quantity'] ?></strong></td>
                                <td style="font-weight: 700; color: var(--admin-primary);"><?= formatPrice($it['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="admin-card-body" style="background: #FAF7F8; border-top: 1px solid var(--admin-border);">
                <div style="max-width: 320px; margin-left: auto; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.92rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Subtotal:</span>
                        <span><?= formatPrice($order['subtotal']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Shipping:</span>
                        <span><?= $order['shipping_charge'] > 0 ? formatPrice($order['shipping_charge']) : 'FREE' ?></span>
                    </div>
                    <?php if ($order['discount'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; color: #16A34A;">
                            <span>Discount:</span>
                            <span>-<?= formatPrice($order['discount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.15rem; color: var(--admin-primary); border-top: 2px solid var(--admin-border); padding-top: 0.5rem;">
                        <span>Total Paid:</span>
                        <span><?= formatPrice($order['total_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Destination Address -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Delivery Address</h3>
            </div>
            <div class="admin-card-body">
                <div style="font-size: 0.95rem; line-height: 1.7; white-space: pre-line; color: var(--admin-text);">
                    <?= e($order['shipping_address']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Update Status & Client Details -->
    <div>
        <!-- Status Control Form -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Fulfillment Controls</h3>
            </div>
            <div class="admin-card-body">
                <form method="POST" action="<?= BASE_URL ?>admin/order-details.php?id=<?= $orderId ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="update_status" value="1">

                    <div class="form-group">
                        <label class="form-label" for="orderStatus">Order Status</label>
                        <select id="orderStatus" name="order_status" class="form-control" style="font-weight: 600;">
                            <?php foreach (['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $os): ?>
                                <option value="<?= $os ?>" <?= ($order['order_status'] === $os) ? 'selected' : '' ?>><?= $os ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="paymentStatus">Payment Status</label>
                        <select id="paymentStatus" name="payment_status" class="form-control" style="font-weight: 600;">
                            <?php foreach (['Pending', 'Paid', 'Failed', 'Refunded'] as $ps): ?>
                                <option value="<?= $ps ?>" <?= ($order['payment_status'] === $ps) ? 'selected' : '' ?>><?= $ps ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-admin btn-admin-primary btn-block" style="padding: 0.75rem;">
                        Update Order Status
                    </button>
                </form>
            </div>
        </div>

        <!-- Client Coordinate Info -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Client Profile</h3>
            </div>
            <div class="admin-card-body" style="font-size: 0.9rem; line-height: 1.8;">
                <div><strong>Name:</strong> <?= e($order['customer_name']) ?></div>
                <div><strong>Phone:</strong> <?= e($order['customer_phone']) ?></div>
                <div><strong>Email:</strong> <?= e($order['customer_email']) ?></div>
                <div><strong>Order Date:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
                <div><strong>Payment Method:</strong> <?= strtoupper(e($order['payment_method'])) ?></div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 992px) {
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
