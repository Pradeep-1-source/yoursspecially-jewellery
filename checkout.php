<?php
/**
 * YoursSpeciallyJewellery - Luxury Checkout Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/payment/gateway.php';

$pageTitle = 'Secure Checkout | YoursSpeciallyJewellery';
$metaDescription = 'Complete your luxury jewellery acquisition with encrypted, insured pan-India checkout.';

$cartItems = [];
$subtotal = 0.0;
$shippingCharge = (float)getSetting('shipping_flat_rate', '99.00');
$freeShippingThreshold = (float)getSetting('free_shipping_threshold', '999.00');
$discountAmount = 0.0;
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;
$customer = currentCustomer();

try {
    $db = getDBConnection();
    $cartId = getActiveCartId();

    $stmt = $db->prepare("SELECT ci.id AS item_id, ci.quantity, ci.price, p.id AS product_id, p.name, p.slug, p.sku, p.main_image, p.stock 
                          FROM cart_items ci 
                          JOIN products p ON ci.product_id = p.id 
                          WHERE ci.cart_id = ? 
                          ORDER BY ci.id DESC");
    $stmt->execute([$cartId]);
    $cartItems = $stmt->fetchAll();

    if (empty($cartItems)) {
        header("Location: " . BASE_URL . "cart.php");
        exit;
    }

    foreach ($cartItems as $item) {
        $subtotal += ($item['quantity'] * $item['price']);
    }

    if ($subtotal >= $freeShippingThreshold || $subtotal == 0) {
        $shippingCharge = 0.0;
    }

    if ($appliedCoupon && $subtotal > 0) {
        if ($appliedCoupon['discount_type'] === 'percentage') {
            $calc = ($subtotal * $appliedCoupon['discount_value']) / 100;
            if (!empty($appliedCoupon['maximum_discount']) && $calc > $appliedCoupon['maximum_discount']) {
                $calc = $appliedCoupon['maximum_discount'];
            }
            $discountAmount = $calc;
        } else {
            $discountAmount = min($subtotal, $appliedCoupon['discount_value']);
        }
    }

    $grandTotal = max(0, ($subtotal - $discountAmount + $shippingCharge));

    // Fetch saved address if customer is logged in
    $savedAddress = null;
    if ($customer) {
        $addrStmt = $db->prepare("SELECT * FROM addresses WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
        $addrStmt->execute([$customer['id']]);
        $savedAddress = $addrStmt->fetch();
    }

} catch (Exception $e) {
    error_log("Checkout error: " . $e->getMessage());
    header("Location: " . BASE_URL . "cart.php");
    exit;
}

// Handle Order Placement POST
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security token expired. Please reload the page and try again.';
    }

    $name = trim($_POST['customer_name'] ?? '');
    $email = trim(strtolower($_POST['customer_email'] ?? ''));
    $phone = trim($_POST['customer_phone'] ?? '');
    $address1 = trim($_POST['address_line_1'] ?? '');
    $address2 = trim($_POST['address_line_2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $country = trim($_POST['country'] ?? 'India');
    $paymentMethod = $_POST['payment_method'] ?? 'online'; // 'online' or 'cod'

    if (empty($name) || empty($email) || empty($phone) || empty($address1) || empty($city) || empty($state) || empty($pincode)) {
        $errors[] = 'Please fill in all required shipping and contact details.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    // Verify stock availability one more time before order confirmation
    foreach ($cartItems as $ci) {
        $stkCheck = $db->prepare("SELECT stock, name FROM products WHERE id = ?");
        $stkCheck->execute([$ci['product_id']]);
        $currStk = $stkCheck->fetch();
        if (!$currStk || $currStk['stock'] < $ci['quantity']) {
            $errors[] = 'Sorry, ' . ($currStk['name'] ?? 'an item') . ' is currently out of stock or exceeds available quantity. Please adjust your shopping bag.';
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $orderNumber = 'YSJ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $customerId = currentCustomerId();

            $fullAddress = sprintf(
                "%s\n%s%s, %s - %s\n%s\nContact: %s",
                $address1,
                $address2 ? $address2 . "\n" : "",
                $city,
                $state,
                $pincode,
                $country,
                $phone
            );

            $orderStmt = $db->prepare("INSERT INTO orders 
                (customer_id, order_number, subtotal, shipping_charge, discount, total_amount, payment_method, payment_status, order_status, customer_name, customer_phone, customer_email, shipping_address, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $initialPaymentStatus = ($paymentMethod === 'cod') ? 'Pending' : 'Pending';
            $initialOrderStatus = ($paymentMethod === 'cod') ? 'Confirmed' : 'Pending';

            $orderStmt->execute([
                $customerId,
                $orderNumber,
                $subtotal,
                $shippingCharge,
                $discountAmount,
                $grandTotal,
                $paymentMethod,
                $initialPaymentStatus,
                $initialOrderStatus,
                $name,
                $phone,
                $email,
                $fullAddress
            ]);

            $newOrderId = (int)$db->lastInsertId();

            // Insert each item into order_items
            $itemInsStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");

            foreach ($cartItems as $ci) {
                $lineSub = $ci['quantity'] * $ci['price'];
                $itemInsStmt->execute([
                    $newOrderId,
                    $ci['product_id'],
                    $ci['name'],
                    $ci['quantity'],
                    $ci['price'],
                    $lineSub
                ]);

                // If COD, deduct inventory stock immediately
                if ($paymentMethod === 'cod') {
                    $updStock = $db->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                    $updStock->execute([$ci['quantity'], $ci['product_id']]);
                }
            }

            // Save address to customer profile if logged in
            if ($customerId) {
                $addrCheck = $db->prepare("SELECT id FROM addresses WHERE customer_id = ? LIMIT 1");
                $addrCheck->execute([$customerId]);
                if (!$addrCheck->fetch()) {
                    $insAddr = $db->prepare("INSERT INTO addresses (customer_id, name, phone, address_line_1, address_line_2, city, state, pincode, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insAddr->execute([$customerId, $name, $phone, $address1, $address2, $city, $state, $pincode, $country]);
                }
            }

            $db->commit();

            // Handle Payment Strategy
            if ($paymentMethod === 'cod') {
                // Clear cart
                $delCart = $db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
                $delCart->execute([$cartId]);
                unset($_SESSION['applied_coupon']);

                header("Location: " . BASE_URL . "order-success.php?order=" . urlencode($orderNumber));
                exit;
            } else {
                // Online Payment Gateway Checkout: Render Payment Verification Screen
                $_SESSION['pending_order_id'] = $newOrderId;
                $_SESSION['pending_order_number'] = $orderNumber;
                $_SESSION['pending_order_total'] = $grandTotal;

                // Proceed to payment gateway initiation
                $gateway = getPaymentGateway();
                $gatewayOrder = $gateway->createGatewayOrder([
                    'total_amount' => $grandTotal,
                    'order_number' => $orderNumber,
                    'customer_name' => $name,
                    'customer_email' => $email
                ]);

                // Render payment gateway bridge
                require_once __DIR__ . '/includes/header.php';
                ?>
                <div class="container section-padding" style="max-width: 600px; text-align: center;">
                    <div style="background: #fff; padding: 3rem 2rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); box-shadow: var(--shadow-card);">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--bg-blush); color: var(--primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        </div>
                        <h2 style="margin-bottom: 0.5rem;">Payment Gateway Gateway</h2>
                        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Order Number: <strong><?= e($orderNumber) ?></strong><br>Amount to Pay: <strong><?= formatPrice($grandTotal) ?></strong></p>

                        <div style="background: var(--bg-cream); border: 1px dashed var(--border-subtle); border-radius: 6px; padding: 1.25rem; font-size: 0.88rem; margin-bottom: 2rem; text-align: left;">
                            <strong style="color: var(--primary);">Secure Integration Note:</strong><br>
                            This payment layer is pre-configured with the standard Razorpay / Cashfree HMAC verification workflow. Click below to simulate or execute payment verification.
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>payment/verify-payment.php">
                            <input type="hidden" name="order_id" value="<?= $newOrderId ?>">
                            <input type="hidden" name="razorpay_order_id" value="<?= e($gatewayOrder['gateway_order_id'] ?? 'mock_order_123') ?>">
                            <input type="hidden" name="razorpay_payment_id" value="pay_<?= bin2hex(random_bytes(8)) ?>">
                            <input type="hidden" name="razorpay_signature" value="sig_<?= bin2hex(random_bytes(16)) ?>">

                            <button type="submit" class="btn btn-primary btn-block" style="margin-bottom: 1rem;">
                                Complete Payment (<?= formatPrice($grandTotal) ?>) &rarr;
                            </button>
                        </form>

                        <a href="<?= BASE_URL ?>cart.php" style="font-size: 0.85rem; color: var(--text-muted);">Cancel and return to bag</a>
                    </div>
                </div>
                <?php
                require_once __DIR__ . '/includes/footer.php';
                exit;
            }

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Order creation error: " . $e->getMessage());
            $errors[] = 'A server error occurred while processing your order. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 5rem;">
    <h1 style="font-size: 2.2rem; color: var(--primary); margin-bottom: 0.5rem; text-align: center;">Bespoke Checkout</h1>
    <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2.5rem;">Enter your delivery destination to receive your handcrafted jewellery.</p>

    <?php if (!empty($errors)): ?>
        <div style="background: #FDF2F2; border: 1px solid #F8B4B4; color: #9B1C1C; padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 2rem; max-width: 800px; margin-left: auto; margin-right: auto;">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>checkout.php">
        <?= csrfField() ?>

        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 3rem; align-items: start;">
            <!-- Left Side: Shipping Information Form -->
            <div style="background: #fff; padding: 2.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); box-shadow: var(--shadow-soft);">
                <h3 style="font-size: 1.35rem; color: var(--primary); margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.8rem;">
                    1. Delivery Destination
                </h3>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="customerName">Full Name *</label>
                        <input type="text" id="customerName" name="customer_name" class="form-control" value="<?= e($_POST['customer_name'] ?? ($savedAddress['name'] ?? ($customer['name'] ?? ''))) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="customerPhone">Phone Number *</label>
                        <input type="tel" id="customerPhone" name="customer_phone" class="form-control" placeholder="10-digit mobile number" value="<?= e($_POST['customer_phone'] ?? ($savedAddress['phone'] ?? ($customer['phone'] ?? ''))) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="customerEmail">Email Address (for order receipts & tracking) *</label>
                    <input type="email" id="customerEmail" name="customer_email" class="form-control" value="<?= e($_POST['customer_email'] ?? ($customer['email'] ?? '')) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address1">Street Address / House No. *</label>
                    <input type="text" id="address1" name="address_line_1" class="form-control" placeholder="House/Flat No, Apartment, Street" value="<?= e($_POST['address_line_1'] ?? ($savedAddress['address_line_1'] ?? '')) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address2">Landmark / Suite / Floor (Optional)</label>
                    <input type="text" id="address2" name="address_line_2" class="form-control" placeholder="Nearby landmark, suite" value="<?= e($_POST['address_line_2'] ?? ($savedAddress['address_line_2'] ?? '')) ?>">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="city">City *</label>
                        <input type="text" id="city" name="city" class="form-control" value="<?= e($_POST['city'] ?? ($savedAddress['city'] ?? '')) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="state">State *</label>
                        <input type="text" id="state" name="state" class="form-control" value="<?= e($_POST['state'] ?? ($savedAddress['state'] ?? '')) ?>" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="pincode">Pincode *</label>
                        <input type="text" id="pincode" name="pincode" class="form-control" value="<?= e($_POST['pincode'] ?? ($savedAddress['pincode'] ?? '')) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="country">Country *</label>
                        <input type="text" id="country" name="country" class="form-control" value="India" readonly>
                    </div>
                </div>

                <h3 style="font-size: 1.35rem; color: var(--primary); margin-top: 2rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.8rem;">
                    2. Payment Method
                </h3>

                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <label style="display: flex; align-items: center; gap: 1rem; padding: 1.25rem; border: 1px solid var(--border-subtle); border-radius: 6px; cursor: pointer; background: var(--bg-cream);">
                        <input type="radio" name="payment_method" value="online" checked style="accent-color: var(--primary); width: 18px; height: 18px;">
                        <div>
                            <strong style="color: var(--primary); display: block;">Instant Online Payment (UPI, Cards, NetBanking, Wallets)</strong>
                            <span style="font-size: 0.85rem; color: var(--text-secondary);">Supports Google Pay, PhonePe, Paytm, Visa, Mastercard, RuPay & NetBanking.</span>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 1rem; padding: 1.25rem; border: 1px solid var(--border-subtle); border-radius: 6px; cursor: pointer; background: #fff;">
                        <input type="radio" name="payment_method" value="cod" style="accent-color: var(--primary); width: 18px; height: 18px;">
                        <div>
                            <strong style="color: var(--primary); display: block;">Cash On Delivery (COD) / Direct Boutique Transfer</strong>
                            <span style="font-size: 0.85rem; color: var(--text-secondary);">Pay upon receiving your insured velvet package at your doorstep.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Right Side: Order Review Summary -->
            <div>
                <div class="summary-card" style="position: sticky; top: 110px;">
                    <h3>Order Breakdown</h3>

                    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem; max-height: 280px; overflow-y: auto; padding-right: 6px;">
                        <?php foreach ($cartItems as $item): ?>
                            <div style="display: flex; gap: 0.85rem; align-items: center;">
                                <img src="<?= BASE_URL . e($item['main_image'] ?: 'assets/images/prod-neck-1.jpg') ?>" alt="<?= e($item['name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-subtle);">
                                <div style="flex-grow: 1;">
                                    <div style="font-size: 0.88rem; font-weight: 600; color: var(--primary);"><?= e($item['name']) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);"><?= $item['quantity'] ?> &times; <?= formatPrice($item['price']) ?></div>
                                </div>
                                <div style="font-weight: 600; font-size: 0.9rem;"><?= formatPrice($item['quantity'] * $item['price']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-line">
                        <span>Subtotal</span>
                        <span><?= formatPrice($subtotal) ?></span>
                    </div>

                    <div class="summary-line">
                        <span>Insured Delivery</span>
                        <span><?= $shippingCharge > 0 ? formatPrice($shippingCharge) : '<strong style="color:#16A34A;">FREE</strong>' ?></span>
                    </div>

                    <?php if ($discountAmount > 0): ?>
                        <div class="summary-line" style="color: #16A34A;">
                            <span>Promo Discount (<?= e($appliedCoupon['code']) ?>)</span>
                            <span>-<?= formatPrice($discountAmount) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="summary-total">
                        <span>Grand Total</span>
                        <span><?= formatPrice($grandTotal) ?></span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding: 1rem;">
                        Place Order &rarr;
                    </button>

                    <div style="margin-top: 1.25rem; text-align: center; font-size: 0.75rem; color: var(--text-muted); line-height: 1.5;">
                        By placing this order, you agree to YoursSpeciallyJewellery's <a href="<?= BASE_URL ?>terms.php" target="_blank" style="text-decoration: underline;">Terms</a> and <a href="<?= BASE_URL ?>privacy-policy.php" target="_blank" style="text-decoration: underline;">Privacy Policy</a>.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
@media (max-width: 900px) {
    div[style*="grid-template-columns: 1.5fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
