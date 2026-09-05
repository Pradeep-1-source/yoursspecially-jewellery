<?php
/**
 * YoursSpeciallyJewellery - Admin Customer Directory
 */

$adminPageTitle = 'Customer Portfolio Directory';
require_once __DIR__ . '/includes/header.php';

$search = trim($_GET['search'] ?? '');
$db = getDBConnection();

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = implode(" AND ", $where);
$sql = "SELECT c.*, 
        COUNT(o.id) as order_count, 
        COALESCE(SUM(o.total_amount), 0) as total_spent 
        FROM customers c 
        LEFT JOIN orders o ON c.id = o.customer_id 
        WHERE {$whereSql} 
        GROUP BY c.id 
        ORDER BY c.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<!-- Search Toolbar -->
<div class="admin-card" style="margin-bottom: 1.5rem;">
    <div class="admin-card-body" style="padding: 1.25rem;">
        <form method="GET" action="<?= BASE_URL ?>admin/customers.php" style="display: flex; gap: 1rem; align-items: center;">
            <input type="text" name="search" placeholder="Search by name, email, or phone..." value="<?= e($search) ?>" class="form-control" style="width: 320px; padding: 0.5rem 0.85rem;">
            <button type="submit" class="btn-admin btn-admin-primary">Search Clients</button>
            <?php if (!empty($search)): ?>
                <a href="<?= BASE_URL ?>admin/customers.php" class="btn-admin btn-admin-outline">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Customer List Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Registered Clients (<?= count($customers) ?>)</h3>
    </div>
    <div class="admin-table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Email Address</th>
                    <th>Mobile Phone</th>
                    <th>Orders Placed</th>
                    <th>Total Investment</th>
                    <th>Member Since</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 3rem; color: var(--admin-muted);">No customer profiles found.</td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td><strong style="color: var(--admin-primary);"><?= e($c['name']) ?></strong></td>
                            <td><?= e($c['email']) ?></td>
                            <td><?= e($c['phone'] ?: 'N/A') ?></td>
                            <td><span style="font-weight: 600;"><?= $c['order_count'] ?></span></td>
                            <td style="font-weight: 700; color: var(--admin-primary);"><?= formatPrice($c['total_spent']) ?></td>
                            <td style="font-size: 0.85rem; color: var(--admin-muted);"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>admin/orders.php?search=<?= urlencode($c['email']) ?>" class="btn-admin btn-admin-outline btn-admin-sm">
                                    View Orders &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
