<?php
/**
 * YoursSpeciallyJewellery - Customer Registration
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

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
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($password !== $confirmPassword) {
            $error = 'Passwords do not match. Please verify.';
        } else {
            $result = registerCustomer($name, $email, $phone, $password);
            if ($result['success']) {
                setFlash('success', 'Your client account has been created successfully! Welcome to YoursSpecially.');
                header("Location: " . BASE_URL . "account.php");
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = 'Create Account | YoursSpeciallyJewellery';
$metaDescription = 'Join the YoursSpeciallyJewellery circle to enjoy personalized concierge assistance, order tracking and bespoke previews.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding" style="max-width: 520px;">
    <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-subtle); padding: 3rem 2.5rem; box-shadow: var(--shadow-card);">
        <div class="text-center" style="margin-bottom: 2rem;">
            <span class="section-eyebrow">Client Registration</span>
            <h1 style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;">Join Our Circle</h1>
            <p style="font-size: 0.9rem; color: var(--text-secondary);">Create an account for personalized jewellery privileges.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: #FDF2F2; border: 1px solid #F8B4B4; color: #9B1C1C; padding: 0.85rem 1rem; border-radius: 4px; font-size: 0.88rem; margin-bottom: 1.5rem;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>register.php">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="regName">Full Name *</label>
                <input type="text" id="regName" name="name" class="form-control" placeholder="Your Name" value="<?= e($_POST['name'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="regEmail">Email Address *</label>
                <input type="email" id="regEmail" name="email" class="form-control" placeholder="you@domain.com" value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="regPhone">Mobile Phone Number</label>
                <input type="tel" id="regPhone" name="phone" class="form-control" placeholder="10-digit mobile number" value="<?= e($_POST['phone'] ?? '') ?>">
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="regPassword">Password *</label>
                    <input type="password" id="regPassword" name="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="regConfirm">Confirm Password *</label>
                    <input type="password" id="regConfirm" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem; padding: 0.9rem;">
                Create Client Account &rarr;
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-subtle); font-size: 0.88rem; color: var(--text-secondary);">
            Already have an account? 
            <a href="<?= BASE_URL ?>login.php" style="color: var(--secondary); font-weight: 600;">Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
