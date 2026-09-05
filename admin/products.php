<?php
/**
 * YoursSpeciallyJewellery - Admin Product Catalog Management
 */

$adminPageTitle = 'Product Inventory Management';
require_once __DIR__ . '/includes/header.php';

$search = trim($_GET['search'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$stockFilter = trim($_GET['stock_filter'] ?? '');

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

if ($stockFilter === 'low') {
    $where[] = "p.stock <= 5";
} elseif ($stockFilter === 'out') {
    $where[] = "p.stock = 0";
}

// Handle product deletion
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

try {
    $db = getDBConnection();

    // Fetch categories for filter dropdown
    $cats = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

    // Fetch filtered products
    $whereSql = implode(" AND ", $where);
    $stmt = $db->prepare("SELECT p.*, c.name AS category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE {$whereSql} 
                          ORDER BY p.id DESC");
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
        <form method="GET" action="<?= BASE_URL ?>admin/products.php" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <input type="text" name="search" placeholder="Search by name or SKU..." value="<?= e($search) ?>" class="form-control" style="width: 240px; padding: 0.5rem 0.85rem;">

            <select name="category_id" class="form-control" style="width: 180px; padding: 0.5rem 0.85rem;">
                <option value="0">All Categories</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($categoryId == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="stock_filter" class="form-control" style="width: 160px; padding: 0.5rem 0.85rem;">
                <option value="">All Stock Levels</option>
                <option value="low" <?= ($stockFilter == 'low') ? 'selected' : '' ?>>Low Stock (&le; 5)</option>
                <option value="out" <?= ($stockFilter == 'out') ? 'selected' : '' ?>>Sold Out (0)</option>
            </select>

            <button type="submit" class="btn-admin btn-admin-primary">Filter</button>
            <?php if (!empty($search) || $categoryId > 0 || !empty($stockFilter)): ?>
                <a href="<?= BASE_URL ?>admin/products.php" class="btn-admin btn-admin-outline">Clear</a>
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
    <div class="admin-card-header">
        <h3>All Products (<?= count($products) ?>)</h3>
    </div>
    <div class="admin-table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Thumbnail</th>
                    <th>Product Name & SKU</th>
                    <th>Category</th>
                    <th>Price / Sale</th>
                    <th>Stock</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 3rem; color: var(--admin-muted);">No jewellery products found.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td style="width: 60px;">
                                <img src="<?= BASE_URL . e($p['main_image'] ?: 'assets/images/prod-neck-1.jpg') ?>" alt="<?= e($p['name']) ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                            </td>
                            <td>
                                <strong style="color: var(--admin-primary);"><?= e($p['name']) ?></strong>
                                <div style="font-size: 0.75rem; color: var(--admin-muted);">SKU: <?= e($p['sku']) ?></div>
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
                                    <span class="badge-status pending"><?= $p['stock'] ?> (Low)</span>
                                <?php else: ?>
                                    <span class="badge-status delivered"><?= $p['stock'] ?> In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= !empty($p['featured']) ? '<span style="color:var(--admin-secondary); font-weight:bold;">★ Yes</span>' : '<span style="color:var(--admin-muted);">No</span>' ?>
                            </td>
                            <td>
                                <span class="badge-status <?= $p['status'] == 1 ? 'delivered' : 'cancelled' ?>">
                                    <?= $p['status'] == 1 ? 'Active' : 'Draft' ?>
                                </span>
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
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
