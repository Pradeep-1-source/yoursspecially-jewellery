<?php
/**
 * YoursSpeciallyJewellery - Global Configuration & Core Helper Engine
 * Compatible with Hostinger Apache/LiteSpeed & PHP 8+
 */

// Strict session security settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// Error reporting: Log errors silently, do not show raw stack traces in production
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Include Database Handler
require_once __DIR__ . '/database.php';

// Dynamically determine Base URL (works seamlessly in root or subdirectories)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($scriptName, '/');
// If script is in /admin, /api, or /payment, trim that out to get root base URL
$basePath = preg_replace('#/(admin|api|payment)$#', '', $basePath);
define('BASE_URL', $protocol . $host . ($basePath ? $basePath : '') . '/');
define('SITE_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('UPLOAD_DIR', SITE_ROOT . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL', BASE_URL . 'assets/uploads/');

/**
 * Fetch dynamic site setting from database with in-memory caching
 */
function getSetting(string $key, string $default = ''): string {
    static $settingsCache = null;

    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $db = getDBConnection();
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
            while ($row = $stmt->fetch()) {
                $settingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            error_log("Settings load error: " . $e->getMessage());
        }
    }

    return $settingsCache[$key] ?? $default;
}

/**
 * Format currency price (INR standard ₹)
 */
function formatPrice($amount): string {
    $symbol = getSetting('currency_symbol', '₹');
    return $symbol . ' ' . number_format((float)$amount, 2);
}

/**
 * Escape string for safe HTML output (XSS defense)
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF Protection Token Generator
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF input field
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Flash message handling
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate URL safe slug
 */
function generateSlug(string $string): string {
    $slug = preg_replace('~[^\pL\d]+~u', '-', $string);
    $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
    $slug = preg_replace('~[^-\w]+~', '', $slug);
    $slug = trim($slug, '-');
    $slug = preg_replace('~-+~', '-', $slug);
    $slug = strtolower($slug);
    return empty($slug) ? 'n-a' : $slug;
}

/**
 * Secure file upload helper
 */
function handleSecureUpload(array $file, string $targetSubdir = ''): ?string {
    if (!isset($file['error']) || is_array($file['error'])) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Limit size to 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp'
    ];

    $ext = array_search($mimeType, $allowedMimes, true);
    if ($ext === false) {
        return null;
    }

    // Ensure uploads directory exists
    $targetDir = UPLOAD_DIR . ($targetSubdir ? trim($targetSubdir, '/\\') . DIRECTORY_SEPARATOR : '');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Create safe random filename
    $filename = sprintf('%s_%s.%s', date('Ymd_His'), bin2hex(random_bytes(8)), $ext);
    $destination = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    return 'assets/uploads/' . ($targetSubdir ? trim($targetSubdir, '/\\') . '/' : '') . $filename;
}
