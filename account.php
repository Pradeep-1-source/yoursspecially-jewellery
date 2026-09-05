<?php
/**
 * YoursSpeciallyJewellery - Customer Account Dashboard
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

requireCustomerLogin();
$customer = currentCustomer();

try {
    $db = getDBConnection();

    // Fetch customer's recent orders
    $stmt = $db->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$customer['id']]);
    $recentOrders = $stmt->fetchAll();

    // Fetch customer's saved addresses
    $addrStmt = $db->prepare("SELECT * FROM addresses WHERE customer_id = ? ORDER BY id DESC");
    $addrStmt->execute([$customer['id']]);
    $addresses = $addrStmt->fetchAll();

    // Total spent & order count
    $statStmt = $db->prepare("SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_spent FROM orders WHERE customer_id = ?");
    $statStmt->execute([$customer['id']]);
    $stats = $statStmt->fetch();

} catch (Exception $e) {
    error_log("Account error: " . $e->getMessage());
    $recentOrders = [];
    $addresses = [];
    $stats = ['order_count' => 0, 'total_spent' => 0];
}

// Handle Profile Update POST
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $newName = trim($_POST['name'] ?? '');
        $newPhone = trim($_POST['phone'] ?? '');

        if (!empty($newName)) {
            $upd = $db->prepare("UPDATE customers SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$newName, $newPhone, $customer['id']]);
            $_SESSION['customer_name'] = $newName;
            setFlash('success', 'Your client profile has been updated.');
            header("Location: " . BASE_URL . "account.php");
            exit;
        }
    }
}

$pageTitle = 'My Portfolio & Account | YoursSpeciallyJewellery';
$metaDescription = 'Manage your bespoke profile, saved delivery addresses, and track your fine jewellery orders.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding" style="padding-top: 3rem; padding-bottom: 5rem;">
    <!-- Welcome Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle);">
        <div>
            <span class="section-eyebrow">Client Portfolio</span>
            <h1 style="font-size: 2.2rem; color: var(--primary); margin: 0;">Salutations, <?= e($customer['name']) ?></h1>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 4px;">Member since <?= date('F Y', strtotime($customer['created_at'])) ?></p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>logout.php" class="btn btn-outline btn-sm">Log Out of Portfolio</a>
        </div>
    </div>

    <!-- Stats Highlight Cards -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 3rem;">
        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; text-align: center; box-shadow: var(--shadow-soft);">
            <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); display: block; margin-bottom: 4px;">Total Orders</span>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--primary);"><?= $stats['order_count'] ?></div>
        </div>
        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; text-align: center; box-shadow: var(--shadow-soft);">
            <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); display: block; margin-bottom: 4px;">Total Portfolio Investment</span>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--primary);"><?= formatPrice($stats['total_spent']) ?></div>
        </div>
        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; text-align: center; box-shadow: var(--shadow-soft);">
            <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); display: block; margin-bottom: 4px;">Privilege Status</span>
            <div style="font-size: 1.2rem; font-weight: 600; color: var(--secondary); margin-top: 6px;">Gold Circle Connoisseur</div>
        </div>
    </div>

    <!-- Account Grid (Profile Details + Recent Orders) -->
    <div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 2.5rem; align-items: start;">
        <!-- Left: Profile & Addresses -->
        <div>
            <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2rem; box-shadow: var(--shadow-soft); margin-bottom: 2rem;">
                <h3 style="font-size: 1.2rem; color: var(--primary); margin-bottom: 1.2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.6rem;">Profile Details</h3>
                
                <form method="POST" action="<?= BASE_URL ?>account.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="update_profile" value="1">

                    <div class="form-group">
                        <label class="form-label" for="profName">Full Name</label>
                        <input type="text" id="profName" name="name" class="form-control" value="<?= e($customer['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address (Read-only)</label>
                        <input type="email" class="form-control" value="<?= e($customer['email']) ?>" readonly style="background: var(--bg-cream); color: var(--text-muted);">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="profPhone">Mobile Phone Number</label>
                        <input type="tel" id="profPhone" name="phone" class="form-control" value="<?= e($customer['phone'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-outline btn-block btn-sm">Save Profile Changes</button>
                </form>
            </div>

            <!-- Saved Addresses -->
            <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2rem; box-shadow: var(--shadow-soft);">
                <h3 style="font-size: 1.2rem; color: var(--primary); margin-bottom: 1.2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.6rem;">Saved Addresses</h3>
                <?php if (empty($addresses)): ?>
                    <p style="font-size: 0.88rem; color: var(--text-muted);">No addresses saved yet. Your address will be saved upon placing an order.</p>
                <?php else: ?>
                    <?php foreach ($addresses as $a): ?>
                        <div style="background: var(--bg-cream); padding: 1rem; border-radius: 4px; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 0.75rem;">
                            <strong><?= e($a['name']) ?></strong> (<?= e($a['phone']) ?>)<br>
                            <?= e($a['address_line_1']) ?><?= !empty($a['address_line_2']) ? ', ' . e($a['address_line_2']) : '' ?><br>
                            <?= e($a['city']) ?>, <?= e($a['state']) ?> - <?= e($a['pincode']) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Recent Orders -->
        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2rem; box-shadow: var(--shadow-soft);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.8rem;">
                <h3 style="font-size: 1.25rem; color: var(--primary); margin: 0;">Recent Orders</h3>
                <a href="<?= BASE_URL ?>orders.php" style="font-size: 0.85rem; color: var(--secondary); font-weight: 600;">View All Orders &rarr;</a>
            </div>

            <?php if (empty($recentOrders)): ?>
                <div style="text-align: center; padding: 3rem 1rem;">
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">You have not placed any orders with us yet.</p>
                    <a href="<?= BASE_URL ?>products.php" class="btn btn-primary btn-sm">Explore Jewellery</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <?php foreach ($recentOrders as $o): ?>
                        <div style="border: 1px solid var(--border-subtle); border-radius: 6px; padding: 1.25rem;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                                <div>
                                    <strong style="color: var(--primary); font-size: 1rem;"><?= e($o['order_number']) ?></strong>
                                    <span style="font-size: 0.8rem; color: var(--text-muted); margin-left: 8px;"><?= date('d M Y', strtotime($o['created_at'])) ?></span>
                                </div>
                                <div style="font-weight: 700; color: var(--primary); font-size: 1.05rem;">
                                    <?= formatPrice($o['total_amount']) ?>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                <div style="display: flex; gap: 8px;">
                                    <span class="badge-status <?= strtolower(e($o['order_status'])) ?>"><?= e($o['order_status']) ?></span>
                                    <span class="badge-status <?= strtolower(e($o['payment_status'])) ?>"><?= e($o['payment_status']) ?></span>
                                </div>
                                <a href="<?= BASE_URL ?>order-success.php?order=<?= urlencode($o['order_number']) ?>" style="color: var(--secondary); font-weight: 600; text-decoration: underline;">
                                    View Receipt &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media (max-width: 860px) {
    div[style*="grid-template-columns: repeat(3, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
    div[style*="grid-template-columns: 1fr 1.8fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
