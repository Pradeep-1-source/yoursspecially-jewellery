<?php
/**
 * YoursSpeciallyJewellery - Footer Component
 */
$whatsappNum = getSetting('whatsapp_number', '9940474469');
$instaUrl = getSetting('instagram_url', 'https://www.instagram.com/yours__specially');
$contactEmail = getSetting('contact_email', 'contact@yoursspecially.com');
$contactPhone = getSetting('contact_phone', '+91 9940474469');
$businessAddress = getSetting('business_address', 'Boutique Studio, Luxury District, Chennai, Tamil Nadu, India');
?>

<!-- Luxury Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-col footer-brand">
                <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
                    <img src="<?= BASE_URL ?>assets/images/logo.jpeg" alt="YoursSpeciallyJewellery" style="height:55px; border-radius:50%;">
                    <div>
                        <span style="font-family:var(--font-serif); font-size:1.45rem; font-weight:700; color:#fff; display:block;">YoursSpecially</span>
                        <span style="font-size:0.65rem; letter-spacing:2px; color:var(--secondary); text-transform:uppercase;">JEWELLERY</span>
                    </div>
                </div>
                <p>Curating timeless expressions of love and modern romance. Handcrafted fine jewellery made with certified craftsmanship and supreme devotion.</p>
                <div class="footer-social-links">
                    <a href="<?= e($instaUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-icon-circle" title="Follow us on Instagram">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </a>
                    <a href="https://wa.me/91<?= preg_replace('/\D/', '', $whatsappNum) ?>?text=<?= urlencode('Hi YoursSpecially, I would like to enquire about your luxury jewellery collection.') ?>" target="_blank" rel="noopener noreferrer" class="social-icon-circle" title="Chat on WhatsApp">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.23 8.23 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.42 0-2.82-.37-4.06-1.07l-.29-.17-3.12.82.83-3.04-.19-.3a8.19 8.19 0 0 1-1.26-4.48c0-4.54 3.7-8.24 8.24-8.24m4.52 11.59c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.49-1.4-1.74-.14-.25-.02-.39.11-.51.11-.11.25-.29.37-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.44 1.03 2.61c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.1-.22-.17-.47-.29z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h4>Explore</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>index.php">Home</a></li>
                    <li><a href="<?= BASE_URL ?>products.php">Shop All Jewellery</a></li>
                    <li><a href="<?= BASE_URL ?>products.php?category=new-arrivals">New Arrivals</a></li>
                    <li><a href="<?= BASE_URL ?>about.php">Our Story & Heritage</a></li>
                    <li><a href="<?= BASE_URL ?>contact.php">Contact Boutique</a></li>
                </ul>
            </div>

            <!-- Customer Care & Policies -->
            <div class="footer-col">
                <h4>Customer Care</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>wishlist.php">My Wishlist</a></li>
                    <li><a href="<?= BASE_URL ?>orders.php">Track Orders</a></li>
                    <li><a href="<?= BASE_URL ?>shipping-policy.php">Shipping Policy</a></li>
                    <li><a href="<?= BASE_URL ?>refund-policy.php">Returns & Refunds</a></li>
                    <li><a href="<?= BASE_URL ?>privacy-policy.php">Privacy Policy</a></li>
                    <li><a href="<?= BASE_URL ?>terms.php">Terms & Conditions</a></li>
                </ul>
            </div>

            <!-- Contact & Hours -->
            <div class="footer-col">
                <h4>Boutique Concierge</h4>
                <p style="font-size:0.9rem; margin-bottom:0.75rem; color:#D3C5CB;"><?= e($businessAddress) ?></p>
                <p style="font-size:0.9rem; margin-bottom:0.5rem; color:#D3C5CB;"><strong>WhatsApp:</strong> +91 <?= e($whatsappNum) ?></p>
                <p style="font-size:0.9rem; margin-bottom:0.5rem; color:#D3C5CB;"><strong>Phone:</strong> <?= e($contactPhone) ?></p>
                <p style="font-size:0.9rem; margin-bottom:1rem; color:#D3C5CB;"><strong>Email:</strong> <?= e($contactEmail) ?></p>
                <div style="font-size:0.8rem; color:#A4949C;">Mon – Sat: 10:00 AM – 8:00 PM IST</div>
            </div>
        </div>

        <div class="footer-bottom">
            <div>&copy; <?= date('Y') ?> <strong>YoursSpeciallyJewellery</strong>. All Rights Reserved. Handcrafted with devotion.</div>
            <div style="display:flex; gap:1.5rem;">
                <a href="<?= BASE_URL ?>privacy-policy.php" style="color:inherit;">Privacy</a>
                <a href="<?= BASE_URL ?>terms.php" style="color:inherit;">Terms</a>
                <a href="<?= BASE_URL ?>refund-policy.php" style="color:inherit;">Refunds</a>
                <a href="<?= BASE_URL ?>admin/login.php" style="color:rgba(255,255,255,0.25);">Admin Portal</a>
            </div>
        </div>
    </div>
</footer>

<!-- Floating WhatsApp Action Button -->
<a href="https://wa.me/91<?= preg_replace('/\D/', '', $whatsappNum) ?>?text=<?= urlencode('Hi YoursSpecially, I would like to enquire about your luxury jewellery collection.') ?>" target="_blank" rel="noopener noreferrer" class="whatsapp-float-btn" title="Chat with Jewellery Stylist on WhatsApp">
    <svg viewBox="0 0 24 24">
        <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.23 8.23 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.42 0-2.82-.37-4.06-1.07l-.29-.17-3.12.82.83-3.04-.19-.3a8.19 8.19 0 0 1-1.26-4.48c0-4.54 3.7-8.24 8.24-8.24m4.52 11.59c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.49-1.4-1.74-.14-.25-.02-.39.11-.51.11-.11.25-.29.37-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.44 1.03 2.61c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.1-.22-.17-.47-.29z"/>
    </svg>
    <span>Chat with Stylist</span>
</a>

<!-- Master Script -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
