<?php
/**
 * YoursSpeciallyJewellery - Authentication & Session Security Handler
 */

require_once dirname(__DIR__) . '/config/config.php';

// ----------------------------------------------------
// CUSTOMER AUTHENTICATION
// ----------------------------------------------------

function isCustomerLoggedIn(): bool {
    return !empty($_SESSION['customer_id']) && is_numeric($_SESSION['customer_id']);
}

function currentCustomerId(): ?int {
    return isCustomerLoggedIn() ? (int)$_SESSION['customer_id'] : null;
}

function currentCustomer(): ?array {
    $id = currentCustomerId();
    if (!$id) {
        return null;
    }

    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id, name, email, phone, created_at FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        error_log("currentCustomer error: " . $e->getMessage());
        return null;
    }
}

function loginCustomer(string $email, string $password): array {
    $email = trim(strtolower($email));

    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Please provide both email and password.'];
    }

    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id, name, email, password_hash FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if ($customer && password_verify($password, $customer['password_hash'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['customer_id'] = (int)$customer['id'];
            $_SESSION['customer_name'] = $customer['name'];
            $_SESSION['customer_email'] = $customer['email'];

            // Merge guest cart items into customer cart if any
            mergeGuestCartToCustomer((int)$customer['id']);

            return ['success' => true, 'customer' => $customer];
        }

        return ['success' => false, 'message' => 'Invalid email address or password.'];
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An unexpected server error occurred. Please try again.'];
    }
}

function registerCustomer(string $name, string $email, string $phone, string $password): array {
    $name = trim($name);
    $email = trim(strtolower($email));
    $phone = trim($phone);

    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Please fill in all required fields.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please provide a valid email address.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }

    try {
        $db = getDBConnection();

        // Check if email already registered
        $stmt = $db->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $insertStmt = $db->prepare("INSERT INTO customers (name, email, phone, password_hash) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([$name, $email, $phone, $passwordHash]);

        $newId = (int)$db->lastInsertId();

        // Auto login newly registered customer
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $newId;
        $_SESSION['customer_name'] = $name;
        $_SESSION['customer_email'] = $email;

        mergeGuestCartToCustomer($newId);

        return ['success' => true, 'customer_id' => $newId];
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Unable to complete registration. Please try again.'];
    }
}

function logoutCustomer(): void {
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_name']);
    unset($_SESSION['customer_email']);
    session_regenerate_id(true);
}

function requireCustomerLogin(string $redirectUrl = 'login.php'): void {
    if (!isCustomerLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'account.php';
        header("Location: " . BASE_URL . $redirectUrl);
        exit;
    }
}

// ----------------------------------------------------
// ADMIN AUTHENTICATION
// ----------------------------------------------------

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id']);
}

function currentAdminId(): ?int {
    return isAdminLoggedIn() ? (int)$_SESSION['admin_id'] : null;
}

function currentAdmin(): ?array {
    $id = currentAdminId();
    if (!$id) {
        return null;
    }

    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id, name, email FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        error_log("currentAdmin error: " . $e->getMessage());
        return null;
    }
}

function loginAdmin(string $email, string $password): array {
    $email = trim(strtolower($email));

    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Please enter both admin email and password.'];
    }

    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id, name, email, password_hash FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Invalid administrative credentials.'];
    } catch (Exception $e) {
        error_log("Admin login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Server error occurred during administrative login.'];
    }
}

function logoutAdmin(): void {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_email']);
    session_regenerate_id(true);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header("Location: " . BASE_URL . "admin/login.php");
        exit;
    }
}

// ----------------------------------------------------
// CART SESSION HELPERS
// ----------------------------------------------------

function getCartSessionId(): string {
    if (empty($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['cart_session_id'];
}

function getActiveCartId(): int {
    $db = getDBConnection();
    $sessionId = getCartSessionId();
    $customerId = currentCustomerId();

    if ($customerId) {
        $stmt = $db->prepare("SELECT id FROM carts WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$customerId]);
        $cart = $stmt->fetch();
        if ($cart) {
            return (int)$cart['id'];
        }
        // Create customer cart
        $stmt = $db->prepare("INSERT INTO carts (customer_id, session_id) VALUES (?, ?)");
        $stmt->execute([$customerId, $sessionId]);
        return (int)$db->lastInsertId();
    } else {
        $stmt = $db->prepare("SELECT id FROM carts WHERE session_id = ? AND customer_id IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->execute([$sessionId]);
        $cart = $stmt->fetch();
        if ($cart) {
            return (int)$cart['id'];
        }
        // Create guest cart
        $stmt = $db->prepare("INSERT INTO carts (session_id) VALUES (?)");
        $stmt->execute([$sessionId]);
        return (int)$db->lastInsertId();
    }
}

function getCartItemCount(): int {
    try {
        $db = getDBConnection();
        $cartId = getActiveCartId();
        $stmt = $db->prepare("SELECT SUM(quantity) as total_qty FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cartId]);
        $res = $stmt->fetch();
        return (int)($res['total_qty'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

function mergeGuestCartToCustomer(int $customerId): void {
    if (empty($_SESSION['cart_session_id'])) {
        return;
    }

    try {
        $db = getDBConnection();
        $sessionId = $_SESSION['cart_session_id'];

        // Find guest cart
        $stmt = $db->prepare("SELECT id FROM carts WHERE session_id = ? AND customer_id IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->execute([$sessionId]);
        $guestCart = $stmt->fetch();

        if (!$guestCart) {
            return;
        }

        $guestCartId = (int)$guestCart['id'];

        // Find or create customer cart
        $stmt = $db->prepare("SELECT id FROM carts WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$customerId]);
        $custCart = $stmt->fetch();

        if (!$custCart) {
            // Simply link guest cart to customer
            $update = $db->prepare("UPDATE carts SET customer_id = ? WHERE id = ?");
            $update->execute([$customerId, $guestCartId]);
            return;
        }

        $custCartId = (int)$custCart['id'];

        // Move items from guest cart to customer cart
        $itemsStmt = $db->prepare("SELECT product_id, quantity, price FROM cart_items WHERE cart_id = ?");
        $itemsStmt->execute([$guestCartId]);
        $guestItems = $itemsStmt->fetchAll();

        foreach ($guestItems as $item) {
            $checkStmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
            $checkStmt->execute([$custCartId, $item['product_id']]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                $upd = $db->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?");
                $upd->execute([$item['quantity'], $existing['id']]);
            } else {
                $ins = $db->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $ins->execute([$custCartId, $item['product_id'], $item['quantity'], $item['price']]);
            }
        }

        // Delete guest cart
        $del = $db->prepare("DELETE FROM carts WHERE id = ?");
        $del->execute([$guestCartId]);
    } catch (Exception $e) {
        error_log("Cart merge error: " . $e->getMessage());
    }
}
