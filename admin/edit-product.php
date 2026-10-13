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
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $featured = isset($_POST['featured']) ? 1 : 0;
        $isNewArrival = isset($_POST['is_new_arrival']) ? 1 : 0;
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
                    is_new_arrival = ?, 
                    display_order = ?, 
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
                    $isNewArrival,
                    $displayOrder,
                    $id
                ]);

                // Process Variants
                $submittedVariants = $_POST['variants'] ?? [];
                $keptVariantIds = [];

                if (is_array($submittedVariants)) {
                    $vIns = $db->prepare("INSERT INTO product_variants (product_id, variant_type, variant_value, sku, price, stock, status, display_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $vUpd = $db->prepare("UPDATE product_variants SET variant_type = ?, variant_value = ?, sku = ?, price = ?, stock = ?, status = ?, display_order = ? WHERE id = ? AND product_id = ?");

                    foreach ($submittedVariants as $idx => $v) {
                        $vType = trim($v['type'] ?? 'Option');
                        $vVal = trim($v['value'] ?? '');
                        if (empty($vVal)) continue;

                        $vSku = !empty($v['sku']) ? trim(strtoupper($v['sku'])) : null;
                        $vPrice = (isset($v['price']) && $v['price'] !== '' && is_numeric($v['price'])) ? (float)$v['price'] : null;
                        $vStock = max(0, (int)($v['stock'] ?? 0));
                        $vStatus = isset($v['status']) && $v['status'] == 1 ? 1 : 0;
                        $vOrder = (int)($v['display_order'] ?? $idx);
                        $vId = (int)($v['id'] ?? 0);

                        if ($vId > 0) {
                            $vUpd->execute([$vType, $vVal, $vSku, $vPrice, $vStock, $vStatus, $vOrder, $vId, $id]);
                            $keptVariantIds[] = $vId;
                        } else {
                            $vIns->execute([$id, $vType, $vVal, $vSku, $vPrice, $vStock, $vStatus, $vOrder]);
                            $keptVariantIds[] = (int)$db->lastInsertId();
                        }
                    }
                }

                // Delete variants removed by admin
                if (!empty($keptVariantIds)) {
                    $inClause = implode(',', array_map('intval', $keptVariantIds));
                    $db->exec("DELETE FROM product_variants WHERE product_id = {$id} AND id NOT IN ({$inClause})");
                } else {
                    $db->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$id]);
                }

                setFlash('success', "Jewellery creation '{$name}' and variants updated successfully!");
                header("Location: " . BASE_URL . "admin/products.php");
                exit;
            } catch (Exception $e) {
                error_log("Update product error: " . $e->getMessage());
                $errors[] = 'Database error updating product.';
            }
        }
    }
}

// Fetch existing variants
$varStmt = $db->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY display_order ASC, id ASC");
$varStmt->execute([$id]);
$existingVariants = $varStmt->fetchAll();
?>

<div class="admin-card" style="max-width: 950px; margin: 0 auto;">
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

            <div class="form-grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="editSku">Base SKU Code *</label>
                    <input type="text" id="editSku" name="sku" class="form-control" value="<?= e($_POST['sku'] ?? $product['sku']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editStock">Base Stock Quantity *</label>
                    <input type="number" id="editStock" name="stock" class="form-control" min="0" value="<?= e($_POST['stock'] ?? $product['stock']) ?>" required>
                    <small style="font-size: 0.75rem; color: var(--admin-muted);">Inventory for product (or fallback if no variants).</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editDisplayOrder">Display Order (Sorting Priority)</label>
                    <input type="number" id="editDisplayOrder" name="display_order" class="form-control" min="0" value="<?= (int)($_POST['display_order'] ?? ($product['display_order'] ?? 0)) ?>">
                    <small style="font-size: 0.75rem; color: var(--admin-muted);">Lower numbers appear first on listings & homepage.</small>
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
                <textarea id="editDesc" name="description" rows="4" class="form-control"><?= e($_POST['description'] ?? $product['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="editImgInput">Change Product Image (Leave empty to keep current image)</label>
                <input type="file" id="editImgInput" name="main_image" class="form-control image-upload-input" data-preview="editImgPreview" accept="image/*">
                <div class="image-preview-box" id="editImgPreview" style="margin-top: 0.5rem;">
                    <img src="<?= BASE_URL . e($product['main_image']) ?>" alt="Current Image" style="width:100%; height:100%; object-fit:cover;">
                </div>
            </div>

            <!-- Homepage Visibility Controls -->
            <div style="background: #FFFDF9; border: 1px solid #F3EBDD; border-radius: 8px; padding: 1.25rem; margin: 1.5rem 0;">
                <h4 style="color: var(--admin-primary); margin-bottom: 0.8rem; font-size: 1rem;">Homepage & Boutique Visibility</h4>
                <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="featured" value="1" <?= (!empty($product['featured'])) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
                        <span style="font-weight: 500;">Featured on Homepage (Featured Creations Section)</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_new_arrival" value="1" <?= (!empty($product['is_new_arrival'])) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
                        <span style="font-weight: 500;">New Arrival (New Arrivals Section)</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="status" value="1" <?= ($product['status'] == 1) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
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
                            Create selectable options for customers with custom prices, SKU, and inventory stock. If none added, the base product is sold without variant prompt.
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
                            <?php if (empty($existingVariants)): ?>
                                <tr id="noVariantsRow">
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--admin-muted); font-size: 0.88rem;">
                                        No variants configured yet. Click <strong>"+ Add Variant Option"</strong> above to add options like Size, Colour, or Material.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($existingVariants as $vIdx => $var): ?>
                                    <tr class="variant-row" data-index="<?= $vIdx ?>">
                                        <input type="hidden" name="variants[<?= $vIdx ?>][id]" value="<?= $var['id'] ?>">
                                        <td>
                                            <select name="variants[<?= $vIdx ?>][type]" class="form-control" style="padding: 0.35rem 0.5rem; font-size: 0.85rem;">
                                                <option value="Size" <?= ($var['variant_type'] == 'Size') ? 'selected' : '' ?>>Size</option>
                                                <option value="Colour" <?= ($var['variant_type'] == 'Colour') ? 'selected' : '' ?>>Colour</option>
                                                <option value="Material" <?= ($var['variant_type'] == 'Material') ? 'selected' : '' ?>>Material</option>
                                                <option value="Design" <?= ($var['variant_type'] == 'Design') ? 'selected' : '' ?>>Design</option>
                                                <option value="Option" <?= ($var['variant_type'] == 'Option') ? 'selected' : '' ?>>Other Option</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="variants[<?= $vIdx ?>][value]" value="<?= e($var['variant_value']) ?>" class="form-control" placeholder="e.g. 16 inch / Rose Gold" style="padding: 0.35rem 0.5rem; font-size: 0.85rem;" required>
                                        </td>
                                        <td>
                                            <input type="text" name="variants[<?= $vIdx ?>][sku]" value="<?= e($var['sku'] ?? '') ?>" class="form-control" placeholder="Optional SKU" style="padding: 0.35rem 0.5rem; font-size: 0.85rem;">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="variants[<?= $vIdx ?>][price]" value="<?= !empty($var['price']) ? e($var['price']) : '' ?>" class="form-control" placeholder="Default" style="padding: 0.35rem 0.5rem; font-size: 0.85rem;">
                                        </td>
                                        <td>
                                            <input type="number" min="0" name="variants[<?= $vIdx ?>][stock]" value="<?= (int)$var['stock'] ?>" class="form-control" style="padding: 0.35rem 0.5rem; font-size: 0.85rem;" required>
                                        </td>
                                        <td style="text-align: center;">
                                            <input type="checkbox" name="variants[<?= $vIdx ?>][status]" value="1" <?= ($var['status'] == 1) ? 'checked' : '' ?> style="width: 16px; height: 16px; accent-color: var(--admin-primary);">
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" onclick="removeVariantRow(this)" style="background: none; border: none; color: #DC2626; cursor: pointer; font-size: 1.1rem; padding: 2px;" title="Remove Option">&times;</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 0.85rem 2.5rem; font-size: 0.95rem;">
                Save Product & Variants
            </button>
        </form>
    </div>
</div>

<script>
let variantCount = <?= count($existingVariants) ?>;

function addVariantRow() {
    const noRow = document.getElementById('noVariantsRow');
    if (noRow) noRow.remove();

    const tbody = document.getElementById('variantsBody');
    const tr = document.createElement('tr');
    tr.className = 'variant-row';
    tr.dataset.index = variantCount;

    tr.innerHTML = `
        <input type="hidden" name="variants[${variantCount}][id]" value="0">
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
                    No variants configured yet. Click <strong>"+ Add Variant Option"</strong> above to add options like Size, Colour, or Material.
                </td>
            </tr>
        `;
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
