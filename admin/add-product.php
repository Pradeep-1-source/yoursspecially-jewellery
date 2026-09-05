<?php
/**
 * YoursSpeciallyJewellery - Admin Add Product
 */

$adminPageTitle = 'Add New Jewellery Creation';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$db = getDBConnection();

// Fetch categories
$cats = $db->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC")->fetchAll();

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

        // Check unique SKU
        $skuCheck = $db->prepare("SELECT id FROM products WHERE sku = ? LIMIT 1");
        $skuCheck->execute([$sku]);
        if ($skuCheck->fetch()) {
            $errors[] = 'SKU already exists. Please choose a unique SKU.';
        }

        // Slug generation
        $slug = generateSlug($name);
        $slugCheck = $db->prepare("SELECT id FROM products WHERE slug = ? LIMIT 1");
        $slugCheck->execute([$slug]);
        if ($slugCheck->fetch()) {
            $slug .= '-' . bin2hex(random_bytes(2));
        }

        // Handle Main Image Upload
        $mainImagePath = 'assets/images/prod-neck-1.jpg'; // default fallback
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
                $stmt = $db->prepare("INSERT INTO products 
                    (category_id, name, slug, description, price, sale_price, stock, sku, main_image, status, featured, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $categoryId,
                    $name,
                    $slug,
                    $description,
                    $price,
                    $salePrice,
                    $stock,
                    $sku,
                    $mainImagePath,
                    $status,
                    $featured
                ]);

                setFlash('success', "Jewellery creation '{$name}' added to boutique inventory!");
                header("Location: " . BASE_URL . "admin/products.php");
                exit;
            } catch (Exception $e) {
                error_log("Add product error: " . $e->getMessage());
                $errors[] = 'Database error creating product.';
            }
        }
    }
}
?>

<div class="admin-card" style="max-width: 900px; margin: 0 auto;">
    <div class="admin-card-header">
        <h3>Add New Jewellery Product</h3>
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

        <form method="POST" action="<?= BASE_URL ?>admin/add-product.php" enctype="multipart/form-data">
            <?= csrfField() ?>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="prodName">Product Name *</label>
                    <input type="text" id="prodName" name="name" class="form-control auto-slug-source" placeholder="e.g. Celestial Diamond Pendant" value="<?= e($_POST['name'] ?? '') ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="prodCategory">Category *</label>
                    <select id="prodCategory" name="category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (($_POST['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="prodSku">SKU Code (Stock Keeping Unit) *</label>
                    <input type="text" id="prodSku" name="sku" class="form-control" placeholder="e.g. YSJ-NECK-105" value="<?= e($_POST['sku'] ?? ('YSJ-' . strtoupper(bin2hex(random_bytes(3))))) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="prodStock">Initial Stock Quantity *</label>
                    <input type="number" id="prodStock" name="stock" class="form-control" min="0" value="<?= e($_POST['stock'] ?? '10') ?>" required>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="prodPrice">Regular Price (INR ₹) *</label>
                    <input type="number" step="0.01" id="prodPrice" name="price" class="form-control" placeholder="2999.00" value="<?= e($_POST['price'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="prodSalePrice">Sale / Promotional Price (Optional ₹)</label>
                    <input type="number" step="0.01" id="prodSalePrice" name="sale_price" class="form-control" placeholder="2499.00" value="<?= e($_POST['sale_price'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="prodDesc">Product Description & Craftsmanship Details</label>
                <textarea id="prodDesc" name="description" rows="5" class="form-control" placeholder="Describe the materials, gemstone cuts, plating finish, closure mechanism..."><?= e($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="mainImgInput">Main Product Image (JPG, PNG, WEBP)</label>
                <input type="file" id="mainImgInput" name="main_image" class="form-control image-upload-input" data-preview="mainImgPreview" accept="image/*">
                <div class="image-preview-box" id="mainImgPreview">
                    <span style="font-size: 0.75rem; color: var(--admin-muted);">Preview</span>
                </div>
            </div>

            <div style="display: flex; gap: 2rem; margin: 1.5rem 0; padding: 1rem; background: #FAF7F8; border-radius: 6px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="featured" value="1" <?= (!empty($_POST['featured'])) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
                    <span style="font-weight: 500;">Feature on Homepage</span>
                </label>

                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="status" value="1" <?= (!isset($_POST['status']) || !empty($_POST['status'])) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
                    <span style="font-weight: 500;">Active in Boutique (Visible to Customers)</span>
                </label>
            </div>

            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 0.85rem 2rem; font-size: 0.95rem;">
                Publish Jewellery Creation
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
