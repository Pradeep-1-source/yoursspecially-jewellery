<?php
/**
 * YoursSpeciallyJewellery - Guest Order Tracker
 * Allows guests to track orders using Order Number and Phone/Email
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$searchedOrder = null;
$error = '';
$orderNum = trim($_GET['order_number'] ?? '');
$contact = trim($_GET['contact'] ?? '');

if (!empty($orderNum)) {
    try {
        $db = getDBConnection();
        if (!empty($contact)) {
            $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? AND (customer_phone LIKE ? OR customer_email = ?) LIMIT 1");
            $stmt->execute([$orderNum, '%' . $contact . '%', strtolower($contact)]);
        } else {
            $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
            $stmt->execute([$orderNum]);
        }
        $searchedOrder = $stmt->fetch();

        if (!$searchedOrder) {
            $error = "No matching order found for reference '" . htmlspecialchars($orderNum) . "'. Please check your order number and contact details.";
        } else {
            $itemsStmt = $db->prepare("SELECT oi.*, p.slug, p.main_image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $itemsStmt->execute([$searchedOrder['id']]);
            $orderItems = $itemsStmt->fetchAll();
        }
    } catch (Exception $e) {
        error_log("Order track error: " . $e->getMessage());
        $error = "Unable to retrieve order details at this moment. Please try again later.";
    }
}

$pageTitle = 'Track Your Order | YoursSpeciallyJewellery';
$metaDescription = 'Track your luxury handcrafted jewellery order status and shipping details in real-time.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding" style="padding-top: 3rem; padding-bottom: 5rem;">
    <!-- Breadcrumb -->
    <nav style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
        <a href="<?= BASE_URL ?>index.php" style="color: inherit;">Home</a> &rarr; 
        <span style="color: var(--primary); font-weight: 500;">Track Order</span>
    </nav>

    <div style="text-align: center; margin-bottom: 3rem;">
        <span class="section-eyebrow" style="display: block; margin-bottom: 0.5rem;">Real-Time Concierge</span>
        <h1 style="font-size: 2.4rem; color: var(--primary); margin-bottom: 0.5rem;">Track Your Order</h1>
        <p style="color: var(--text-secondary); max-width: 520px; margin: 0 auto;">
            Enter your order reference number received via SMS / Email to view real-time delivery status.
        </p>
    </div>

    <!-- Search Form Card -->
    <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2rem; max-width: 600px; margin: 0 auto 3rem; box-shadow: var(--shadow-soft);">
        <form method="GET" action="<?= BASE_URL ?>orders.php" style="display: flex; flex-direction: column; gap: 1rem;">
            <div>
                <label style="display: block; font-size: 0.88rem; font-weight: 600; color: var(--primary); margin-bottom: 0.4rem;">Order Reference Number *</label>
                <input type="text" name="order_number" placeholder="e.g. YSJ-20260908-ABCDEF" value="<?= e($orderNum) ?>" required style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-subtle); border-radius: 4px; font-size: 0.95rem;">
            </div>

            <div>
                <label style="display: block; font-size: 0.88rem; font-weight: 600; color: var(--primary); margin-bottom: 0.4rem;">Phone Number or Email (Optional)</label>
                <input type="text" name="contact" placeholder="Registered phone or email" value="<?= e($contact) ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-subtle); border-radius: 4px; font-size: 0.95rem;">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 0.5rem;">Track Status</button>
        </form>

        <?php if (!empty($error)): ?>
            <div style="background: #FFF1F2; border: 1px solid #FECDD3; padding: 1rem; border-radius: 6px; margin-top: 1.5rem; color: #9F1239; font-size: 0.9rem;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Details View -->
    <?php if ($searchedOrder): ?>
        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); max-width: 800px; margin: 0 auto; padding: 2.5rem; box-shadow: var(--shadow-soft);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Order Reference</span>
                    <h2 style="color: var(--primary); margin-top: 0.2rem;"><?= e($searchedOrder['order_number']) ?></h2>
                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.3rem;">
                        Placed on: <?= date('d M Y, h:i A', strtotime($searchedOrder['created_at'])) ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.3rem;">Status</div>
                    <span class="badge-status <?= strtolower(e($searchedOrder['order_status'])) ?>" style="padding: 6px 14px; font-size: 0.85rem; font-weight: 600; border-radius: 20px;">
                        <?= e($searchedOrder['order_status']) ?>
                    </span>
                </div>
            </div>

            <!-- Recipient & Address -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <h4 style="color: var(--primary); margin-bottom: 0.5rem; font-size: 0.95rem;">Delivery Destination</h4>
                    <p style="font-size: 0.9rem; color: var(--text-secondary); white-space: pre-line; line-height: 1.6;">
                        <?= e($searchedOrder['shipping_address']) ?>
                    </p>
                </div>
                <div>
                    <h4 style="color: var(--primary); margin-bottom: 0.5rem; font-size: 0.95rem;">Payment Information</h4>
                    <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6;">
                        Method: <strong><?= strtoupper(e($searchedOrder['payment_method'])) ?></strong><br>
                        Payment Status: <strong><?= e($searchedOrder['payment_status']) ?></strong><br>
                        Total Paid: <strong style="color: var(--primary);"><?= formatPrice($searchedOrder['total_amount']) ?></strong>
                    </p>
                </div>
            </div>

            <!-- Ordered Items Table -->
            <?php if (!empty($orderItems)): ?>
                <h4 style="color: var(--primary); margin-bottom: 1rem; font-size: 1rem;">Items in Order</h4>
                <div style="border: 1px solid var(--border-subtle); border-radius: 6px; overflow: hidden; margin-bottom: 1.5rem;">
                    <?php foreach ($orderItems as $item): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border-bottom: 1px solid var(--border-subtle);">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <?php if (!empty($item['main_image'])): ?>
                                    <img src="<?= BASE_URL . e($item['main_image']) ?>" alt="<?= e($item['product_name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <?php endif; ?>
                                <div>
                                    <strong style="color: var(--primary); font-family: var(--font-serif); font-size: 1.05rem; display: block;"><?= e($item['product_name']) ?></strong>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">Qty: <?= (int)$item['quantity'] ?> &times; <?= formatPrice($item['price']) ?></span>
                                </div>
                            </div>
                            <div style="font-weight: 700; color: var(--primary);">
                                <?= formatPrice($item['quantity'] * $item['price']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="https://wa.me/91<?= preg_replace('/\D/', '', getSetting('whatsapp_number', '9940474469')) ?>?text=<?= urlencode('Hi YoursSpecially, I need an update regarding my order #' . $searchedOrder['order_number']) ?>" target="_blank" class="btn btn-outline btn-sm">
                    Inquire via WhatsApp Concierge
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
        gap: 1.25rem !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
