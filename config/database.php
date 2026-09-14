<?php
/**
 * YoursSpeciallyJewellery - Database Configuration & PDO Handler
 * Optimized for Hostinger Web Hosting & Local Development
 */

// Prevent direct script execution
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// ==========================================================
// ENVIRONMENT & CREDENTIALS LOADER (Secure & Non-Tracked)
// Priority:
// 1. config/database.production.php (Untracked PHP array)
// 2. .env file in project root (Untracked key=val pairs)
// 3. System environment variables (getenv / $_ENV / $_SERVER)
// 4. Safe defaults (Local development vs Hostinger production)
// ==========================================================
$prodConfig = [];
$prodConfigFile = __DIR__ . '/database.production.php';
if (file_exists($prodConfigFile)) {
    $loaded = include $prodConfigFile;
    if (is_array($loaded)) {
        $prodConfig = $loaded;
    }
}

// Support root .env file if present
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($envKey, $envVal) = explode('=', $line, 2);
                $envKey = trim($envKey);
                $envVal = trim($envVal);
                $valLen = strlen($envVal);
                if ($valLen >= 2 && (
                    ($envVal[0] === '"' && $envVal[$valLen - 1] === '"') ||
                    ($envVal[0] === "'" && $envVal[$valLen - 1] === "'")
                )) {
                    $envVal = substr($envVal, 1, -1);
                }
                if (!array_key_exists($envKey, $_SERVER) && !array_key_exists($envKey, $_ENV)) {
                    putenv("{$envKey}={$envVal}");
                    $_ENV[$envKey] = $envVal;
                    $_SERVER[$envKey] = $envVal;
                }
            }
        }
    }
}

$getConfigVal = function(string $key, $fallback = '') use ($prodConfig) {
    if (isset($prodConfig[$key]) && $prodConfig[$key] !== '') {
        return $prodConfig[$key];
    }
    $envVal = getenv($key);
    if ($envVal !== false && $envVal !== '') {
        return $envVal;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }
    return $fallback;
};

// ==========================================================
// DATABASE CONFIGURATION (Supports Localhost & Hostinger)
// ==========================================================
$isLocalEnv = (
    in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) ||
    in_array(parse_url($_SERVER['HTTP_HOST'] ?? '', PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? ''), ['localhost', '127.0.0.1']) ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
    php_sapi_name() === 'cli'
);

// Hostinger production database credentials:
// DB_NAME = u391621178_yoursjewellery
// DB_USER = u391621178_yoursjewellery
// DB_HOST = localhost (Hostinger standard MySQL host)
// DB_PASS = configured separately in config/database.production.php or .env (never hardcoded in Git)
$defaultDbName = $isLocalEnv ? 'yoursspecially' : 'u391621178_yoursjewellery';
$defaultDbUser = $isLocalEnv ? 'root' : 'u391621178_yoursjewellery';
$defaultDbPass = $isLocalEnv ? 'root123' : '';

if (!defined('DB_HOST')) define('DB_HOST', $getConfigVal('DB_HOST', 'localhost') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', $getConfigVal('DB_NAME', $defaultDbName));
if (!defined('DB_USER')) define('DB_USER', $getConfigVal('DB_USER', $defaultDbUser));
if (!defined('DB_PASS')) define('DB_PASS', $getConfigVal('DB_PASS', $defaultDbPass));
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Returns the singleton PDO database connection instance.
 *
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES " . DB_CHARSET;
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET NAMES " . DB_CHARSET);
        } catch (Throwable $e) {
            // Log real error securely on server
            error_log("Database Connection Error: " . $e->getMessage());

            // Provide a graceful, secure message without leaking sensitive server secrets
            if (php_sapi_name() === 'cli') {
                throw new Exception("Database connection failed. Please configure DB credentials in config/database.production.php or .env.");
            }

            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Database Setup Required | YoursSpeciallyJewellery</title>
                <style>
                    body {
                        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
                        background: #FFF9F7;
                        color: #5B1B36;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                        margin: 0;
                        padding: 20px;
                        box-sizing: border-box;
                    }
                    .error-card {
                        background: #ffffff;
                        border: 1px solid #FADCD9;
                        border-radius: 12px;
                        padding: 40px;
                        max-width: 580px;
                        box-shadow: 0 10px 30px rgba(91, 27, 54, 0.08);
                        text-align: center;
                    }
                    .logo-text {
                        font-size: 26px;
                        font-weight: 700;
                        color: #5B1B36;
                        letter-spacing: 1px;
                        margin-bottom: 8px;
                    }
                    .tagline {
                        font-size: 13px;
                        text-transform: uppercase;
                        letter-spacing: 2px;
                        color: #C9654E;
                        margin-bottom: 24px;
                    }
                    h2 {
                        font-size: 20px;
                        margin-bottom: 12px;
                        color: #5B1B36;
                    }
                    p {
                        color: #555555;
                        font-size: 14.5px;
                        line-height: 1.6;
                        margin-bottom: 20px;
                    }
                    .steps {
                        text-align: left;
                        background: #FFF9F7;
                        padding: 16px 20px;
                        border-radius: 8px;
                        border-left: 4px solid #C9654E;
                        font-size: 13.5px;
                        color: #444;
                        margin-bottom: 24px;
                    }
                    .steps ol {
                        margin: 0;
                        padding-left: 20px;
                    }
                    .steps li {
                        margin-bottom: 6px;
                    }
                    .badge {
                        display: inline-block;
                        background: #FADCD9;
                        color: #5B1B36;
                        padding: 6px 14px;
                        border-radius: 20px;
                        font-size: 12px;
                        font-weight: 600;
                    }
                </style>
            </head>
            <body>
                <div class="error-card">
                    <div class="logo-text">YoursSpecially</div>
                    <div class="tagline">Luxury Jewellery</div>
                    <span class="badge">Database Connection Notice</span>
                    <h2 style="margin-top: 18px;">Hostinger Database Setup Required</h2>
                    <p>Welcome to YoursSpeciallyJewellery. The website cannot connect to the MySQL database yet. Please configure your database credentials:</p>
                    <div class="steps">
                        <ol>
                            <li>Log in to your <strong>Hostinger cPanel / hPanel</strong>.</li>
                            <li>Go to <strong>Databases</strong> &rarr; Verify your MySQL database (<code>u391621178_yoursjewellery</code>) and user.</li>
                            <li>Ensure <code>database.sql</code> is imported via <strong>phpMyAdmin</strong>.</li>
                            <li>In Hostinger <strong>File Manager</strong>, create <code>config/database.production.php</code> (or <code>.env</code>) and add your database password.</li>
                            <li>Refresh this page to launch your jewellery store!</li>
                        </ol>
                    </div>
                    <p style="font-size: 12px; color: #888;">For detailed assistance, please refer to <code>README-HOSTINGER.md</code> included in the project directory.</p>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }

    return $pdo;
}
