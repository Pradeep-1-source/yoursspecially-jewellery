<?php
/**
 * YoursSpeciallyJewellery - Guest Wishlist Page
 * Completely client-side persistent (localStorage) with instant Add to Bag & Buy Now actions
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'My Wishlist | YoursSpeciallyJewellery';
$metaDescription = 'View your saved luxury handcrafted jewellery pieces. Add to bag or checkout instantly.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 5rem;">
    <!-- Breadcrumb -->
    <nav style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
        <a href="<?= BASE_URL ?>index.php" style="color: inherit;">Home</a> &rarr; 
        <span style="color: var(--primary); font-weight: 500;">My Wishlist</span>
    </nav>

    <div style="text-align: center; margin-bottom: 3rem;">
        <span class="section-eyebrow" style="margin-bottom: 0.5rem; display: block;">Saved Treasures</span>
        <h1 style="font-size: 2.4rem; color: var(--primary); margin-bottom: 0.5rem;">Your Wishlist</h1>
        <p style="color: var(--text-secondary); max-width: 550px; margin: 0 auto;">
            Cherish the pieces that captured your heart. Move them to your shopping bag or buy instantly whenever you're ready.
        </p>
    </div>

    <!-- Empty Wishlist Container -->
    <div id="wishlistEmptyState" style="display: none; background: #fff; padding: 4.5rem 2rem; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); max-width: 580px; margin: 0 auto; box-shadow: var(--shadow-soft);">
        <svg viewBox="0 0 24 24" width="56" height="56" fill="none" stroke="var(--primary)" stroke-width="1.3" style="margin-bottom: 1.2rem;">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
        <h3 style="margin-bottom: 0.6rem; color: var(--primary);">Your Wishlist is Empty</h3>
        <p style="color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6;">
            Explore our curated collections of radiant necklaces, rings, earrings, and bridal sets, and tap the heart icon to save your favorites here.
        </p>
        <a href="<?= BASE_URL ?>products.php" class="btn btn-primary">Discover Jewellery</a>
    </div>

    <!-- Wishlist Grid Container -->
    <div id="wishlistContent" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
            <span style="font-size: 0.95rem; color: var(--text-secondary);">
                Saved Items (<strong id="wishlistTotalCount">0</strong>)
            </span>
            <button type="button" onclick="clearEntireWishlist()" style="background: none; border: none; color: var(--text-muted); font-size: 0.85rem; cursor: pointer; text-decoration: underline;">
                Clear All
            </button>
        </div>

        <div class="wishlist-grid" id="wishlistItemsGrid">
            <!-- Dynamically populated by JS -->
        </div>
    </div>
</div>

<script>
window.renderWishlistPage = function() {
    const list = (typeof getWishlist === 'function') ? getWishlist() : [];
    const emptyState = document.getElementById('wishlistEmptyState');
    const content = document.getElementById('wishlistContent');
    const grid = document.getElementById('wishlistItemsGrid');
    const countSpan = document.getElementById('wishlistTotalCount');

    if (!emptyState || !content || !grid) return;

    if (!list || list.length === 0) {
        emptyState.style.display = 'block';
        content.style.display = 'none';
        return;
    }

    emptyState.style.display = 'none';
    content.style.display = 'block';
    if (countSpan) countSpan.textContent = list.length;

    let html = '';
    list.forEach(item => {
        const hasDiscount = item.sale_price && parseFloat(item.sale_price) > 0 && parseFloat(item.sale_price) < parseFloat(item.price);
        const displayPrice = hasDiscount ? item.sale_price : item.price;

        html += `
            <div class="product-card" id="wishlist-item-${item.id}">
                <div class="product-thumb-wrap">
                    <a href="<?= BASE_URL ?>product.php?slug=${encodeURIComponent(item.slug)}">
                        <img src="<?= BASE_URL ?>${item.image || 'assets/images/prod-neck-1.jpg'}" alt="${escapeHtml(item.name)}" class="product-thumb" loading="lazy">
                    </a>

                    <div class="product-actions-floating">
                        <button type="button" class="floating-action-btn" title="Remove from Wishlist" onclick="removeFromWishlistPage('${item.id}')" style="color: #DC2626;">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="product-info">
                    <h4 class="product-title" style="margin-bottom: 0.5rem;">
                        <a href="<?= BASE_URL ?>product.php?slug=${encodeURIComponent(item.slug)}">${escapeHtml(item.name)}</a>
                    </h4>

                    <div class="product-price-box">
                        <span class="price-current">₹${parseFloat(displayPrice).toLocaleString('en-IN')}</span>
                        ${hasDiscount ? `<span class="price-original">₹${parseFloat(item.price).toLocaleString('en-IN')}</span>` : ''}
                    </div>

                    <div class="product-card-footer">
                        <button type="button" class="btn-add-cart ajax-add-to-cart" data-product-id="${item.id}">Add to Bag</button>
                        <button type="button" class="btn-buy-now" data-product-id="${item.id}" onclick="buyNow('${item.id}')">Buy Now</button>
                    </div>
                </div>
            </div>
        `;
    });

    grid.innerHTML = html;
};

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
    });
}

function removeFromWishlistPage(productId) {
    if (typeof toggleWishlist === 'function') {
        toggleWishlist({ id: productId });
    }
    renderWishlistPage();
}

function clearEntireWishlist() {
    if (confirm('Are you sure you want to clear your saved wishlist items?')) {
        saveWishlist([]);
        renderWishlistPage();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    renderWishlistPage();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
