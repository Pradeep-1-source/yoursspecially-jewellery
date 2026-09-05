<?php
/**
 * YoursSpeciallyJewellery - Admin Edit Product
 */

$adminPageTitle = 'Edit Jewellery Creation';
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: " . BASE_URL . "admin/products.php");
    exit;
}

$db = getDBConnection();

// Fetch product
$stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    header("Location: " . BASE_URL . "admin/products.php");
    exit;
}

// Fetch categories
$cats = $db->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh the form.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $salePrice = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
        $sku = trim(strtoupper($_POST['sku'] ?? ''));
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $featured = isset($_POST['featured']) ? 1 : 0;
        $status = isset($_POST['status']) ? 1 : 0;

        if (empty($name)) $errors[] = 'Product name is required.';
        if ($categoryId <= 0) $errors[] = 'Please select a valid category.';
        if ($price <= 0) $errors[] = 'Regular price must be greater than 0.';
        if (empty($sku)) $errors[] = 'SKU is required.';

        // Check unique SKU (excluding self)
        $skuCheck = $db->prepare("SELECT id FROM products WHERE sku = ? AND id != ? LIMIT 1");
        $skuCheck->execute([$sku, $id]);
        if ($skuCheck->fetch()) {
            $errors[] = 'SKU already exists on another product.';
        }

        // Handle Image Upload if changed
        $mainImagePath = $product['main_image'];
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = handleSecureUpload($_FILES['main_image'], 'products');
            if ($uploaded) {
                $mainImagePath = $uploaded;
            } else {
                $errors[] = 'Main image upload failed. Allowed types: JPG, PNG, WEBP (Max 5MB).';
            }
        }

        if (empty($errors)) {
            try {
                $upd = $db->prepare("UPDATE products SET 
                    category_id = ?, 
                    name = ?, 
                    description = ?, 
                    price = ?, 
                    sale_price = ?, 
                    stock = ?, 
                    sku = ?, 
                    main_image = ?, 
                    status = ?, 
                    featured = ?, 
                    updated_at = NOW() 
                    WHERE id = ?");
                $upd->execute([
                    $categoryId,
                    $name,
                    $description,
                    $price,
                    $salePrice,
                    $stock,
                    $sku,
                    $mainImagePath,
                    $status,
                    $featured,
                    $id
                ]);

                setFlash('success', "Jewellery creation '{$name}' updated successfully!");
                header("Location: " . BASE_URL . "admin/products.php");
                exit;
            } catch (Exception $e) {
                error_log("Update product error: " . $e->getMessage());
                $errors[] = 'Database error updating product.';
            }
        }
    }
}
?>

<div class="admin-card" style="max-width: 900px; margin: 0 auto;">
    <div class="admin-card-header">
        <h3>Edit: <?= e($product['name']) ?></h3>
        <a href="<?= BASE_URL ?>admin/products.php" class="btn-admin btn-admin-outline btn-admin-sm">&larr; Back to Products</a>
    </div>
    <div class="admin-card-body">
        <?php if (!empty($errors)): ?>
            <div style="background: #FDF2F2; border: 1px solid #F8B4B4; color: #9B1C1C; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>admin/edit-product.php?id=<?= $id ?>" enctype="multipart/form-data">
            <?= csrfField() ?>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="editName">Product Name *</label>
                    <input type="text" id="editName" name="name" class="form-control" value="<?= e($_POST['name'] ?? $product['name']) ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editCategory">Category *</label>
                    <select id="editCategory" name="category_id" class="form-control" required>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (($product['category_id'] == $c['id'])) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="editSku">SKU Code *</label>
                    <input type="text" id="editSku" name="sku" class="form-control" value="<?= e($_POST['sku'] ?? $product['sku']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editStock">Inventory Stock Quantity *</label>
                    <input type="number" id="editStock" name="stock" class="form-control" min="0" value="<?= e($_POST['stock'] ?? $product['stock']) ?>" required>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="editPrice">Regular Price (INR ₹) *</label>
                    <input type="number" step="0.01" id="editPrice" name="price" class="form-control" value="<?= e($_POST['price'] ?? $product['price']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editSalePrice">Sale / Promotional Price (Optional ₹)</label>
                    <input type="number" step="0.01" id="editSalePrice" name="sale_price" class="form-control" value="<?= e($_POST['sale_price'] ?? $product['sale_price']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="editDesc">Product Description & Details</label>
                <textarea id="editDesc" name="description" rows="5" class="form-control"><?= e($_POST['description'] ?? $product['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="editImgInput">Change Product Image (Leave empty to keep current image)</label>
                <input type="file" id="editImgInput" name="main_image" class="form-control image-upload-input" data-preview="editImgPreview" accept="image/*">
                <div class="image-preview-box" id="editImgPreview" style="margin-top: 0.5rem;">
                    <img src="<?= BASE_URL . e($product['main_image']) ?>" alt="Current Image" style="width:100%; height:100%; object-fit:cover;">
                </div>
            </div>

            <div style="display: flex; gap: 2rem; margin: 1.5rem 0; padding: 1rem; background: #FAF7F8; border-radius: 6px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="featured" value="1" <?= (!empty($product['featured'])) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
                    <span style="font-weight: 500;">Featured Piece</span>
                </label>

                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="status" value="1" <?= ($product['status'] == 1) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
                    <span style="font-weight: 500;">Active in Boutique</span>
                </label>
            </div>

            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 0.85rem 2rem; font-size: 0.95rem;">
                Save Changes
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
