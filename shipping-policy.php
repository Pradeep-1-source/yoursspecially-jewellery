<?php
/**
 * YoursSpeciallyJewellery - Shipping & Delivery Policy
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Shipping & Delivery Policy | YoursSpeciallyJewellery';
$metaDescription = 'Complimentary pan-India insured delivery, tamper-proof packaging, and shipping transit timelines.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding" style="max-width: 860px; padding-top: 3.5rem;">
    <h1 style="font-size: 2.4rem; color: var(--primary); margin-bottom: 0.8rem;">Shipping Policy</h1>
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 2.5rem;">Last Updated: January 2026</div>

    <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 3rem 2.5rem; box-shadow: var(--shadow-soft); font-size: 0.95rem; color: var(--text-secondary); line-height: 1.8;">
        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">1. Complimentary Insured Delivery</h3>
        <p style="margin-bottom: 1.5rem;">
            We offer <strong>Free Insured Express Shipping</strong> across all pin codes in India on orders exceeding <strong><?= formatPrice(getSetting('free_shipping_threshold', '999.00')) ?></strong>. For orders below this threshold, a flat delivery charge of <?= formatPrice(getSetting('shipping_flat_rate', '99.00')) ?> applies.
        </p>

        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">2. Transit Timelines</h3>
        <p style="margin-bottom: 1.5rem;">
            - <strong>Metros & Major Cities:</strong> 2 to 4 business days.<br>
            - <strong>Tier 2 & Tier 3 Locations:</strong> 4 to 6 business days.<br>
            - <strong>Custom / Bespoke Creations:</strong> Require an additional 2 to 3 days for artisanal crafting.
        </p>

        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">3. Tamper-Proof Packaging</h3>
        <p style="margin-bottom: 1.5rem;">
            Every YoursSpecially piece is cushioned in an anti-tarnish velvet jewellery box and shipped in a discreet, heavy-duty tamper-proof security envelope with tracking.
        </p>

        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">4. Shipment Tracking</h3>
        <p style="margin-bottom: 0;">
            Upon dispatch, a live tracking link and SMS/WhatsApp confirmation will be sent to your registered contact coordinates. You can also view live order progress inside <a href="<?= BASE_URL ?>orders.php" style="color: var(--secondary); font-weight: 600;">My Account &rarr; Orders</a>.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
