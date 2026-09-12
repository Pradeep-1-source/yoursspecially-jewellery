<?php
/**
 * YoursSpeciallyJewellery - Shopping Bag Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Shopping Bag | YoursSpeciallyJewellery';
$metaDescription = 'Review your curated luxury jewellery selections, apply promotional vouchers, and proceed to bespoke checkout.';

$cartItems = [];
$subtotal = 0.0;
$shippingCharge = (float)getSetting('shipping_flat_rate', '99.00');
$freeShippingThreshold = (float)getSetting('free_shipping_threshold', '999.00');
$discountAmount = 0.0;
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;

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

    foreach ($cartItems as $item) {
        $subtotal += ($item['quantity'] * $item['price']);
    }

    // Free shipping calculation
    if ($subtotal >= $freeShippingThreshold || $subtotal == 0) {
        $shippingCharge = 0.0;
    }

    // Coupon discount calculation
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

} catch (Exception $e) {
    error_log("Cart load error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 5rem;">
    <h1 style="font-size: 2.2rem; color: var(--primary); margin-bottom: 0.5rem; text-align: center;">Your Shopping Bag</h1>
    <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2.5rem;">Carefully crafted treasures waiting to illuminate your moments.</p>

    <?php if (empty($cartItems)): ?>
        <div style="background: #fff; padding: 4.5rem 2rem; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); max-width: 600px; margin: 0 auto;">
            <svg viewBox="0 0 24 24" width="56" height="56" fill="none" stroke="var(--primary)" stroke-width="1.3" style="margin-bottom: 1.2rem;">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <h3 style="margin-bottom: 0.6rem; color: var(--primary);">Your Shopping Bag is Empty</h3>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">Discover our latest rose gold and fine jewellery collections to find your perfect match.</p>
            <a href="<?= BASE_URL ?>products.php" class="btn btn-primary">Start Exploring</a>
        </div>
    <?php else: ?>

        <!-- Free Shipping Progress Bar -->
        <?php 
        $neededForFree = max(0, $freeShippingThreshold - $subtotal);
        $progressPercent = min(100, ($subtotal / $freeShippingThreshold) * 100);
        ?>
        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.25rem 1.5rem; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.5rem;">
                <?php if ($neededForFree <= 0): ?>
                    <span style="color: #16A34A; font-weight: 600;">✨ Congratulations! You have unlocked FREE Insured Shipping!</span>
                <?php else: ?>
                    <span style="color: var(--primary);">Add <strong><?= formatPrice($neededForFree) ?></strong> more to unlock <strong>FREE Express Shipping</strong></span>
                <?php endif; ?>
                <span style="font-weight: 600; color: var(--secondary);"><?= round($progressPercent) ?>%</span>
            </div>
            <div style="background: var(--bg-blush-light); height: 8px; border-radius: 4px; overflow: hidden;">
                <div style="background: var(--secondary); height: 100%; width: <?= $progressPercent ?>%; transition: width 0.4s ease;"></div>
            </div>
        </div>

        <div class="cart-layout">
            <!-- Left: Items Table -->
            <div>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Jewellery Creation</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): 
                            $lineTotal = $item['quantity'] * $item['price'];
                            $isOverStock = ($item['quantity'] > $item['stock']);
                        ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($item['slug']) ?>" style="flex-shrink: 0;">
                                            <img src="<?= BASE_URL . e($item['main_image'] ?: 'assets/images/prod-neck-1.jpg') ?>" alt="<?= e($item['name']) ?>" style="width: 70px; height: 70px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-subtle);">
                                        </a>
                                        <div>
                                            <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($item['slug']) ?>" style="font-weight: 600; color: var(--primary); font-family: var(--font-serif); font-size: 1.05rem; display: block;">
                                                <?= e($item['name']) ?>
                                            </a>
                                            <span style="font-size: 0.78rem; color: var(--text-muted);">SKU: <?= e($item['sku']) ?></span>
                                            <?php if ($isOverStock): ?>
                                                <div style="color: #DC2626; font-size: 0.78rem; font-weight: 600; margin-top: 3px;">
                                                    Selected quantity exceeds available stock. Please reduce quantity to proceed.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-weight: 500;"><?= formatPrice($item['price']) ?></td>
                                <td>
                                    <div class="qty-control" style="transform: scale(0.9); transform-origin: left center;">
                                        <button type="button" class="qty-btn" onclick="updateItemQuantity(<?= $item['item_id'] ?>, <?= $item['quantity'] - 1 ?>)">-</button>
                                        <input type="text" value="<?= $item['quantity'] ?>" class="qty-input" readonly>
                                        <button type="button" class="qty-btn" onclick="updateItemQuantity(<?= $item['item_id'] ?>, <?= $item['quantity'] + 1 ?>)" <?= ($item['quantity'] >= $item['stock']) ? 'disabled style="opacity:0.4;"' : '' ?>>+</button>
                                    </div>
                                </td>
                                <td style="font-weight: 700; color: var(--primary);"><?= formatPrice($lineTotal) ?></td>
                                <td style="text-align: right;">
                                    <button type="button" onclick="removeItem(<?= $item['item_id'] ?>)" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px;" title="Remove Item">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <a href="<?= BASE_URL ?>products.php" class="btn btn-outline btn-sm">&larr; Continue Exploring</a>
                </div>
            </div>

            <!-- Right: Order Summary Card -->
            <div>
                <div class="summary-card">
                    <h3>Order Summary</h3>

                    <div class="summary-line">
                        <span>Items Subtotal</span>
                        <span><?= formatPrice($subtotal) ?></span>
                    </div>

                    <div class="summary-line">
                        <span>Insured Delivery</span>
                        <span><?= $shippingCharge > 0 ? formatPrice($shippingCharge) : '<strong style="color:#16A34A;">FREE</strong>' ?></span>
                    </div>

                    <?php if ($discountAmount > 0): ?>
                        <div class="summary-line" style="color: #16A34A;">
                            <span>Promo Voucher (<?= e($appliedCoupon['code']) ?>)</span>
                            <span>-<?= formatPrice($discountAmount) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="summary-total">
                        <span>Estimated Total</span>
                        <span><?= formatPrice($grandTotal) ?></span>
                    </div>

                    <!-- Promo Code Form -->
                    <div style="margin-bottom: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-subtle);">
                        <?php if ($appliedCoupon): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #DCFCE7; padding: 0.6rem 1rem; border-radius: 4px; font-size: 0.85rem; color: #166534;">
                                <span>Coupon <strong><?= e($appliedCoupon['code']) ?></strong> applied!</span>
                                <button type="button" onclick="removeCoupon()" style="background:none; border:none; color:#DC2626; cursor:pointer; font-weight:600;">Remove</button>
                            </div>
                        <?php else: ?>
                            <form onsubmit="applyCoupon(event)" style="display: flex; gap: 6px;">
                                <input type="text" id="couponCodeInput" placeholder="Promo Voucher Code" style="flex-grow: 1; padding: 0.65rem 0.85rem; border: 1px solid var(--border-subtle); border-radius: 4px; font-size: 0.85rem; text-transform: uppercase;">
                                <button type="submit" class="btn btn-outline btn-sm">Apply</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <a href="<?= BASE_URL ?>checkout.php" class="btn btn-primary btn-block">
                        Proceed to Secure Checkout &rarr;
                    </a>

                    <div style="text-align: center; margin-top: 1.25rem; font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        <span>256-Bit SSL Encrypted & Insured Checkout</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateItemQuantity(itemId, newQty) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('item_id', itemId);
    formData.append('quantity', newQty);

    fetch('api/cart.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            window.location.reload();
        } else {
            alert(d.message || 'Error updating item quantity.');
        }
    });
}

function removeItem(itemId) {
    if (!confirm('Remove this jewellery creation from your shopping bag?')) return;
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('item_id', itemId);

    fetch('api/cart.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(() => window.location.reload());
}

function applyCoupon(e) {
    e.preventDefault();
    const code = document.getElementById('couponCodeInput').value.trim();
    if (!code) return;

    const formData = new FormData();
    formData.append('action', 'apply_coupon');
    formData.append('coupon_code', code);

    fetch('api/cart.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            window.location.reload();
        } else {
            alert(d.message);
        }
    });
}

function removeCoupon() {
    const formData = new FormData();
    formData.append('action', 'remove_coupon');

    fetch('api/cart.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(() => window.location.reload());
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
