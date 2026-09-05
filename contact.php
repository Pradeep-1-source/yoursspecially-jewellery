<?php
/**
 * YoursSpeciallyJewellery - Contact & Boutique Concierge
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$whatsappNum = getSetting('whatsapp_number', '9940474469');
$instaUrl = getSetting('instagram_url', 'https://www.instagram.com/yours__specially');
$contactEmail = getSetting('contact_email', 'contact@yoursspecially.com');
$contactPhone = getSetting('contact_phone', '+91 9940474469');
$businessAddress = getSetting('business_address', 'Boutique Studio, Luxury District, Chennai, Tamil Nadu, India');

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $cName = trim($_POST['name'] ?? '');
        $cEmail = trim($_POST['email'] ?? '');
        $cMessage = trim($_POST['message'] ?? '');

        if (!empty($cName) && !empty($cEmail) && !empty($cMessage)) {
            // Log message securely on server or dispatch email notification
            error_log("Contact Inquiry received from {$cName} ({$cEmail}): {$cMessage}");
            setFlash('success', 'Thank you for reaching out to our boutique atelier. A jewellery stylist will be in touch with you shortly.');
            header("Location: " . BASE_URL . "contact.php");
            exit;
        }
    }
}

$pageTitle = 'Boutique Concierge & Contact | YoursSpeciallyJewellery';
$metaDescription = 'Connect with our jewellery stylists on WhatsApp or visit our boutique atelier in Chennai. We are pleased to assist you with bespoke sizing and custom creations.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding" style="padding-top: 3.5rem;">
    <div class="text-center" style="max-width: 680px; margin: 0 auto 3.5rem;">
        <span class="section-eyebrow">Personalized Assistance</span>
        <h1 style="font-size: clamp(2.2rem, 4vw, 3rem); color: var(--primary); margin-bottom: 0.8rem;">
            Atelier Concierge
        </h1>
        <p style="color: var(--text-secondary); line-height: 1.7;">
            Whether you desire styling guidance for a wedding, custom sizing, or immediate assistance, our dedicated jewellery curators are here for you.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 3.5rem; align-items: start;">
        <!-- Left: Contact Form -->
        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 3rem 2.5rem; box-shadow: var(--shadow-card);">
            <h3 style="font-size: 1.35rem; color: var(--primary); margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.8rem;">
                Send an Atelier Message
            </h3>

            <form method="POST" action="<?= BASE_URL ?>contact.php">
                <?= csrfField() ?>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="contactName">Your Name *</label>
                        <input type="text" id="contactName" name="name" class="form-control" placeholder="Full name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactEmail">Email Address *</label>
                        <input type="email" id="contactEmail" name="email" class="form-control" placeholder="you@domain.com" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="contactPhone">Phone / WhatsApp</label>
                        <input type="tel" id="contactPhone" name="phone" class="form-control" placeholder="Mobile number">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactSubject">Inquiry Topic</label>
                        <select id="contactSubject" name="subject" class="form-control">
                            <option value="General Styling Inquiry">General Styling Guidance</option>
                            <option value="Bridal Trousseau Consultation">Bridal Trousseau Consultation</option>
                            <option value="Custom Sizing / Custom Piece">Custom Sizing / Bespoke Piece</option>
                            <option value="Order Tracking / Delivery">Order Tracking / Delivery</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="contactMessage">Your Message *</label>
                    <textarea id="contactMessage" name="message" rows="5" class="form-control" placeholder="Please share how our stylists may assist you..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 0.9rem;">
                    Transmit Message to Atelier &rarr;
                </button>
            </form>
        </div>

        <!-- Right: Direct Channels -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- WhatsApp Highlight Box -->
            <div style="background: linear-gradient(135deg, #128C7E 0%, #075E54 100%); color: #fff; padding: 2rem; border-radius: var(--radius-md); box-shadow: var(--shadow-card);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor">
                        <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.23 8.23 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.42 0-2.82-.37-4.06-1.07l-.29-.17-3.12.82.83-3.04-.19-.3a8.19 8.19 0 0 1-1.26-4.48c0-4.54 3.7-8.24 8.24-8.24m4.52 11.59c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.49-1.4-1.74-.14-.25-.02-.39.11-.51.11-.11.25-.29.37-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.44 1.03 2.61c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.1-.22-.17-.47-.29z"/>
                    </svg>
                    <h4 style="color: #fff; font-size: 1.3rem; margin: 0;">Instant WhatsApp Chat</h4>
                </div>
                <p style="font-size: 0.9rem; color: rgba(255,255,255,0.9); margin-bottom: 1.5rem; line-height: 1.6;">
                    For instant styling recommendations, videos of pieces under boutique lighting, or ring sizing support, chat live with our atelier stylists.
                </p>
                <a href="https://wa.me/91<?= preg_replace('/\D/', '', $whatsappNum) ?>?text=<?= urlencode('Hi YoursSpecially, I would like to speak with a jewellery concierge stylist.') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-block" style="background: #25D366; color: #fff; font-weight: 700;">
                    Open WhatsApp Chat &rarr;
                </a>
            </div>

            <!-- Instagram Connect Box -->
            <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2rem; box-shadow: var(--shadow-soft);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="var(--primary)" stroke-width="2">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                    <h4 style="font-size: 1.2rem; color: var(--primary); margin: 0;">Instagram Atelier</h4>
                </div>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
                    Follow <strong>@yours__specially</strong> for reels, new collection drops, and behind-the-scenes glimpses.
                </p>
                <a href="<?= e($instaUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-block btn-sm">
                    Visit Instagram Profile &rarr;
                </a>
            </div>

            <!-- Boutique Address & Hours -->
            <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2rem; box-shadow: var(--shadow-soft);">
                <h4 style="font-size: 1.2rem; color: var(--primary); margin-bottom: 1rem;">Boutique Information</h4>
                <div style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.8;">
                    <p style="margin-bottom: 0.75rem;"><strong>Address:</strong><br><?= e($businessAddress) ?></p>
                    <p style="margin-bottom: 0.75rem;"><strong>Phone:</strong> <?= e($contactPhone) ?></p>
                    <p style="margin-bottom: 0.75rem;"><strong>Email:</strong> <?= e($contactEmail) ?></p>
                    <p style="margin-bottom: 0;"><strong>Atelier Hours:</strong> Mon – Sat: 10:00 AM – 8:00 PM IST</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 860px) {
    div[style*="grid-template-columns: 1.2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
