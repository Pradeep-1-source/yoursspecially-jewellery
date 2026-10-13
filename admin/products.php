<?php
/**
 * YoursSpeciallyJewellery - Admin Product Catalog Management
 */

$adminPageTitle = 'Product Inventory Management';
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

// 1. Handle Batch Display Order Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_orders'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token invalid. Please refresh the page.');
    } else {
        $displayOrders = $_POST['display_orders'] ?? [];
        if (is_array($displayOrders) && !empty($displayOrders)) {
            $updStmt = $db->prepare("UPDATE products SET display_order = ? WHERE id = ?");
            foreach ($displayOrders as $pId => $ord) {
                $updStmt->execute([(int)$ord, (int)$pId]);
            }
            setFlash('success', 'Products display order updated successfully!');
            header("Location: " . BASE_URL . "admin/products.php?" . http_build_query($_GET));
            exit;
        }
    }
}

// 2. Handle Quick Toggles (Featured, New Arrival, Status)
if (isset($_GET['toggle']) && isset($_GET['product_id'])) {
    $toggleField = $_GET['toggle'];
    $prodId = (int)$_GET['product_id'];

    if (in_array($toggleField, ['featured', 'is_new_arrival', 'status']) && $prodId > 0) {
        $currStmt = $db->prepare("SELECT {$toggleField} FROM products WHERE id = ?");
        $currStmt->execute([$prodId]);
        $currVal = $currStmt->fetchColumn();

        if ($currVal !== false) {
            $newVal = ($currVal == 1) ? 0 : 1;
            $upd = $db->prepare("UPDATE products SET {$toggleField} = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$newVal, $prodId]);
            setFlash('success', 'Product updated successfully.');
        }
    }

    $redirectParams = $_GET;
    unset($redirectParams['toggle'], $redirectParams['product_id']);
    header("Location: " . BASE_URL . "admin/products.php?" . http_build_query($redirectParams));
    exit;
}

// 3. Handle product deletion
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    try {
        $delStmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $delStmt->execute([$delId]);
        setFlash('success', 'Jewellery creation deleted successfully.');
        header("Location: " . BASE_URL . "admin/products.php");
        exit;
    } catch (Exception $e) {
        error_log("Delete product error: " . $e->getMessage());
        setFlash('error', 'Unable to delete product.');
    }
}

// 4. Filters & Queries
$search = trim($_GET['search'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$sectionFilter = trim($_GET['section_filter'] ?? '');
$stockFilter = trim($_GET['stock_filter'] ?? '');
$sortBy = trim($_GET['sort'] ?? 'order');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

if ($sectionFilter === 'featured') {
    $where[] = "p.featured = 1";
} elseif ($sectionFilter === 'new_arrival') {
    $where[] = "p.is_new_arrival = 1";
} elseif ($sectionFilter === 'draft') {
    $where[] = "p.status = 0";
}

if ($stockFilter === 'low') {
    $where[] = "p.stock <= 5";
} elseif ($stockFilter === 'out') {
    $where[] = "p.stock = 0";
}

$orderBy = "p.display_order ASC, p.id DESC";
if ($sortBy === 'newest') {
    $orderBy = "p.id DESC";
} elseif ($sortBy === 'name') {
    $orderBy = "p.name ASC";
} elseif ($sortBy === 'price_asc') {
    $orderBy = "p.price ASC";
} elseif ($sortBy === 'price_desc') {
    $orderBy = "p.price DESC";
}

try {
    // Fetch categories for filter dropdown
    $cats = $db->query("SELECT id, name FROM categories ORDER BY display_order ASC, name ASC")->fetchAll();

    // Fetch filtered products with variant count
    $whereSql = implode(" AND ", $where);
    $stmt = $db->prepare("SELECT p.*, c.name AS category_name, 
                          (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE {$whereSql} 
                          ORDER BY {$orderBy}");
    $stmt->execute($params);
    $products = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Admin products query error: " . $e->getMessage());
    $products = [];
    $cats = [];
}
?>

<!-- Filter & Search Toolbar -->
<div class="admin-card" style="margin-bottom: 1.5rem;">
    <div class="admin-card-body" style="padding: 1.25rem;">
        <form method="GET" action="<?= BASE_URL ?>admin/products.php" style="display: flex; gap: 0.85rem; align-items: center; flex-wrap: wrap;">
            <input type="text" name="search" placeholder="Search name or SKU..." value="<?= e($search) ?>" class="form-control" style="width: 200px; padding: 0.5rem 0.85rem;">

            <select name="category_id" class="form-control" style="width: 170px; padding: 0.5rem 0.85rem;">
                <option value="0">All Categories</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($categoryId == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="section_filter" class="form-control" style="width: 170px; padding: 0.5rem 0.85rem;">
                <option value="">All Showcase Types</option>
                <option value="featured" <?= ($sectionFilter === 'featured') ? 'selected' : '' ?>>★ Featured Only</option>
                <option value="new_arrival" <?= ($sectionFilter === 'new_arrival') ? 'selected' : '' ?>>✨ New Arrivals Only</option>
                <option value="draft" <?= ($sectionFilter === 'draft') ? 'selected' : '' ?>>Drafts / Inactive</option>
            </select>

            <select name="stock_filter" class="form-control" style="width: 150px; padding: 0.5rem 0.85rem;">
                <option value="">All Stock</option>
                <option value="low" <?= ($stockFilter === 'low') ? 'selected' : '' ?>>Low (&le; 5)</option>
                <option value="out" <?= ($stockFilter === 'out') ? 'selected' : '' ?>>Sold Out (0)</option>
            </select>

            <select name="sort" class="form-control" style="width: 160px; padding: 0.5rem 0.85rem;">
                <option value="order" <?= ($sortBy === 'order') ? 'selected' : '' ?>>Order Priority</option>
                <option value="newest" <?= ($sortBy === 'newest') ? 'selected' : '' ?>>Newest First</option>
                <option value="name" <?= ($sortBy === 'name') ? 'selected' : '' ?>>Name A-Z</option>
                <option value="price_asc" <?= ($sortBy === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= ($sortBy === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
            </select>

            <button type="submit" class="btn-admin btn-admin-primary">Filter</button>
            <?php if (!empty($search) || $categoryId > 0 || !empty($sectionFilter) || !empty($stockFilter) || $sortBy !== 'order'): ?>
                <a href="<?= BASE_URL ?>admin/products.php" class="btn-admin btn-admin-outline">Reset</a>
            <?php endif; ?>

            <div style="margin-left: auto;">
                <a href="<?= BASE_URL ?>admin/add-product.php" class="btn-admin btn-admin-primary">
                    + Add New Product
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table Card -->
<div class="admin-card">
    <form method="POST" action="<?= BASE_URL ?>admin/products.php?<?= http_build_query($_GET) ?>">
        <?= csrfField() ?>
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0;">Jewellery Catalog (<?= count($products) ?> items)</h3>
                <small style="color: var(--admin-muted); font-size: 0.78rem;">Display order controls position on homepage & category listings.</small>
            </div>
            <button type="submit" name="update_orders" value="1" class="btn-admin btn-admin-primary btn-admin-sm">
                💾 Save Display Order
            </button>
        </div>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 65px;">Order</th>
                        <th>Piece</th>
                        <th>Category</th>
                        <th>Price / Sale</th>
                        <th>Stock / Variants</th>
                        <th>Featured</th>
                        <th>New Arrival</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="9" style="text-align: center; padding: 3rem; color: var(--admin-muted);">No jewellery products found matching criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): 
                            $queryStr = http_build_query($_GET);
                            $toggleBase = BASE_URL . "admin/products.php?{$queryStr}&product_id=" . $p['id'];
                        ?>
                            <tr>
                                <td>
                                    <input type="number" name="display_orders[<?= $p['id'] ?>]" value="<?= (int)$p['display_order'] ?>" style="width: 55px; padding: 4px 6px; border: 1px solid var(--admin-border); border-radius: 4px; text-align: center; font-weight: 600;" min="0">
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="<?= BASE_URL . e($p['main_image'] ?: 'assets/images/prod-neck-1.jpg') ?>" alt="<?= e($p['name']) ?>" style="width: 44px; height: 44px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border); flex-shrink: 0;">
                                        <div>
                                            <strong style="color: var(--admin-primary); display: block; font-size: 0.92rem;"><?= e($p['name']) ?></strong>
                                            <div style="font-size: 0.75rem; color: var(--admin-muted);">SKU: <?= e($p['sku']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($p['category_name'] ?? 'Uncategorized') ?></td>
                                <td>
                                    <div><strong><?= formatPrice($p['sale_price'] ?: $p['price']) ?></strong></div>
                                    <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                                        <div style="font-size: 0.75rem; text-decoration: line-through; color: var(--admin-muted);"><?= formatPrice($p['price']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['stock'] <= 0): ?>
                                        <span class="badge-status cancelled">0 (Sold Out)</span>
                                    <?php elseif ($p['stock'] <= 5): ?>
                                        <span class="badge-status pending"><?= $p['stock'] ?> Left</span>
                                    <?php else: ?>
                                        <span class="badge-status delivered"><?= $p['stock'] ?> In Stock</span>
                                    <?php endif; ?>

                                    <?php if ($p['variant_count'] > 0): ?>
                                        <div style="margin-top: 3px;">
                                            <span style="font-size: 0.72rem; color: var(--admin-secondary); font-weight: 600; background: #FFF4F0; padding: 2px 6px; border-radius: 3px;">
                                                <?= $p['variant_count'] ?> variant<?= $p['variant_count'] > 1 ? 's' : '' ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= $toggleBase ?>&toggle=featured" title="Click to toggle featured status">
                                        <?= !empty($p['featured']) 
                                            ? '<span style="color:#B45309; background:#FEF3C7; padding:3px 8px; border-radius:4px; font-weight:600; font-size:0.75rem;">★ Yes</span>' 
                                            : '<span style="color:var(--admin-muted); font-size:0.75rem;">No</span>' ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?= $toggleBase ?>&toggle=is_new_arrival" title="Click to toggle new arrival status">
                                        <?= !empty($p['is_new_arrival']) 
                                            ? '<span style="color:#047857; background:#D1FAE5; padding:3px 8px; border-radius:4px; font-weight:600; font-size:0.75rem;">✨ Yes</span>' 
                                            : '<span style="color:var(--admin-muted); font-size:0.75rem;">No</span>' ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?= $toggleBase ?>&toggle=status" title="Click to toggle active status">
                                        <span class="badge-status <?= $p['status'] == 1 ? 'delivered' : 'cancelled' ?>" style="cursor: pointer;">
                                            <?= $p['status'] == 1 ? 'Active' : 'Draft' ?>
                                        </span>
                                    </a>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="<?= BASE_URL ?>product.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="btn-admin btn-admin-outline btn-admin-sm" title="Preview on live site">
                                            View
                                        </a>
                                        <a href="<?= BASE_URL ?>admin/edit-product.php?id=<?= $p['id'] ?>" class="btn-admin btn-admin-primary btn-admin-sm">
                                            Edit
                                        </a>
                                        <a href="<?= BASE_URL ?>admin/products.php?delete_id=<?= $p['id'] ?>" class="btn-admin btn-admin-danger btn-admin-sm confirm-delete" data-confirm="Are you sure you want to permanently delete '<?= e($p['name']) ?>'?">
                                            Del
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
