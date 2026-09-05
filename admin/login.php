<?php
/**
 * YoursSpeciallyJewellery - Administrative Login Portal
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (isAdminLoggedIn()) {
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $error = 'Security session expired. Please reload the login portal.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = loginAdmin($email, $password);
        if ($result['success']) {
            header("Location: " . BASE_URL . "admin/dashboard.php");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In | YoursSpeciallyJewellery</title>
    
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>assets/images/logo.jpeg">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
</head>
<body class="admin-login-body">

<div class="admin-login-card">
    <div style="margin-bottom: 1.5rem;">
        <img src="<?= BASE_URL ?>assets/images/logo.jpeg" alt="YoursSpecially" style="height: 68px; border-radius: 50%; margin: 0 auto 0.75rem; box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
        <h2 style="font-size: 1.6rem; color: var(--admin-primary); margin: 0;">YoursSpecially</h2>
        <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; color: var(--admin-secondary); font-weight: 600;">Atelier Management Console</div>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background: #FDF2F2; border: 1px solid #F8B4B4; color: #9B1C1C; padding: 0.75rem; border-radius: 4px; font-size: 0.85rem; margin-bottom: 1.5rem; text-align: left;">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>admin/login.php" style="text-align: left;">
        <?= csrfField() ?>

        <div class="form-group">
            <label class="form-label" for="adminEmail">Administrator Email</label>
            <input type="email" id="adminEmail" name="email" class="form-control" placeholder="admin@yoursspecially.com" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label" for="adminPass">Password</label>
            <input type="password" id="adminPass" name="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="padding: 0.85rem; margin-top: 1.5rem; font-size: 0.9rem;">
            Authenticate Access &rarr;
        </button>
    </form>

    <div style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid var(--admin-border); font-size: 0.78rem; color: var(--admin-muted);">
        Default Credentials: <code>admin@yoursspecially.com</code> / <code>Admin@123</code><br>
        <span style="color: var(--admin-secondary);">Remember to change default password after first login!</span>
    </div>
</div>

</body>
</html>
