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
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $featured = isset($_POST['featured']) ? 1 : 0;
        $isNewArrival = isset($_POST['is_new_arrival']) ? 1 : 0;
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
                    (category_id, name, slug, description, price, sale_price, stock, sku, main_image, status, featured, is_new_arrival, display_order, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
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
                    $featured,
                    $isNewArrival,
                    $displayOrder
                ]);

                $newProductId = (int)$db->lastInsertId();

                // Process Variants if added
                $submittedVariants = $_POST['variants'] ?? [];
                if (is_array($submittedVariants) && !empty($submittedVariants)) {
                    $vIns = $db->prepare("INSERT INTO product_variants (product_id, variant_type, variant_value, sku, price, stock, status, display_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    foreach ($submittedVariants as $idx => $v) {
                        $vType = trim($v['type'] ?? 'Option');
                        $vVal = trim($v['value'] ?? '');
                        if (empty($vVal)) continue;

                        $vSku = !empty($v['sku']) ? trim(strtoupper($v['sku'])) : null;
                        $vPrice = (isset($v['price']) && $v['price'] !== '' && is_numeric($v['price'])) ? (float)$v['price'] : null;
                        $vStock = max(0, (int)($v['stock'] ?? 0));
                        $vStatus = isset($v['status']) && $v['status'] == 1 ? 1 : 0;
                        $vOrder = (int)($v['display_order'] ?? $idx);

                        $vIns->execute([$newProductId, $vType, $vVal, $vSku, $vPrice, $vStock, $vStatus, $vOrder]);
                    }
                }

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

<div class="admin-card" style="max-width: 950px; margin: 0 auto;">
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

            <div class="form-grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="prodSku">Base SKU Code *</label>
                    <input type="text" id="prodSku" name="sku" class="form-control" placeholder="e.g. YSJ-NECK-105" value="<?= e($_POST['sku'] ?? ('YSJ-' . strtoupper(bin2hex(random_bytes(3))))) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="prodStock">Base Stock Quantity *</label>
                    <input type="number" id="prodStock" name="stock" class="form-control" min="0" value="<?= e($_POST['stock'] ?? '10') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="prodOrder">Display Order (Priority)</label>
                    <input type="number" id="prodOrder" name="display_order" class="form-control" min="0" value="<?= (int)($_POST['display_order'] ?? 0) ?>" placeholder="0">
                    <small style="font-size: 0.75rem; color: var(--admin-muted);">Lower numbers appear first.</small>
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
                <textarea id="prodDesc" name="description" rows="4" class="form-control" placeholder="Describe the materials, gemstone cuts, plating finish, closure mechanism..."><?= e($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="mainImgInput">Main Product Image (JPG, PNG, WEBP)</label>
                <input type="file" id="mainImgInput" name="main_image" class="form-control image-upload-input" data-preview="mainImgPreview" accept="image/*">
                <div class="image-preview-box" id="mainImgPreview">
                    <span style="font-size: 0.75rem; color: var(--admin-muted);">Preview</span>
                </div>
            </div>

            <!-- Homepage Visibility Controls -->
            <div style="background: #FFFDF9; border: 1px solid #F3EBDD; border-radius: 8px; padding: 1.25rem; margin: 1.5rem 0;">
                <h4 style="color: var(--admin-primary); margin-bottom: 0.8rem; font-size: 1rem;">Homepage & Boutique Visibility</h4>
                <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="featured" value="1" <?= (!empty($_POST['featured'])) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
                        <span style="font-weight: 500;">Featured on Homepage (Featured Creations Section)</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_new_arrival" value="1" <?= (!isset($_POST['is_new_arrival']) || !empty($_POST['is_new_arrival'])) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
                        <span style="font-weight: 500;">New Arrival (New Arrivals Section)</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="status" value="1" <?= (!isset($_POST['status']) || !empty($_POST['status'])) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
                        <span style="font-weight: 500;">Active / Published in Boutique</span>
                    </label>
                </div>
            </div>

            <!-- Product Variants Management Section -->
            <div style="background: #FAFAFC; border: 1px solid var(--admin-border); border-radius: 8px; padding: 1.5rem; margin: 2rem 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div>
                        <h4 style="color: var(--admin-primary); margin: 0; font-size: 1.1rem;">Product Variants (Size, Colour, Material, etc.)</h4>
                        <p style="margin: 0.3rem 0 0; font-size: 0.82rem; color: var(--admin-muted);">
                            Create selectable options for customers (e.g. Ring Size, Metal Finish) with custom prices and stock.
                        </p>
                    </div>
                    <button type="button" class="btn-admin btn-admin-outline btn-admin-sm" onclick="addVariantRow()">
                        + Add Variant Option
                    </button>
                </div>

                <div class="admin-table-responsive" style="background: #fff; border-radius: 6px; border: 1px solid var(--admin-border);">
                    <table class="admin-table" id="variantsTable" style="margin: 0;">
                        <thead>
                            <tr style="background: #F3EFF2;">
                                <th style="width: 130px;">Option Type</th>
                                <th style="width: 160px;">Option Value *</th>
                                <th style="width: 140px;">Variant SKU</th>
                                <th style="width: 120px;">Price (₹)</th>
                                <th style="width: 90px;">Stock *</th>
                                <th style="width: 70px;">Active</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="variantsBody">
                            <tr id="noVariantsRow">
                                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--admin-muted); font-size: 0.88rem;">
                                    No variants added yet. Click <strong>"+ Add Variant Option"</strong> above to configure options for this piece.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 0.85rem 2.5rem; font-size: 0.95rem;">
                Publish Jewellery Creation
            </button>
        </form>
    </div>
</div>

<script>
let variantCount = 0;

function addVariantRow() {
    const noRow = document.getElementById('noVariantsRow');
    if (noRow) noRow.remove();

    const tbody = document.getElementById('variantsBody');
    const tr = document.createElement('tr');
    tr.className = 'variant-row';
    tr.dataset.index = variantCount;

    tr.innerHTML = `
        <td>
            <select name="variants[${variantCount}][type]" class="form-control" style="padding: 0.35rem 0.5rem; font-size: 0.85rem;">
                <option value="Size">Size</option>
                <option value="Colour">Colour</option>
                <option value="Material">Material</option>
                <option value="Design">Design</option>
                <option value="Option">Other Option</option>
            </select>
        </td>
        <td>
            <input type="text" name="variants[${variantCount}][value]" class="form-control" placeholder="e.g. 16 inch / Rose Gold" style="padding: 0.35rem 0.5rem; font-size: 0.85rem;" required>
        </td>
        <td>
            <input type="text" name="variants[${variantCount}][sku]" class="form-control" placeholder="Optional SKU" style="padding: 0.35rem 0.5rem; font-size: 0.85rem;">
        </td>
        <td>
            <input type="number" step="0.01" name="variants[${variantCount}][price]" class="form-control" placeholder="Default" style="padding: 0.35rem 0.5rem; font-size: 0.85rem;">
        </td>
        <td>
            <input type="number" min="0" name="variants[${variantCount}][stock]" value="10" class="form-control" style="padding: 0.35rem 0.5rem; font-size: 0.85rem;" required>
        </td>
        <td style="text-align: center;">
            <input type="checkbox" name="variants[${variantCount}][status]" value="1" checked style="width: 16px; height: 16px; accent-color: var(--admin-primary);">
        </td>
        <td style="text-align: center;">
            <button type="button" onclick="removeVariantRow(this)" style="background: none; border: none; color: #DC2626; cursor: pointer; font-size: 1.1rem; padding: 2px;" title="Remove Option">&times;</button>
        </td>
    `;
    tbody.appendChild(tr);
    variantCount++;
}

function removeVariantRow(btn) {
    const tr = btn.closest('tr');
    tr.remove();
    const tbody = document.getElementById('variantsBody');
    if (tbody.querySelectorAll('.variant-row').length === 0) {
        tbody.innerHTML = `
            <tr id="noVariantsRow">
                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--admin-muted); font-size: 0.88rem;">
                    No variants added yet. Click <strong>"+ Add Variant Option"</strong> above to configure options for this piece.
                </td>
            </tr>
        `;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
