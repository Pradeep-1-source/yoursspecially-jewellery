<?php
/**
 * YoursSpeciallyJewellery - Customer Login
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isCustomerLoggedIn()) {
    header("Location: " . BASE_URL . "account.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $error = 'Security session expired. Please reload the page.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = loginCustomer($email, $password);
        if ($result['success']) {
            $redirect = $_SESSION['redirect_after_login'] ?? 'account.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: " . BASE_URL . ltrim($redirect, '/'));
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Customer Login | YoursSpeciallyJewellery';
$metaDescription = 'Sign in to your YoursSpeciallyJewellery account to track your orders and manage your jewellery boutique profile.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding" style="max-width: 480px;">
    <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-subtle); padding: 3rem 2.5rem; box-shadow: var(--shadow-card);">
        <div class="text-center" style="margin-bottom: 2rem;">
            <span class="section-eyebrow">Client Portal</span>
            <h1 style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;">Welcome Back</h1>
            <p style="font-size: 0.9rem; color: var(--text-secondary);">Sign in to access your curated jewellery portfolio.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: #FDF2F2; border: 1px solid #F8B4B4; color: #9B1C1C; padding: 0.85rem 1rem; border-radius: 4px; font-size: 0.88rem; margin-bottom: 1.5rem;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>login.php">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="loginEmail">Email Address</label>
                <input type="email" id="loginEmail" name="email" class="form-control" placeholder="you@domain.com" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label class="form-label" for="loginPassword" style="margin-bottom: 0;">Password</label>
                    <a href="<?= BASE_URL ?>contact.php?subject=forgot_password" style="font-size: 0.78rem; color: var(--secondary);">Forgot Password?</a>
                </div>
                <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem; padding: 0.9rem;">
                Sign In to Boutique &rarr;
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-subtle); font-size: 0.88rem; color: var(--text-secondary);">
            New to YoursSpecially? 
            <a href="<?= BASE_URL ?>register.php" style="color: var(--secondary); font-weight: 600;">Create an Account</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
