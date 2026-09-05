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
    <div style="display: grid; grid-template-columns: 240px 1fr; gap: 2.5rem; align-items: start;">
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
                <div class="product-grid" style="grid-template-columns: repeat(3, 1fr);">
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
                                    <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($prod['slug']) ?>" class="floating-action-btn" title="View Details">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <a href="https://wa.me/91<?= preg_replace('/\D/', '', getSetting('whatsapp_number', '9940474469')) ?>?text=<?= urlencode("Hi YoursSpecially, I would like to enquire about " . $prod['name'] . " (" . $prod['sku'] . ").") ?>" target="_blank" class="floating-action-btn" title="Enquire on WhatsApp">
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
                                    <button class="btn-add-cart ajax-add-to-cart" data-product-id="<?= $prod['id'] ?>">Add to Bag</button>
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
