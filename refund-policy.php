<?php
/**
 * YoursSpeciallyJewellery - Returns & Refund Policy
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Returns & Refund Policy | YoursSpeciallyJewellery';
$metaDescription = 'Our policy on exchanges, returns, and refunds for handcrafted jewellery pieces.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding" style="max-width: 860px; padding-top: 3.5rem;">
    <h1 style="font-size: 2.4rem; color: var(--primary); margin-bottom: 0.8rem;">Returns & Refund Policy</h1>
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 2.5rem;">Last Updated: January 2026</div>

    <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 3rem 2.5rem; box-shadow: var(--shadow-soft); font-size: 0.95rem; color: var(--text-secondary); line-height: 1.8;">
        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">1. Return Window</h3>
        <p style="margin-bottom: 1.5rem;">
            We take utmost pride in crafting pieces of supreme quality. If you receive an item that is defective or damaged during transit, you may request an exchange or return within <strong>7 days</strong> of verified delivery.
        </p>

        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">2. Eligibility Conditions</h3>
        <p style="margin-bottom: 1.5rem;">
            To be eligible for a return or replacement:
        </p>
        <ul style="list-style: disc; margin-left: 24px; margin-bottom: 1.5rem;">
            <li>The jewellery piece must remain unworn, unaltered, and in its pristine original condition.</li>
            <li>All security tags, certificates of authenticity, and velvet keepsake packaging must be intact.</li>
            <li>Customized, engraved, or bespoke pieces tailored to individual ring sizes are non-returnable unless defective.</li>
        </ul>

        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">3. Return Initiation Procedure</h3>
        <p style="margin-bottom: 1.5rem;">
            To initiate a return, contact our concierge on WhatsApp at <strong>+91 <?= e(getSetting('whatsapp_number', '9940474469')) ?></strong> or email <strong><?= e(getSetting('contact_email', 'contact@yoursspecially.com')) ?></strong> with your order reference number and unboxing photographs.
        </p>

        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">4. Refund Processing</h3>
        <p style="margin-bottom: 0;">
            Once our master jewelers inspect and verify the returned item at our atelier, refunds will be credited back to your original payment method (or bank account for COD orders) within 5–7 business days.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
