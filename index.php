<?php
/**
 * YoursSpeciallyJewellery - Homepage
 * Premium Luxury Jewellery Boutique
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'YoursSpeciallyJewellery | Handcrafted Fine Jewellery & Bespoke Elegance';
$metaDescription = 'Explore exquisite rose gold necklaces, diamond rings, kundan bridal chokers, bangles, and bespoke jewellery at YoursSpeciallyJewellery.';

// Fetch active hero banners from database
try {
    $db = getDBConnection();
    $today = date('Y-m-d');
    $bannerStmt = $db->prepare("SELECT * FROM banners WHERE status = 1 AND (start_date IS NULL OR start_date <= ?) AND (end_date IS NULL OR end_date >= ?) ORDER BY sort_order ASC, id DESC");
    $bannerStmt->execute([$today, $today]);
    $banners = $bannerStmt->fetchAll();

    // Fallback if no banner scheduled
    if (empty($banners)) {
        $banners = [
            [
                'title' => 'Festive Jewellery Collection',
                'subtitle' => 'Drape yourself in handcrafted grandeur with up to 30% off our couture festive edit.',
                'image' => 'assets/images/banner-hero-1.jpg',
                'button_text' => 'Shop Festive Edit',
                'button_link' => 'products.php'
            ]
        ];
    }

    // Fetch active categories
    $catStmt = $db->query("SELECT * FROM categories WHERE status = 1 ORDER BY id ASC LIMIT 8");
    $categories = $catStmt->fetchAll();

    // Fetch featured products
    $featuredStmt = $db->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 AND p.status = 1 ORDER BY p.id DESC LIMIT 8");
    $featuredProducts = $featuredStmt->fetchAll();

    // Fetch new arrivals
    $newArrivalsStmt = $db->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 1 ORDER BY p.id DESC LIMIT 4");
    $newArrivals = $newArrivalsStmt->fetchAll();

    // Fetch best sellers (or top items)
    $bestSellersStmt = $db->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 1 ORDER BY p.price DESC LIMIT 4");
    $bestSellers = $bestSellersStmt->fetchAll();

} catch (Exception $e) {
    error_log("Homepage query error: " . $e->getMessage());
    $banners = [];
    $categories = [];
    $featuredProducts = [];
    $newArrivals = [];
    $bestSellers = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. DYNAMIC HERO PROMOTIONAL BANNER SYSTEM -->
<section class="hero-slider-container" aria-label="Hero Promotional Carousel">
    <div class="hero-slider">
        <?php foreach ($banners as $idx => $banner): ?>
            <div class="hero-slide <?= ($idx === 0) ? 'active' : '' ?>" style="background-image: url('<?= BASE_URL . e($banner['image']) ?>');">
                <div class="hero-slide-overlay"></div>
                <div class="container" style="position: relative; z-index: 2;">
                    <div class="hero-content">
                        <span class="hero-eyebrow">Haute Joaillerie</span>
                        <h1 class="hero-title"><?= e($banner['title']) ?></h1>
                        <?php if (!empty($banner['subtitle'])): ?>
                            <p class="hero-subtitle"><?= e($banner['subtitle']) ?></p>
                        <?php endif; ?>
                        <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                            <a href="<?= BASE_URL . e($banner['button_link'] ?: 'products.php') ?>" class="btn btn-primary">
                                <?= e($banner['button_text'] ?: 'Shop Now') ?>
                            </a>
                            <a href="<?= BASE_URL ?>products.php" class="btn btn-outline-white">Explore Gallery</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($banners) > 1): ?>
        <button class="slider-arrow prev" aria-label="Previous Slide">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <button class="slider-arrow next" aria-label="Next Slide">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
        <div class="slider-dots">
            <?php foreach ($banners as $idx => $b): ?>
                <div class="slider-dot <?= ($idx === 0) ? 'active' : '' ?>" data-index="<?= $idx ?>"></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- 2. SHOP BY CATEGORY -->
<section class="section-padding">
    <div class="container">
        <div class="text-center">
            <span class="section-eyebrow">Curated Collections</span>
            <h2 class="section-title">Shop by Category</h2>
            <p class="section-subtitle">Discover handcrafted artistry sculpted with rose gold romance, sparkling gems, and heritage passion.</p>
        </div>

        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL ?>products.php?category=<?= urlencode($cat['slug']) ?>" class="category-card">
                    <div class="category-img-box">
                        <img src="<?= BASE_URL . e($cat['image'] ?: 'assets/images/cat-necklaces.jpg') ?>" alt="<?= e($cat['name']) ?>" loading="lazy">
                        <div class="category-overlay">
                            <h3 class="category-title"><?= e($cat['name']) ?></h3>
                            <span class="category-explore">Explore Collection &rarr;</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 3. FEATURED JEWELLERY PIECES -->
<section class="section-padding" style="background: var(--bg-blush-light);">
    <div class="container">
        <div class="text-center">
            <span class="section-eyebrow">Signature Masterpieces</span>
            <h2 class="section-title">Featured Creations</h2>
            <p class="section-subtitle">Exquisite pieces hand-selected for their timeless beauty and bespoke craftsmanship.</p>
        </div>

        <div class="product-grid">
            <?php foreach ($featuredProducts as $prod): 
                $hasDiscount = !empty($prod['sale_price']) && $prod['sale_price'] < $prod['price'];
                $discountPercent = $hasDiscount ? round((($prod['price'] - $prod['sale_price']) / $prod['price']) * 100) : 0;
            ?>
                <div class="product-card">
                    <div class="product-thumb-wrap">
                        <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($prod['slug']) ?>">
                            <img src="<?= BASE_URL . e($prod['main_image'] ?: 'assets/images/prod-neck-1.jpg') ?>" alt="<?= e($prod['name']) ?>" class="product-thumb" loading="lazy">
                        </a>

                        <?php if ($hasDiscount): ?>
                            <span class="product-badge badge-discount"><?= $discountPercent ?>% OFF</span>
                        <?php elseif (!empty($prod['featured'])): ?>
                            <span class="product-badge">Featured</span>
                        <?php endif; ?>

                        <div class="product-actions-floating">
                            <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($prod['slug']) ?>" class="floating-action-btn" title="Quick View">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                            <a href="https://wa.me/91<?= preg_replace('/\D/', '', getSetting('whatsapp_number', '9940474469')) ?>?text=<?= urlencode("Hi YoursSpecially, I am interested in " . $prod['name'] . " (" . $prod['sku'] . "). Please share more details.") ?>" target="_blank" class="floating-action-btn" title="Enquire on WhatsApp">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                    <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.23 8.23 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.42 0-2.82-.37-4.06-1.07l-.29-.17-3.12.82.83-3.04-.19-.3a8.19 8.19 0 0 1-1.26-4.48c0-4.54 3.7-8.24 8.24-8.24m4.52 11.59c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.49-1.4-1.74-.14-.25-.02-.39.11-.51.11-.11.25-.29.37-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.44 1.03 2.61c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.1-.22-.17-.47-.29z"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="product-info">
                        <span class="product-category-name"><?= e($prod['category_name'] ?? 'Jewellery') ?></span>
                        <h4 class="product-title">
                            <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($prod['slug']) ?>"><?= e($prod['name']) ?></a>
                        </h4>

                        <div class="product-price-box">
                            <?php if ($hasDiscount): ?>
                                <span class="price-current"><?= formatPrice($prod['sale_price']) ?></span>
                                <span class="price-original"><?= formatPrice($prod['price']) ?></span>
                            <?php else: ?>
                                <span class="price-current"><?= formatPrice($prod['price']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="product-card-footer">
                            <button class="btn-add-cart ajax-add-to-cart" data-product-id="<?= $prod['id'] ?>">
                                Add to Bag
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center" style="margin-top: 3rem;">
            <a href="<?= BASE_URL ?>products.php" class="btn btn-outline">View Complete Showcase &rarr;</a>
        </div>
    </div>
</section>

<!-- 4. EDITORIAL PROMOTIONAL SHOWCASE -->
<section class="section-padding promo-editorial-section">
    <div class="container">
        <div class="promo-banner-card">
            <div class="promo-image-side">
                <img src="<?= BASE_URL ?>assets/images/promo-banner.jpg" alt="The Art of Bespoke Romance">
            </div>
            <div class="promo-content-side">
                <span class="section-eyebrow">The Atelier Story</span>
                <h2 style="font-size: clamp(2rem, 3.5vw, 2.8rem); margin-bottom: 1.2rem;">The Art of Bespoke Romance</h2>
                <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 2rem;">
                    Every design at YoursSpecially begins as an intimate whisper of affection. We blend the warmth of rose gold with the regal majesty of heritage uncut gems and sparkling zircons to craft jewellery that becomes part of your soul story.
                </p>
                <div>
                    <a href="<?= BASE_URL ?>about.php" class="btn btn-primary">Discover Our Craftsmanship</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. NEW ARRIVALS -->
<section class="section-padding">
    <div class="container">
        <div class="text-center">
            <span class="section-eyebrow">Fresh in the Boutique</span>
            <h2 class="section-title">New Arrivals</h2>
            <p class="section-subtitle">Be the first to adorn yourself with our newest seasonal jewellery releases.</p>
        </div>

        <div class="product-grid">
            <?php foreach ($newArrivals as $prod): 
                $hasDiscount = !empty($prod['sale_price']) && $prod['sale_price'] < $prod['price'];
            ?>
                <div class="product-card">
                    <div class="product-thumb-wrap">
                        <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($prod['slug']) ?>">
                            <img src="<?= BASE_URL . e($prod['main_image'] ?: 'assets/images/prod-neck-1.jpg') ?>" alt="<?= e($prod['name']) ?>" class="product-thumb" loading="lazy">
                        </a>
                        <span class="product-badge" style="background: var(--gold);">New Arrival</span>
                    </div>

                    <div class="product-info">
                        <span class="product-category-name"><?= e($prod['category_name'] ?? 'Fine Jewellery') ?></span>
                        <h4 class="product-title">
                            <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($prod['slug']) ?>"><?= e($prod['name']) ?></a>
                        </h4>
                        <div class="product-price-box">
                            <span class="price-current"><?= formatPrice($hasDiscount ? $prod['sale_price'] : $prod['price']) ?></span>
                            <?php if ($hasDiscount): ?>
                                <span class="price-original"><?= formatPrice($prod['price']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-card-footer">
                            <button class="btn-add-cart ajax-add-to-cart" data-product-id="<?= $prod['id'] ?>">Add to Bag</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 6. WHY CHOOSE US / TRUST BADGES -->
<section class="section-padding" style="background: var(--bg-cream); border-top: 1px solid var(--border-subtle);">
    <div class="container">
        <div class="trust-features-grid">
            <div class="trust-item">
                <div class="trust-icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                </div>
                <h4 class="trust-title">Certified Quality</h4>
                <p class="trust-desc">Every piece passes rigorous quality tests for finish, polish, and stone security.</p>
            </div>

            <div class="trust-item">
                <div class="trust-icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                </div>
                <h4 class="trust-title">Bespoke Elegance</h4>
                <p class="trust-desc">Limited edition, handcrafted designs crafted for your unforgettable moments.</p>
            </div>

            <div class="trust-item">
                <div class="trust-icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                </div>
                <h4 class="trust-title">Insured Pan-India Delivery</h4>
                <p class="trust-desc">Complimentary shipping on orders above ₹999 with tamper-proof packaging.</p>
            </div>

            <div class="trust-item">
                <div class="trust-icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                </div>
                <h4 class="trust-title">Stylist Concierge</h4>
                <p class="trust-desc">Connect directly on WhatsApp with our jewellery stylists for sizing and gifts.</p>
            </div>
        </div>
    </div>
</section>

<!-- 7. INSTAGRAM SHOWCASE -->
<section class="section-padding" style="background: #fff;">
    <div class="container">
        <div class="text-center" style="margin-bottom: 2.5rem;">
            <span class="section-eyebrow">Connect With Us</span>
            <h2 class="section-title">Follow @yours__specially</h2>
            <p class="section-subtitle">Join our community of jewellery connoisseurs. Tag us to be featured in our royal gallery.</p>
        </div>

        <div class="insta-grid">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <a href="<?= e(getSetting('instagram_url', 'https://www.instagram.com/yours__specially')) ?>" target="_blank" rel="noopener noreferrer" class="insta-card">
                    <img src="<?= BASE_URL ?>assets/images/insta-<?= $i ?>.jpg" alt="Instagram Post <?= $i ?>" loading="lazy">
                    <div class="insta-hover-overlay">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </div>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- 8. NEWSLETTER PRIVILEGE CLUB -->
<section class="section-padding" style="background: var(--bg-cream);">
    <div class="container">
        <div class="newsletter-card">
            <h2>Join the Privilege Circle</h2>
            <p>Subscribe to receive secret preview access to bridal trunk shows, exclusive festive coupons, and jewellery care guides.</p>
            <form action="<?= BASE_URL ?>contact.php" method="POST" class="newsletter-form">
                <?= csrfField() ?>
                <input type="email" name="newsletter_email" placeholder="Enter your email address..." class="newsletter-input" required>
                <button type="submit" class="btn btn-secondary">Subscribe</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
