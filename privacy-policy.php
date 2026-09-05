<?php
/**
 * YoursSpeciallyJewellery - Privacy Policy
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Privacy Policy | YoursSpeciallyJewellery';
$metaDescription = 'Our commitment to safeguarding your private client information and data protection practices.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding" style="max-width: 860px; padding-top: 3.5rem;">
    <h1 style="font-size: 2.4rem; color: var(--primary); margin-bottom: 0.8rem;">Privacy Policy</h1>
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 2.5rem;">Last Updated: January 2026</div>

    <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 3rem 2.5rem; box-shadow: var(--shadow-soft); font-size: 0.95rem; color: var(--text-secondary); line-height: 1.8;">
        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">1. Introduction & Overview</h3>
        <p style="margin-bottom: 1.5rem;">
            At <strong>YoursSpeciallyJewellery</strong>, we respect your privacy and are committed to protecting the personal data of our valued patrons. This Privacy Policy details how we collect, safeguard, and utilize your information when you interact with our boutique website and concierge channels.
        </p>

        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">2. Information We Collect</h3>
        <p style="margin-bottom: 1.5rem;">
            We collect information essential to providing our fine jewellery services, including your name, delivery address, telephone number, email address, transaction records, and communication preferences. We do not store sensitive payment card details or net banking credentials on our servers.
        </p>

        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">3. Utilization of Your Information</h3>
        <p style="margin-bottom: 1.5rem;">
            Your personal data is strictly employed to process orders, arrange insured courier deliveries, dispatch shipment milestones, verify legitimate transactions, and provide personalized concierge support via WhatsApp or email upon your request.
        </p>

        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">4. Third-Party Service Providers</h3>
        <p style="margin-bottom: 1.5rem;">
            We partner with trusted, secure entities—such as authorized payment gateways and verified courier logistics partners—solely for order fulfillment. We never trade, rent, or vend client information to unrelated third parties.
        </p>

        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">5. Security of Client Data</h3>
        <p style="margin-bottom: 1.5rem;">
            Our infrastructure incorporates standard 256-bit Secure Socket Layer (SSL) encryption, password hashing, and restricted database access to defend your private records against unauthorized intrusion.
        </p>

        <h3 style="color: var(--primary); margin-bottom: 0.8rem;">6. Contacting the Data Protection Officer</h3>
        <p style="margin-bottom: 0;">
            For privacy inquiries or requests to update your registered account details, please reach out directly to <a href="mailto:<?= e(getSetting('contact_email', 'contact@yoursspecially.com')) ?>" style="color: var(--secondary); font-weight: 600;"><?= e(getSetting('contact_email', 'contact@yoursspecially.com')) ?></a>.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
