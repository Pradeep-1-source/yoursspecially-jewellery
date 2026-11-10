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
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
);
$protocol = $isHttps ? "https://" : "http://";
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
 * Safely resolves a product image URL, verifying physical file existence on disk
 * and providing intelligent category-based luxury fallback images.
 */
function getProductImageUrl(?string $imagePath, string $category = '', string $productName = ''): string {
    if (!empty($imagePath)) {
        $cleanPath = ltrim(trim($imagePath), '/');
        
        // Handle external absolute URLs
        if (preg_match('#^https?://#i', $cleanPath)) {
            if (strpos(BASE_URL, 'https://') === 0 && strpos($cleanPath, 'http://') === 0) {
                return 'https://' . substr($cleanPath, 7);
            }
            return $cleanPath;
        }

        // Verify physical file exists on disk
        $localFile = SITE_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
        if (file_exists($localFile) && is_file($localFile)) {
            return BASE_URL . $cleanPath;
        }
    }

    // Intelligent fallback based on product name and category keywords
    $nameLower = strtolower($productName . ' ' . $category);

    if (strpos($nameLower, 'earring') !== false || strpos($nameLower, 'jhumka') !== false || strpos($nameLower, 'tulip') !== false) {
        return BASE_URL . 'assets/images/prod-ear-1.jpg';
    }
    if (strpos($nameLower, 'choker') !== false || strpos($nameLower, 'necklace') !== false || strpos($nameLower, 'pendant') !== false || strpos($nameLower, 'lariat') !== false) {
        return BASE_URL . 'assets/images/prod-neck-1.jpg';
    }
    if (strpos($nameLower, 'bracelet') !== false || strpos($nameLower, 'cuff') !== false) {
        return BASE_URL . 'assets/images/prod-brac-1.jpg';
    }
    if (strpos($nameLower, 'ring') !== false) {
        return BASE_URL . 'assets/images/prod-ring-1.jpg';
    }
    if (strpos($nameLower, 'bangle') !== false || strpos($nameLower, 'kada') !== false) {
        return BASE_URL . 'assets/images/prod-bang-1.jpg';
    }
    if (strpos($nameLower, 'chain') !== false) {
        return BASE_URL . 'assets/images/prod-chn-1.jpg';
    }
    if (strpos($nameLower, 'set') !== false || strpos($nameLower, 'bridal') !== false || strpos($nameLower, 'ethnic') !== false) {
        return BASE_URL . 'assets/images/prod-set-1.jpg';
    }
    if (strpos($nameLower, 'new') !== false) {
        return BASE_URL . 'assets/images/prod-new-1.jpg';
    }

    return BASE_URL . 'assets/images/prod-neck-1.jpg';
}

/**
 * Safely resolves a category image URL, verifying physical file existence on disk
 * and providing high-resolution category fallback images.
 */
function getCategoryImageUrl(?string $imagePath, string $slug = '', string $name = ''): string {
    if (!empty($imagePath)) {
        $cleanPath = ltrim(trim($imagePath), '/');
        
        if (preg_match('#^https?://#i', $cleanPath)) {
            if (strpos(BASE_URL, 'https://') === 0 && strpos($cleanPath, 'http://') === 0) {
                return 'https://' . substr($cleanPath, 7);
            }
            return $cleanPath;
        }

        $localFile = SITE_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
        if (file_exists($localFile) && is_file($localFile)) {
            return BASE_URL . $cleanPath;
        }
    }

    $key = strtolower($slug . ' ' . $name);

    if (strpos($key, 'neck') !== false || strpos($key, 'choker') !== false) {
        return BASE_URL . 'assets/images/cat-necklaces.jpg';
    }
    if (strpos($key, 'ear') !== false) {
        return BASE_URL . 'assets/images/cat-earrings.jpg';
    }
    if (strpos($key, 'brac') !== false) {
        return BASE_URL . 'assets/images/cat-bracelets.jpg';
    }
    if (strpos($key, 'ring') !== false) {
        return BASE_URL . 'assets/images/cat-rings.jpg';
    }
    if (strpos($key, 'bang') !== false || strpos($key, 'kada') !== false) {
        return BASE_URL . 'assets/images/cat-bangles.jpg';
    }
    if (strpos($key, 'chain') !== false) {
        return BASE_URL . 'assets/images/cat-chains.jpg';
    }
    if (strpos($key, 'set') !== false) {
        return BASE_URL . 'assets/images/cat-sets.jpg';
    }
    if (strpos($key, 'new') !== false) {
        return BASE_URL . 'assets/images/cat-new.jpg';
    }

    return BASE_URL . 'assets/images/cat-necklaces.jpg';
}

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
