<?php
/**
 * YoursSpeciallyJewellery - Admin Boutique Settings & Admin Security
 */

$adminPageTitle = 'Boutique Settings & Configuration';
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();
$admin = currentAdmin();
$errors = [];

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh the page.';
    } else {
        // 1. Site Settings Update
        if (isset($_POST['save_settings'])) {
            $settings = [
                'site_name' => trim($_POST['site_name'] ?? 'YoursSpeciallyJewellery'),
                'site_tagline' => trim($_POST['site_tagline'] ?? ''),
                'whatsapp_number' => trim($_POST['whatsapp_number'] ?? '9940474469'),
                'instagram_url' => trim($_POST['instagram_url'] ?? 'https://www.instagram.com/yours__specially'),
                'contact_email' => trim($_POST['contact_email'] ?? ''),
                'contact_phone' => trim($_POST['contact_phone'] ?? ''),
                'business_address' => trim($_POST['business_address'] ?? ''),
                'currency_symbol' => trim($_POST['currency_symbol'] ?? '₹'),
                'shipping_flat_rate' => trim($_POST['shipping_flat_rate'] ?? '99.00'),
                'free_shipping_threshold' => trim($_POST['free_shipping_threshold'] ?? '999.00'),
                'announcement_bar' => trim($_POST['announcement_bar'] ?? '')
            ];

            try {
                $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                foreach ($settings as $key => $val) {
                    $stmt->execute([$key, $val]);
                }
                setFlash('success', 'Boutique settings saved successfully.');
                header("Location: " . BASE_URL . "admin/settings.php");
                exit;
            } catch (Exception $e) {
                error_log("Save settings error: " . $e->getMessage());
                $errors[] = 'Database error updating settings.';
            }
        }

        // 2. Change Admin Password
        if (isset($_POST['change_password'])) {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (strlen($newPass) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($newPass !== $confirmPass) {
                $errors[] = 'New passwords do not match.';
            } else {
                // Verify current password
                $pStmt = $db->prepare("SELECT password_hash FROM admins WHERE id = ? LIMIT 1");
                $pStmt->execute([$admin['id']]);
                $hash = $pStmt->fetchColumn();

                if ($hash && password_verify($currentPass, $hash)) {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                    $upd = $db->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                    $upd->execute([$newHash, $admin['id']]);
                    setFlash('success', 'Administrator password successfully updated!');
                    header("Location: " . BASE_URL . "admin/settings.php");
                    exit;
                } else {
                    $errors[] = 'Current administrator password is incorrect.';
                }
            }
        }
    }
}
?>

<div style="max-width: 900px; margin: 0 auto;">
    <?php if (!empty($errors)): ?>
        <div style="background: #FDF2F2; border: 1px solid #F8B4B4; color: #9B1C1C; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Site Configuration Card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Boutique Identity & Contact Coordinates</h3>
        </div>
        <div class="admin-card-body">
            <form method="POST" action="<?= BASE_URL ?>admin/settings.php">
                <?= csrfField() ?>
                <input type="hidden" name="save_settings" value="1">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="siteName">Brand / Website Name</label>
                        <input type="text" id="siteName" name="site_name" class="form-control" value="<?= e(getSetting('site_name', 'YoursSpeciallyJewellery')) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="siteTagline">Brand Tagline</label>
                        <input type="text" id="siteTagline" name="site_tagline" class="form-control" value="<?= e(getSetting('site_tagline', 'Handcrafted Luxury & Timeless Elegance')) ?>">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="waNum">Business WhatsApp Number</label>
                        <input type="text" id="waNum" name="whatsapp_number" class="form-control" value="<?= e(getSetting('whatsapp_number', '9940474469')) ?>" required>
                        <small style="color: var(--admin-muted); font-size: 0.75rem;">10-digit mobile number for customer click-to-chat concierge.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="instaUrl">Official Instagram URL</label>
                        <input type="url" id="instaUrl" name="instagram_url" class="form-control" value="<?= e(getSetting('instagram_url', 'https://www.instagram.com/yours__specially')) ?>" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="contEmail">Contact Email</label>
                        <input type="email" id="contEmail" name="contact_email" class="form-control" value="<?= e(getSetting('contact_email', 'contact@yoursspecially.com')) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contPhone">Contact Telephone</label>
                        <input type="text" id="contPhone" name="contact_phone" class="form-control" value="<?= e(getSetting('contact_phone', '+91 9940474469')) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="bizAddr">Boutique Studio Physical Address</label>
                    <textarea id="bizAddr" name="business_address" rows="2" class="form-control"><?= e(getSetting('business_address', 'Boutique Studio, Luxury District, Chennai, Tamil Nadu, India')) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="announceBar">Announcement Bar Headline</label>
                    <input type="text" id="announceBar" name="announcement_bar" class="form-control" value="<?= e(getSetting('announcement_bar', '✨ Handcrafted Luxury Jewellery • Free Shipping on Orders Above ₹999 Across India ✨')) ?>">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="flatRate">Flat Delivery Charge (INR ₹)</label>
                        <input type="number" step="0.01" id="flatRate" name="shipping_flat_rate" class="form-control" value="<?= e(getSetting('shipping_flat_rate', '99.00')) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="freeThresh">Free Shipping Order Threshold (INR ₹)</label>
                        <input type="number" step="0.01" id="freeThresh" name="free_shipping_threshold" class="form-control" value="<?= e(getSetting('free_shipping_threshold', '999.00')) ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 0.85rem 2rem; margin-top: 1rem;">
                    Save Boutique Settings
                </button>
            </form>
        </div>
    </div>

    <!-- Admin Password Security Card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Administrator Security & Password</h3>
        </div>
        <div class="admin-card-body">
            <form method="POST" action="<?= BASE_URL ?>admin/settings.php">
                <?= csrfField() ?>
                <input type="hidden" name="change_password" value="1">

                <div class="form-group">
                    <label class="form-label" for="currPass">Current Administrator Password *</label>
                    <input type="password" id="currPass" name="current_password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="newPass">New Password *</label>
                        <input type="password" id="newPass" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirmPass">Confirm New Password *</label>
                        <input type="password" id="confirmPass" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                    </div>
                </div>

                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 0.85rem 2rem; margin-top: 0.5rem;">
                    Update Admin Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
