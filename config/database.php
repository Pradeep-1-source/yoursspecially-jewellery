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
// DATABASE CONFIGURATION (Supports Localhost & Hostinger)
// ==========================================================
$isLocalEnv = (
    in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) ||
    in_array(parse_url($_SERVER['HTTP_HOST'] ?? '', PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? ''), ['localhost', '127.0.0.1']) ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
    php_sapi_name() === 'cli'
);

$defaultDbName = $isLocalEnv ? 'yoursspecially' : 'u123456789_yoursspecially';
$defaultDbUser = $isLocalEnv ? 'root' : 'u123456789_dbuser';
$defaultDbPass = $isLocalEnv ? 'root123' : 'YourStrongDbPassword#2026';

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: $defaultDbName);
define('DB_USER', getenv('DB_USER') ?: $defaultDbUser);
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : $defaultDbPass);
define('DB_CHARSET', 'utf8mb4');

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
                throw new Exception("Database connection failed. Please verify DB credentials in config/database.php.");
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
                            <li>Go to <strong>Databases</strong> &rarr; Create a MySQL database and user.</li>
                            <li>Import <code>database.sql</code> via <strong>phpMyAdmin</strong>.</li>
                            <li>Open <code>config/database.php</code> and enter your DB Name, User, and Password.</li>
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
