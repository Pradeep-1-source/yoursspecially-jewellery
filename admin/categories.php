<?php
/**
 * YoursSpeciallyJewellery - Admin Category Management
 */

$adminPageTitle = 'Category Management';
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();
$errors = [];
$editCategory = null;

// Handle Edit Fetch
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    try {
        $del = $db->prepare("DELETE FROM categories WHERE id = ?");
        $del->execute([$delId]);
        setFlash('success', 'Category removed successfully.');
        header("Location: " . BASE_URL . "admin/categories.php");
        exit;
    } catch (Exception $e) {
        error_log("Delete category error: " . $e->getMessage());
        setFlash('error', 'Cannot delete category while it contains products.');
    }
}

// Handle Batch Order Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_orders'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token invalid. Please refresh the page.');
    } else {
        $displayOrders = $_POST['display_orders'] ?? [];
        if (is_array($displayOrders) && !empty($displayOrders)) {
            $updStmt = $db->prepare("UPDATE categories SET display_order = ? WHERE id = ?");
            foreach ($displayOrders as $cId => $ord) {
                $updStmt->execute([(int)$ord, (int)$cId]);
            }
            setFlash('success', 'Category display order updated successfully!');
            header("Location: " . BASE_URL . "admin/categories.php");
            exit;
        }
    }
}

// Handle Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_orders'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh the page.';
    } else {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $status = isset($_POST['status']) ? 1 : 0;

        if (empty($name)) {
            $errors[] = 'Category name is required.';
        }

        $slug = generateSlug($name);

        // Handle Image Upload
        $imagePath = $editCategory['image'] ?? 'assets/images/cat-necklaces.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = handleSecureUpload($_FILES['image'], 'categories');
            if ($uploaded) {
                $imagePath = $uploaded;
            } else {
                $errors[] = 'Category image upload failed. Allowed types: JPG, PNG, WEBP.';
            }
        }

        if (empty($errors)) {
            try {
                if ($categoryId > 0) {
                    // Update
                    $upd = $db->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, image = ?, status = ?, display_order = ? WHERE id = ?");
                    $upd->execute([$name, $slug, $description, $imagePath, $status, $displayOrder, $categoryId]);
                    setFlash('success', "Category '{$name}' updated successfully.");
                } else {
                    // Insert
                    $ins = $db->prepare("INSERT INTO categories (name, slug, description, image, status, display_order, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $ins->execute([$name, $slug, $description, $imagePath, $status, $displayOrder]);
                    setFlash('success', "Category '{$name}' created successfully.");
                }
                header("Location: " . BASE_URL . "admin/categories.php");
                exit;
            } catch (Exception $e) {
                error_log("Category save error: " . $e->getMessage());
                $errors[] = 'Database error saving category.';
            }
        }
    }
}

// Fetch all categories with product counts ordered by display_order
$categories = $db->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c ORDER BY c.display_order ASC, c.id ASC")->fetchAll();
?>

<div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 2rem; align-items: start;">
    <!-- Add / Edit Category Form -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><?= $editCategory ? 'Edit Category' : 'Create New Category' ?></h3>
            <?php if ($editCategory): ?>
                <a href="<?= BASE_URL ?>admin/categories.php" class="btn-admin btn-admin-outline btn-admin-sm">Cancel Edit</a>
            <?php endif; ?>
        </div>
        <div class="admin-card-body">
            <?php if (!empty($errors)): ?>
                <div style="background: #FDF2F2; border: 1px solid #F8B4B4; color: #9B1C1C; padding: 0.85rem; border-radius: 4px; font-size: 0.85rem; margin-bottom: 1.25rem;">
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>admin/categories.php" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="category_id" value="<?= $editCategory ? $editCategory['id'] : 0 ?>">

                <div class="form-group">
                    <label class="form-label" for="catName">Category Name *</label>
                    <input type="text" id="catName" name="name" class="form-control auto-slug-source" placeholder="e.g. Chokers & Necklaces" value="<?= e($_POST['name'] ?? ($editCategory['name'] ?? '')) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="catDesc">Description</label>
                    <textarea id="catDesc" name="description" rows="3" class="form-control" placeholder="Short description of this collection..."><?= e($_POST['description'] ?? ($editCategory['description'] ?? '')) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="catOrder">Display Order (Sorting Priority)</label>
                    <input type="number" id="catOrder" name="display_order" class="form-control" placeholder="e.g. 1 (lower numbers appear first)" value="<?= (int)($_POST['display_order'] ?? ($editCategory['display_order'] ?? 0)) ?>" min="0">
                    <small style="color: var(--admin-muted); font-size: 0.78rem;">Categories with smaller numbers appear first on the homepage and navigation.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="catImg">Category Cover Image</label>
                    <input type="file" id="catImg" name="image" class="form-control image-upload-input" data-preview="catPreview" accept="image/*">
                    <div class="image-preview-box" id="catPreview">
                        <?php if (!empty($editCategory['image'])): ?>
                            <img src="<?= BASE_URL . e($editCategory['image']) ?>" alt="Category Preview" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <span style="font-size:0.75rem; color:var(--admin-muted);">Preview</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin: 1.25rem 0;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="status" value="1" <?= (!isset($editCategory) || $editCategory['status'] == 1) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
                        <span style="font-weight: 500;">Active in Boutique Navigation</span>
                    </label>
                </div>

                <button type="submit" class="btn-admin btn-admin-primary btn-block">
                    <?= $editCategory ? 'Save Category Changes' : 'Create Category' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Category Listing Table -->
    <div class="admin-card">
        <form method="POST" action="<?= BASE_URL ?>admin/categories.php">
            <?= csrfField() ?>
            <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0;">Categories (<?= count($categories) ?>)</h3>
                    <small style="color: var(--admin-muted); font-size: 0.78rem;">Sorted by display order. Edit numbers below and save.</small>
                </div>
                <button type="submit" name="update_orders" value="1" class="btn-admin btn-admin-primary btn-admin-sm">
                    💾 Save Display Order
                </button>
            </div>
            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Order</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <input type="number" name="display_orders[<?= $cat['id'] ?>]" value="<?= (int)$cat['display_order'] ?>" style="width: 58px; padding: 4px 6px; border: 1px solid var(--admin-border); border-radius: 4px; text-align: center; font-weight: 600;" min="0">
                                </td>
                                <td style="width: 50px;">
                                    <img src="<?= BASE_URL . e($cat['image'] ?: 'assets/images/cat-necklaces.jpg') ?>" alt="<?= e($cat['name']) ?>" style="width: 44px; height: 44px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                                </td>
                                <td><strong style="color: var(--admin-primary);"><?= e($cat['name']) ?></strong></td>
                                <td style="font-size: 0.8rem; color: var(--admin-muted);"><code><?= e($cat['slug']) ?></code></td>
                                <td><span style="font-weight: 600;"><?= $cat['product_count'] ?></span></td>
                                <td>
                                    <span class="badge-status <?= $cat['status'] == 1 ? 'delivered' : 'cancelled' ?>">
                                        <?= $cat['status'] == 1 ? 'Active' : 'Disabled' ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="<?= BASE_URL ?>admin/categories.php?edit_id=<?= $cat['id'] ?>" class="btn-admin btn-admin-outline btn-admin-sm">Edit</a>
                                        <a href="<?= BASE_URL ?>admin/categories.php?delete_id=<?= $cat['id'] ?>" class="btn-admin btn-admin-danger btn-admin-sm confirm-delete" data-confirm="Delete category '<?= e($cat['name']) ?>'?">Del</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<style>
@media (max-width: 992px) {
    div[style*="grid-template-columns: 1fr 1.6fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
