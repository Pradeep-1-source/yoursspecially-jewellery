<?php
/**
 * YoursSpeciallyJewellery - Dynamic Hero Offer Banner System
 * Complete Admin Management for Homepage Hero Banners
 */

$adminPageTitle = 'Hero Promotional Banners';
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();
$errors = [];
$editBanner = null;

// Handle Edit Fetch
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $stmt = $db->prepare("SELECT * FROM banners WHERE id = ? LIMIT 1");
    $stmt->execute([$editId]);
    $editBanner = $stmt->fetch();
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    try {
        $del = $db->prepare("DELETE FROM banners WHERE id = ?");
        $del->execute([$delId]);
        setFlash('success', 'Hero promotional banner removed.');
        header("Location: " . BASE_URL . "admin/banners.php");
        exit;
    } catch (Exception $e) {
        error_log("Delete banner error: " . $e->getMessage());
        setFlash('error', 'Unable to delete banner.');
    }
}

// Handle Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please reload the form.';
    } else {
        $bannerId = (int)($_POST['banner_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $buttonText = trim($_POST['button_text'] ?? 'Shop Now');
        $buttonLink = trim($_POST['button_link'] ?? 'products.php');
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $sortOrder = (int)($_POST['sort_order'] ?? 1);
        $status = isset($_POST['status']) ? 1 : 0;

        if (empty($title)) {
            $errors[] = 'Banner headline / title is required.';
        }

        // Handle Banner Image Upload
        $imagePath = $editBanner['image'] ?? 'assets/images/banner-hero-1.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = handleSecureUpload($_FILES['image'], 'banners');
            if ($uploaded) {
                $imagePath = $uploaded;
            } else {
                $errors[] = 'Banner image upload failed. Permitted formats: JPG, PNG, WEBP (Max 5MB).';
            }
        }

        if (empty($errors)) {
            try {
                if ($bannerId > 0) {
                    $upd = $db->prepare("UPDATE banners SET 
                        title = ?, 
                        subtitle = ?, 
                        image = ?, 
                        button_text = ?, 
                        button_link = ?, 
                        status = ?, 
                        start_date = ?, 
                        end_date = ?, 
                        sort_order = ? 
                        WHERE id = ?");
                    $upd->execute([$title, $subtitle, $imagePath, $buttonText, $buttonLink, $status, $startDate, $endDate, $sortOrder, $bannerId]);
                    setFlash('success', "Hero banner '{$title}' updated successfully!");
                } else {
                    $ins = $db->prepare("INSERT INTO banners 
                        (title, subtitle, image, button_text, button_link, status, start_date, end_date, sort_order, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $ins->execute([$title, $subtitle, $imagePath, $buttonText, $buttonLink, $status, $startDate, $endDate, $sortOrder]);
                    setFlash('success', "New promotional hero banner '{$title}' published!");
                }
                header("Location: " . BASE_URL . "admin/banners.php");
                exit;
            } catch (Exception $e) {
                error_log("Save banner error: " . $e->getMessage());
                $errors[] = 'Database error saving banner.';
            }
        }
    }
}

// Fetch all banners
$banners = $db->query("SELECT * FROM banners ORDER BY sort_order ASC, id DESC")->fetchAll();
$today = date('Y-m-d');
?>

<div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 2rem; align-items: start;">
    <!-- Add/Edit Banner Form -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><?= $editBanner ? 'Edit Hero Banner' : 'Create Hero Promotional Banner' ?></h3>
            <?php if ($editBanner): ?>
                <a href="<?= BASE_URL ?>admin/banners.php" class="btn-admin btn-admin-outline btn-admin-sm">Cancel</a>
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

            <form method="POST" action="<?= BASE_URL ?>admin/banners.php" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="banner_id" value="<?= $editBanner ? $editBanner['id'] : 0 ?>">

                <div class="form-group">
                    <label class="form-label" for="bannerTitle">Banner Headline / Offer Title *</label>
                    <input type="text" id="bannerTitle" name="title" class="form-control" placeholder="e.g. Festive Jewellery Collection" value="<?= e($_POST['title'] ?? ($editBanner['title'] ?? '')) ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="bannerSubtitle">Subtitle / Offer Description</label>
                    <textarea id="bannerSubtitle" name="subtitle" rows="2" class="form-control" placeholder="e.g. Up to 30% OFF on all handcrafted rose-gold and bridal chokers."><?= e($_POST['subtitle'] ?? ($editBanner['subtitle'] ?? '')) ?></textarea>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="bannerBtnText">Button Label</label>
                        <input type="text" id="bannerBtnText" name="button_text" class="form-control" placeholder="Shop Now" value="<?= e($_POST['button_text'] ?? ($editBanner['button_text'] ?? 'Shop Now')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bannerBtnLink">Button Link (URL)</label>
                        <input type="text" id="bannerBtnLink" name="button_link" class="form-control" placeholder="products.php?category=necklaces" value="<?= e($_POST['button_link'] ?? ($editBanner['button_link'] ?? 'products.php')) ?>">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="bannerStart">Start Schedule Date</label>
                        <input type="date" id="bannerStart" name="start_date" class="form-control" value="<?= e($_POST['start_date'] ?? ($editBanner['start_date'] ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bannerEnd">Expiration Date</label>
                        <input type="date" id="bannerEnd" name="end_date" class="form-control" value="<?= e($_POST['end_date'] ?? ($editBanner['end_date'] ?? '')) ?>">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="bannerSort">Display Priority Order</label>
                        <input type="number" id="bannerSort" name="sort_order" class="form-control" value="<?= e($_POST['sort_order'] ?? ($editBanner['sort_order'] ?? 1)) ?>">
                    </div>

                    <div class="form-group" style="padding-top: 1.8rem;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="status" value="1" <?= (!isset($editBanner) || $editBanner['status'] == 1) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--admin-primary);">
                            <span style="font-weight: 600;">Enable Banner</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="bannerImg">Banner Hero Image (Suggested 1920 &times; 800)</label>
                    <input type="file" id="bannerImg" name="image" class="form-control image-upload-input" data-preview="bannerPreview" accept="image/*">
                    <div class="image-preview-box" id="bannerPreview" style="width: 100%; height: 140px; margin-top: 0.5rem;">
                        <?php if (!empty($editBanner['image'])): ?>
                            <img src="<?= BASE_URL . e($editBanner['image']) ?>" alt="Banner Preview" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <span style="font-size:0.75rem; color:var(--admin-muted);">Image Preview</span>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn-admin btn-admin-primary btn-block" style="padding: 0.85rem; font-size: 0.95rem;">
                    <?= $editBanner ? 'Save Banner Changes' : 'Publish to Homepage Hero Carousel' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Active Banners List -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Configured Hero Banners (<?= count($banners) ?>)</h3>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <?php if (empty($banners)): ?>
                <div style="padding: 3rem; text-align: center; color: var(--admin-muted);">No hero banners found.</div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column;">
                    <?php foreach ($banners as $b): 
                        $isExpired = (!empty($b['end_date']) && $b['end_date'] < $today);
                        $isUpcoming = (!empty($b['start_date']) && $b['start_date'] > $today);
                        $isActive = ($b['status'] == 1 && !$isExpired && !$isUpcoming);
                    ?>
                        <div style="display: grid; grid-template-columns: 140px 1fr auto; gap: 1.25rem; align-items: center; padding: 1.25rem; border-bottom: 1px solid var(--admin-border);">
                            <div style="height: 75px; border-radius: 4px; overflow: hidden; border: 1px solid var(--admin-border);">
                                <img src="<?= BASE_URL . e($b['image']) ?>" alt="<?= e($b['title']) ?>" style="width:100%; height:100%; object-fit:cover;">
                            </div>

                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                    <strong style="color: var(--admin-primary); font-size: 1.05rem;"><?= e($b['title']) ?></strong>
                                    <?php if ($isActive): ?>
                                        <span class="badge-status delivered" style="font-size:0.65rem;">Live</span>
                                    <?php elseif ($isExpired): ?>
                                        <span class="badge-status cancelled" style="font-size:0.65rem;">Expired</span>
                                    <?php elseif ($isUpcoming): ?>
                                        <span class="badge-status pending" style="font-size:0.65rem;">Scheduled</span>
                                    <?php else: ?>
                                        <span class="badge-status cancelled" style="font-size:0.65rem;">Disabled</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--admin-text);"><?= e($b['subtitle']) ?></div>
                                <div style="font-size: 0.78rem; color: var(--admin-muted); margin-top: 4px;">
                                    Button: <code><?= e($b['button_text']) ?> &rarr; <?= e($b['button_link']) ?></code> &bull; Priority: <?= $b['sort_order'] ?>
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <a href="<?= BASE_URL ?>admin/banners.php?edit_id=<?= $b['id'] ?>" class="btn-admin btn-admin-primary btn-admin-sm">Edit</a>
                                <a href="<?= BASE_URL ?>admin/banners.php?delete_id=<?= $b['id'] ?>" class="btn-admin btn-admin-danger btn-admin-sm confirm-delete" data-confirm="Delete hero banner '<?= e($b['title']) ?>'?">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
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
