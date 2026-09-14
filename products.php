<?php
/**
 * YoursSpeciallyJewellery - Product Listing & Catalog Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$search = trim($_GET['search'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$currentCategory = null;
$whereConditions = ["p.status = 1"];
$params = [];

try {
    $db = getDBConnection();

    // Fetch all active categories for filter sidebar
    $catStmt = $db->query("SELECT id, name, slug, (SELECT COUNT(*) FROM products WHERE category_id = categories.id AND status = 1) as prod_count FROM categories WHERE status = 1 ORDER BY name ASC");
    $allCategories = $catStmt->fetchAll();

    // Filter by Category
    if (!empty($categorySlug)) {
        $cStmt = $db->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
        $cStmt->execute([$categorySlug]);
        $currentCategory = $cStmt->fetch();

        if ($currentCategory) {
            $whereConditions[] = "p.category_id = ?";
            $params[] = $currentCategory['id'];
        }
    }

    // Filter by Search Query
    if (!empty($search)) {
        $whereConditions[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Filter by Price Range
    if ($minPrice > 0) {
        $whereConditions[] = "COALESCE(p.sale_price, p.price) >= ?";
        $params[] = $minPrice;
    }
    if ($maxPrice > 0) {
        $whereConditions[] = "COALESCE(p.sale_price, p.price) <= ?";
        $params[] = $maxPrice;
    }

    // Sorting Logic
    $orderBy = "p.id DESC"; // Default newest
    switch ($sort) {
        case 'price_low':
            $orderBy = "COALESCE(p.sale_price, p.price) ASC";
            break;
        case 'price_high':
            $orderBy = "COALESCE(p.sale_price, p.price) DESC";
            break;
        case 'featured':
            $orderBy = "p.featured DESC, p.id DESC";
            break;
        case 'name_asc':
            $orderBy = "p.name ASC";
            break;
    }

    $whereSql = implode(" AND ", $whereConditions);

    // Count total matching products for pagination
    $countSql = "SELECT COUNT(*) FROM products p WHERE " . $whereSql;
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalProducts = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $perPage);

    // Fetch paginated products
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE " . $whereSql . " 
            ORDER BY " . $orderBy . " 
            LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Products page error: " . $e->getMessage());
    $products = [];
    $allCategories = [];
    $totalProducts = 0;
    $totalPages = 1;
}

$pageTitle = $currentCategory ? e($currentCategory['name']) . ' | YoursSpeciallyJewellery' : 'Luxury Jewellery Collection | YoursSpecially';
$metaDescription = $currentCategory ? 'Explore our handcrafted ' . e($currentCategory['name']) . ' collection made with exquisite artistry and fine materials.' : 'Browse our complete catalogue of fine rings, necklaces, earrings, and bridal jewellery.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 4rem;">
    <!-- Breadcrumb & Header Title -->
    <div style="margin-bottom: 2rem;">
        <nav style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.6rem;">
            <a href="<?= BASE_URL ?>index.php" style="color: inherit;">Home</a> &rarr; 
            <a href="<?= BASE_URL ?>products.php" style="color: inherit;">Shop</a>
            <?php if ($currentCategory): ?>
                &rarr; <span style="color: var(--primary); font-weight: 500;"><?= e($currentCategory['name']) ?></span>
            <?php endif; ?>
        </nav>
        <h1 style="font-size: 2.4rem; color: var(--primary); margin-bottom: 0.5rem;">
            <?= $currentCategory ? e($currentCategory['name']) : 'All Jewellery Collections' ?>
        </h1>
        <p style="color: var(--text-secondary); max-width: 680px;">
            <?= $currentCategory && !empty($currentCategory['description']) ? e($currentCategory['description']) : 'Curated creations forged in pure elegance, timeless rose gold luster, and sparkling gemstones.' ?>
        </p>
    </div>

    <!-- Catalog Control Toolbar -->
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; background: #fff; padding: 1rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 2rem;">
        <div style="font-size: 0.9rem; color: var(--text-secondary);">
            Showing <strong><?= count($products) ?></strong> of <strong><?= $totalProducts ?></strong> items
            <?php if (!empty($search)): ?>
                matching "<strong><?= e($search) ?></strong>"
            <?php endif; ?>
        </div>

        <form method="GET" action="<?= BASE_URL ?>products.php" style="display: flex; align-items: center; gap: 1rem; margin: 0;">
            <?php if (!empty($categorySlug)): ?><input type="hidden" name="category" value="<?= e($categorySlug) ?>"><?php endif; ?>
            <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?= e($search) ?>"><?php endif; ?>

            <label for="sortSelect" style="font-size: 0.85rem; font-weight: 500; color: var(--primary);">Sort By:</label>
            <select name="sort" id="sortSelect" onchange="this.form.submit()" style="padding: 0.45rem 1rem; border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); font-size: 0.88rem; background: var(--bg-cream); color: var(--text-primary); cursor: pointer;">
                <option value="newest" <?= ($sort == 'newest') ? 'selected' : '' ?>>Newest Arrivals</option>
                <option value="featured" <?= ($sort == 'featured') ? 'selected' : '' ?>>Featured Creations</option>
                <option value="price_low" <?= ($sort == 'price_low') ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_high" <?= ($sort == 'price_high') ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="name_asc" <?= ($sort == 'name_asc') ? 'selected' : '' ?>>Name: A to Z</option>
            </select>
        </form>
    </div>

    <!-- Main Content Layout (Sidebar Filters + Grid) -->
    <div class="catalog-layout">
        <!-- Filters Sidebar -->
        <aside style="background: #fff; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
            <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.6rem;">Categories</h3>
            <ul style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 2rem;">
                <li>
                    <a href="<?= BASE_URL ?>products.php" style="display: flex; justify-content: space-between; font-size: 0.9rem; color: <?= empty($categorySlug) ? 'var(--secondary); font-weight:600;' : 'var(--text-secondary);' ?>">
                        <span>All Collections</span>
                    </a>
                </li>
                <?php foreach ($allCategories as $cat): ?>
                    <li>
                        <a href="<?= BASE_URL ?>products.php?category=<?= urlencode($cat['slug']) ?>" style="display: flex; justify-content: space-between; font-size: 0.9rem; color: <?= ($categorySlug == $cat['slug']) ? 'var(--secondary); font-weight:600;' : 'var(--text-secondary);' ?>">
                            <span><?= e($cat['name']) ?></span>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">(<?= $cat['prod_count'] ?>)</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Price Filter -->
            <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.6rem;">Filter by Price</h3>
            <form method="GET" action="<?= BASE_URL ?>products.php">
                <?php if (!empty($categorySlug)): ?><input type="hidden" name="category" value="<?= e($categorySlug) ?>"><?php endif; ?>
                <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?= e($search) ?>"><?php endif; ?>
                <?php if (!empty($sort)): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>

                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                    <input type="number" name="min_price" placeholder="Min ₹" value="<?= $minPrice > 0 ? e($minPrice) : '' ?>" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-subtle); border-radius: 4px; font-size: 0.85rem;">
                    <input type="number" name="max_price" placeholder="Max ₹" value="<?= $maxPrice > 0 ? e($maxPrice) : '' ?>" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-subtle); border-radius: 4px; font-size: 0.85rem;">
                </div>
                <button type="submit" class="btn btn-outline btn-block btn-sm">Apply Price</button>
            </form>

            <?php if (!empty($categorySlug) || !empty($search) || $minPrice > 0 || $maxPrice > 0): ?>
                <div style="margin-top: 1.5rem; text-align: center;">
                    <a href="<?= BASE_URL ?>products.php" style="font-size: 0.82rem; color: var(--secondary); text-decoration: underline;">Reset All Filters</a>
                </div>
            <?php endif; ?>
        </aside>

        <!-- Product Grid Area -->
        <main>
            <?php if (empty($products)): ?>
                <div style="background: #fff; padding: 4rem 2rem; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="var(--primary)" stroke-width="1.5" style="margin-bottom: 1rem;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <h3 style="margin-bottom: 0.5rem;">No Jewellery Pieces Found</h3>
                    <p style="color: var(--text-secondary); max-width: 420px; margin: 0 auto 1.5rem;">
                        We couldn't find any designs matching your selected criteria. Try adjusting your filters or search keywords.
                    </p>
                    <a href="<?= BASE_URL ?>products.php" class="btn btn-primary">Browse All Creations</a>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $prod): 
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
                                    <button type="button" class="wishlist-toggle-btn" 
                                            data-product-id="<?= $prod['id'] ?>"
                                            data-name="<?= e($prod['name']) ?>"
                                            data-slug="<?= e($prod['slug']) ?>"
                                            data-price="<?= e($prod['price']) ?>"
                                            data-sale-price="<?= e($prod['sale_price'] ?? '') ?>"
                                            data-image="<?= e($prod['main_image'] ?: 'assets/images/prod-neck-1.jpg') ?>"
                                            data-sku="<?= e($prod['sku']) ?>"
                                            data-in-stock="<?= ($prod['stock'] > 0) ? '1' : '0' ?>"
                                            title="Save to Wishlist" aria-label="Save to Wishlist">
                                        <svg viewBox="0 0 24 24" width="16" height="16" stroke-width="2">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                        </svg>
                                    </button>
                                    <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($prod['slug']) ?>" class="floating-action-btn" title="View Details">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
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
                                    <?php if ((int)$prod['stock'] > 0): ?>
                                        <button type="button" class="btn-add-cart ajax-add-to-cart" data-product-id="<?= $prod['id'] ?>">Add to Bag</button>
                                        <button type="button" class="btn-buy-now" data-product-id="<?= $prod['id'] ?>" onclick="buyNow(<?= $prod['id'] ?>)">Buy Now</button>
                                    <?php else: ?>
                                        <span class="btn-out-of-stock">Sold Out</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 3rem;">
                        <?php for ($p = 1; $p <= $totalPages; $p++): 
                            $query = $_GET;
                            $query['page'] = $p;
                            $pageUrl = 'products.php?' . http_build_query($query);
                        ?>
                            <a href="<?= BASE_URL . e($pageUrl) ?>" style="padding: 0.5rem 1rem; border-radius: 4px; border: 1px solid var(--border-subtle); background: <?= ($page === $p) ? 'var(--primary); color:#fff;' : '#fff; color:var(--primary);' ?> font-weight: 600; font-size: 0.88rem;">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
@media (max-width: 860px) {
    div[style*="grid-template-columns: 240px 1fr"] {
        grid-template-columns: 1fr !important;
    }
    .product-grid[style*="grid-template-columns: repeat(3, 1fr)"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
