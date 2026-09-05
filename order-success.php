<?php
/**
 * YoursSpeciallyJewellery - Order Success & Confirmation Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$orderNumber = trim($_GET['order'] ?? '');
if (empty($orderNumber)) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if (!$order) {
        header("Location: " . BASE_URL . "index.php");
        exit;
    }

    $itemsStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsStmt->execute([$order['id']]);
    $orderItems = $itemsStmt->fetchAll();

} catch (Exception $e) {
    error_log("Order success error: " . $e->getMessage());
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$pageTitle = 'Order Confirmed - ' . e($order['order_number']) . ' | YoursSpeciallyJewellery';
$metaDescription = 'Your bespoke jewellery order has been received and confirmed with YoursSpeciallyJewellery.';

$whatsappNum = getSetting('whatsapp_number', '9940474469');
$waSupportText = urlencode("Hi YoursSpecially, I have a question regarding my order " . $order['order_number'] . ".");
$waSupportUrl = "https://wa.me/91" . preg_replace('/\D/', '', $whatsappNum) . "?text=" . $waSupportText;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 3.5rem; padding-bottom: 5rem; max-width: 800px;">
    <!-- Success Header Card -->
    <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-subtle); padding: 3rem 2.5rem; box-shadow: var(--shadow-card); text-align: center; margin-bottom: 2rem;">
        <div style="width: 72px; height: 72px; border-radius: 50%; background: #DCFCE7; color: #16A34A; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
            <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <span class="section-eyebrow">Acquisition Complete</span>
        <h1 style="font-size: 2.2rem; color: var(--primary); margin-bottom: 0.8rem;">Thank You, <?= e($order['customer_name']) ?>!</h1>
        <p style="color: var(--text-secondary); max-width: 540px; margin: 0 auto 1.5rem;">
            Your bespoke jewellery order has been safely placed. We are now preparing your pieces in our signature velvet keepsake packaging.
        </p>

        <div style="display: inline-block; background: var(--bg-cream); border: 1px dashed var(--border-subtle); padding: 0.75rem 1.5rem; border-radius: 6px; font-size: 1rem; color: var(--primary);">
            Order Reference: <strong style="letter-spacing: 1px;"><?= e($order['order_number']) ?></strong>
        </div>
    </div>

    <!-- Order Details & Breakdown -->
    <div style="background: #fff; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); padding: 2rem 2.5rem; box-shadow: var(--shadow-soft); margin-bottom: 2rem;">
        <h3 style="font-size: 1.25rem; color: var(--primary); margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.6rem;">
            Order Summary
        </h3>

        <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
            <?php foreach ($orderItems as $item): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.8rem; border-bottom: 1px solid var(--bg-cream);">
                    <div>
                        <strong style="color: var(--primary); font-size: 0.95rem;"><?= e($item['product_name']) ?></strong>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Quantity: <?= $item['quantity'] ?> &times; <?= formatPrice($item['price']) ?></div>
                    </div>
                    <div style="font-weight: 600; color: var(--primary); font-size: 0.95rem;">
                        <?= formatPrice($item['subtotal']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.92rem; border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
            <div style="display: flex; justify-content: space-between;">
                <span>Subtotal:</span>
                <span><?= formatPrice($order['subtotal']) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Insured Delivery:</span>
                <span><?= $order['shipping_charge'] > 0 ? formatPrice($order['shipping_charge']) : '<strong style="color:#16A34A;">FREE</strong>' ?></span>
            </div>
            <?php if ($order['discount'] > 0): ?>
                <div style="display: flex; justify-content: space-between; color: #16A34A;">
                    <span>Discount:</span>
                    <span>-<?= formatPrice($order['discount']) ?></span>
                </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 700; color: var(--primary); border-top: 2px solid var(--border-subtle); padding-top: 0.8rem; margin-top: 0.5rem;">
                <span>Total Amount:</span>
                <span><?= formatPrice($order['total_amount']) ?></span>
            </div>
        </div>
    </div>

    <!-- Delivery & Contact Info Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2.5rem;">
        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem;">
            <h4 style="font-size: 1.05rem; color: var(--primary); margin-bottom: 0.75rem;">Shipping Address</h4>
            <div style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6; white-space: pre-line;">
                <?= e($order['shipping_address']) ?>
            </div>
        </div>

        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem;">
            <h4 style="font-size: 1.05rem; color: var(--primary); margin-bottom: 0.75rem;">Status & Payment</h4>
            <div style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.8;">
                <div><strong>Payment Method:</strong> <?= strtoupper(e($order['payment_method'])) ?></div>
                <div><strong>Payment Status:</strong> <span class="badge-status <?= strtolower(e($order['payment_status'])) ?>"><?= e($order['payment_status']) ?></span></div>
                <div><strong>Order Status:</strong> <span class="badge-status <?= strtolower(e($order['order_status'])) ?>"><?= e($order['order_status']) ?></span></div>
                <div><strong>Estimated Delivery:</strong> 3 – 5 Business Days</div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
        <a href="<?= $waSupportUrl ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="border-color: #25D366; color: #128C7E; display: flex; align-items: center; gap: 8px;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.23 8.23 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.42 0-2.82-.37-4.06-1.07l-.29-.17-3.12.82.83-3.04-.19-.3a8.19 8.19 0 0 1-1.26-4.48c0-4.54 3.7-8.24 8.24-8.24m4.52 11.59c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.49-1.4-1.74-.14-.25-.02-.39.11-.51.11-.11.25-.29.37-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.44 1.03 2.61c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.1-.22-.17-.47-.29z"/></svg>
            <span>Questions? Chat with Stylist</span>
        </a>

        <button type="button" onclick="window.print()" class="btn btn-outline">
            Print Order Receipt
        </button>

        <a href="<?= BASE_URL ?>products.php" class="btn btn-primary">
            Explore More Collections
        </a>
    </div>
</div>

<style>
@media (max-width: 600px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
@media print {
    header, footer, .whatsapp-float-btn, .btn { display: none !important; }
    body { background: #fff !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
