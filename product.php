<?php
/**
 * YoursSpeciallyJewellery - Product Details Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header("Location: " . BASE_URL . "products.php");
    exit;
}

try {
    $db = getDBConnection();

    // Fetch product details
    $stmt = $db->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.slug = ? AND p.status = 1 LIMIT 1");
    $stmt->execute([$slug]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        require_once __DIR__ . '/includes/header.php';
        echo '<div class="container section-padding text-center">
                <h2>Jewellery Piece Not Found</h2>
                <p style="margin: 1rem 0 2rem; color: var(--text-secondary);">The jewellery creation you are looking for may have been retired or moved.</p>
                <a href="' . BASE_URL . 'products.php" class="btn btn-primary">Browse Active Collections</a>
              </div>';
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }

    // Fetch additional gallery images
    $imgStmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
    $imgStmt->execute([$product['id']]);
    $galleryImages = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

    // If main image not in gallery list, put it first
    if (!empty($product['main_image']) && !in_array($product['main_image'], $galleryImages)) {
        array_unshift($galleryImages, $product['main_image']);
    }
    if (empty($galleryImages)) {
        $galleryImages = [$product['main_image'] ?: 'assets/images/prod-neck-1.jpg'];
    }

    // Fetch related products in the same category
    $relStmt = $db->prepare("SELECT p.*, c.name AS category_name 
                             FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             WHERE p.category_id = ? AND p.id != ? AND p.status = 1 
                             ORDER BY RAND() LIMIT 4");
    $relStmt->execute([$product['category_id'], $product['id']]);
    $relatedProducts = $relStmt->fetchAll();

} catch (Exception $e) {
    error_log("Product detail error: " . $e->getMessage());
    header("Location: " . BASE_URL . "products.php");
    exit;
}

$hasDiscount = !empty($product['sale_price']) && $product['sale_price'] < $product['price'];
$effectivePrice = $hasDiscount ? $product['sale_price'] : $product['price'];
$discountPercent = $hasDiscount ? round((($product['price'] - $product['sale_price']) / $product['price']) * 100) : 0;
$inStock = ($product['stock'] > 0);

$pageTitle = e($product['name']) . ' | YoursSpeciallyJewellery';
$metaDescription = e(substr(strip_tags($product['description']), 0, 155));

// WhatsApp enquiry text
$whatsappNum = getSetting('whatsapp_number', '9940474469');
$waText = urlencode("Hi, I'm interested in " . $product['name'] . " (SKU: " . $product['sku'] . "). Please share more details.");
$waUrl = "https://wa.me/91" . preg_replace('/\D/', '', $whatsappNum) . "?text=" . $waText;

require_once __DIR__ . '/includes/header.php';
?>

<!-- JSON-LD Structured Data for Google Rich Snippets -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": <?= json_encode($product['name']) ?>,
  "image": [<?= json_encode(BASE_URL . $galleryImages[0]) ?>],
  "description": <?= json_encode(strip_tags($product['description'])) ?>,
  "sku": <?= json_encode($product['sku']) ?>,
  "brand": {
    "@type": "Brand",
    "name": "YoursSpeciallyJewellery"
  },
  "offers": {
    "@type": "Offer",
    "url": <?= json_encode(BASE_URL . 'product.php?slug=' . $product['slug']) ?>,
    "priceCurrency": "INR",
    "price": <?= json_encode($effectivePrice) ?>,
    "availability": <?= $inStock ? '"https://schema.org/InStock"' : '"https://schema.org/OutOfStock"' ?>,
    "itemCondition": "https://schema.org/NewCondition"
  }
}
</script>

<div class="container" style="padding-top: 2rem; padding-bottom: 5rem;">
    <!-- Breadcrumb & Back Navigation -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <nav style="font-size: 0.85rem; color: var(--text-muted);">
            <a href="<?= BASE_URL ?>index.php" style="color: inherit;">Home</a> &rarr; 
            <a href="<?= BASE_URL ?>products.php" style="color: inherit;">Shop</a> &rarr; 
            <a href="<?= BASE_URL ?>products.php?category=<?= urlencode($product['category_slug']) ?>" style="color: inherit;"><?= e($product['category_name']) ?></a> &rarr; 
            <span style="color: var(--primary); font-weight: 500;"><?= e($product['name']) ?></span>
        </nav>
        <a href="javascript:void(0)" onclick="goBackToCollection()" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.4rem 1rem;">
            &larr; Back to Collection
        </a>
    </div>

    <script>
    function goBackToCollection() {
        const lastUrl = sessionStorage.getItem('ysj_last_listing_url');
        if (lastUrl && (lastUrl.includes('products.php') || lastUrl.includes('index.php'))) {
            window.location.href = lastUrl;
        } else if (document.referrer && (document.referrer.includes('products.php') || document.referrer.includes('index.php'))) {
            window.location.href = document.referrer;
        } else {
            window.location.href = '<?= BASE_URL ?>products.php';
        }
    }
    </script>

    <!-- Main Product Grid -->
    <div class="product-detail-layout">
        <!-- Gallery Side -->
        <div class="product-detail-gallery">
            <div class="gallery-main" id="galleryContainer">
                <img src="<?= BASE_URL . e($galleryImages[0]) ?>" alt="<?= e($product['name']) ?>" id="mainGalleryImg">
            </div>

            <?php if (count($galleryImages) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach ($galleryImages as $idx => $img): ?>
                        <div class="thumb-item <?= ($idx === 0) ? 'active' : '' ?>" data-src="<?= BASE_URL . e($img) ?>">
                            <img src="<?= BASE_URL . e($img) ?>" alt="<?= e($product['name']) ?> thumbnail <?= $idx + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Information Side -->
        <div class="product-detail-info">
            <span class="section-eyebrow" style="margin-bottom: 0.3rem;"><?= e($product['category_name']) ?></span>
            <h1><?= e($product['name']) ?></h1>
            <div class="sku-badge">SKU: <strong><?= e($product['sku']) ?></strong></div>

            <!-- Price Box -->
            <div class="detail-price-box">
                <span class="detail-price-sale"><?= formatPrice($effectivePrice) ?></span>
                <?php if ($hasDiscount): ?>
                    <span class="detail-price-orig"><?= formatPrice($product['price']) ?></span>
                    <span style="font-size: 0.95rem; font-weight: 600; color: #16A34A; background: #DCFCE7; padding: 3px 10px; border-radius: 4px;">
                        Save <?= formatPrice($product['price'] - $product['sale_price']) ?> (<?= $discountPercent ?>% OFF)
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!$inStock): ?>
            <!-- Stock Availability (Only for Sold Out) -->
            <div class="stock-status" style="margin-bottom: 1.25rem;">
                <span class="stock-out">&#9679; Currently Sold Out</span>
            </div>
            <?php endif; ?>

            <!-- Description -->
            <div style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.8; margin-bottom: 2rem;">
                <?= nl2br(e($product['description'])) ?>
            </div>

            <!-- Purchase Controls -->
            <?php if ($inStock): ?>
                <div style="display: flex; gap: 0.85rem; align-items: center; margin-bottom: 2rem; flex-wrap: wrap;">
                    <div class="qty-control">
                        <button type="button" class="qty-btn" onclick="updateQty(-1)">-</button>
                        <input type="number" id="productQuantity" value="1" min="1" max="<?= (int)$product['stock'] ?>" class="qty-input" readonly>
                        <button type="button" class="qty-btn" onclick="updateQty(1)">+</button>
                    </div>

                    <button type="button" class="btn btn-primary ajax-add-to-cart" data-product-id="<?= $product['id'] ?>" style="flex: 1; min-width: 170px;">
                        Add to Shopping Bag
                    </button>

                    <button type="button" class="btn btn-secondary" onclick="buyNow(<?= $product['id'] ?>)" style="min-width: 120px;">
                        Buy Now
                    </button>

                    <!-- Wishlist Toggle -->
                    <button type="button" class="wishlist-toggle-btn" 
                            data-product-id="<?= $product['id'] ?>"
                            data-name="<?= e($product['name']) ?>"
                            data-slug="<?= e($product['slug']) ?>"
                            data-price="<?= e($product['price']) ?>"
                            data-sale-price="<?= e($product['sale_price'] ?? '') ?>"
                            data-image="<?= e($galleryImages[0] ?? 'assets/images/prod-neck-1.jpg') ?>"
                            data-sku="<?= e($product['sku']) ?>"
                            data-in-stock="<?= $inStock ? '1' : '0' ?>"
                            style="width: 44px; height: 44px; flex-shrink: 0; box-shadow: var(--shadow-soft); border: 1px solid var(--border-subtle);"
                            title="Save to Wishlist" aria-label="Save to Wishlist">
                        <svg viewBox="0 0 24 24" width="20" height="20" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </button>
                </div>
            <?php else: ?>
                <div style="background: #FFF1F2; border: 1px solid #FECDD3; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; color: #9F1239; font-size: 0.9rem;">
                    This handcrafted design is currently out of stock. Please check back soon or explore our other collections.
                </div>
            <?php endif; ?>

            <!-- Assurance Highlights -->
            <div style="border-top: 1px solid var(--border-subtle); padding-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.85rem; color: var(--text-secondary);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--primary)" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Complimentary Insured Shipping on all orders above ₹999</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--primary)" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Delivered in signature YoursSpecially velvet jewellery keepsake box</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--primary)" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>100% Quality & Authenticity Guarantee</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Jewellery Showcase -->
    <?php if (!empty($relatedProducts)): ?>
        <div style="margin-top: 5rem; border-top: 1px solid var(--border-subtle); padding-top: 4rem;">
            <div class="text-center" style="margin-bottom: 2.5rem;">
                <span class="section-eyebrow">Complementary Pieces</span>
                <h2 class="section-title">You May Also Adore</h2>
            </div>

            <div class="product-grid">
                <?php foreach ($relatedProducts as $rel): 
                    $relHasDiscount = !empty($rel['sale_price']) && $rel['sale_price'] < $rel['price'];
                ?>
                    <div class="product-card">
                        <div class="product-thumb-wrap">
                            <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($rel['slug']) ?>">
                                <img src="<?= BASE_URL . e($rel['main_image'] ?: 'assets/images/prod-neck-1.jpg') ?>" alt="<?= e($rel['name']) ?>" class="product-thumb" loading="lazy">
                            </a>
                        </div>
                        <div class="product-info">
                            <span class="product-category-name"><?= e($rel['category_name']) ?></span>
                            <h4 class="product-title">
                                <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($rel['slug']) ?>"><?= e($rel['name']) ?></a>
                            </h4>
                            <div class="product-price-box">
                                <span class="price-current"><?= formatPrice($relHasDiscount ? $rel['sale_price'] : $rel['price']) ?></span>
                            </div>
                            <div class="product-card-footer">
                                <button class="btn-add-cart ajax-add-to-cart" data-product-id="<?= $rel['id'] ?>">Add to Bag</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateQty(delta) {
    const input = document.getElementById('productQuantity');
    if (!input) return;
    let current = parseInt(input.value) || 1;
    const max = parseInt(input.getAttribute('max')) || 99;
    current += delta;
    if (current < 1) current = 1;
    if (current > max) current = max;
    input.value = current;
}

function buyNow(productId) {
    const qtyInput = document.getElementById('productQuantity');
    const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch('api/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = '<?= BASE_URL ?>checkout.php';
        } else {
            alert(data.message || 'Unable to proceed to checkout.');
        }
    })
    .catch(() => {
        window.location.href = '<?= BASE_URL ?>checkout.php';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
