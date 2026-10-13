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

            // Automatically verify required schema and default data integrity
            autoMigrateDatabaseIfNeeded($pdo);
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

/**
 * Automatically ensures required columns, tables, and core catalogue data exist.
 * Runs silently and safely without modifying or overwriting existing user data.
 */
function autoMigrateDatabaseIfNeeded(PDO $pdo): void {
    static $migrated = false;
    if ($migrated) return;
    $migrated = true;

    try {
        // 1. Ensure categories table has display_order
        $hasCol = false;
        try {
            $chk = $pdo->query("SHOW COLUMNS FROM `categories` LIKE 'display_order'");
            $hasCol = (bool)$chk->fetch();
        } catch (Throwable $e) {}

        if (!$hasCol) {
            $pdo->exec("ALTER TABLE `categories` ADD COLUMN `display_order` INT NOT NULL DEFAULT 0 AFTER `status`");
            try {
                $pdo->exec("ALTER TABLE `categories` ADD INDEX `idx_cat_order` (`status`, `display_order`, `id`)");
            } catch (Throwable $e) {}
        }

        // 2. Ensure default 8 core categories exist if categories table is empty
        try {
            $catCount = (int)$pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn();
            if ($catCount === 0) {
                $pdo->exec("INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `status`, `display_order`) VALUES
                    (1, 'Necklaces', 'necklaces', 'Timeless chokers, layered pendants, and statement heritage necklaces made to mesmerize.', 'assets/images/cat-necklaces.jpg', 1, 1),
                    (2, 'Earrings', 'earrings', 'From delicate diamond solitaires to graceful chandelier drops and regal jhumkas.', 'assets/images/cat-earrings.jpg', 1, 2),
                    (3, 'Bracelets', 'bracelets', 'Artisanal tennis bracelets, charm cuffs, and delicate chain silhouettes.', 'assets/images/cat-bracelets.jpg', 1, 3),
                    (4, 'Rings', 'rings', 'Eternity bands, romantic solitaire rings, and rose-gold statement pieces.', 'assets/images/cat-rings.jpg', 1, 4),
                    (5, 'Bangles', 'bangles', 'Exquisite handcrafted bridal kadas, rose gold open bangles, and sleek everyday stacks.', 'assets/images/cat-bangles.jpg', 1, 5),
                    (6, 'Chains', 'chains', 'Dainty Italian snake chains, box links, and minimalist everyday essentials.', 'assets/images/cat-chains.jpg', 1, 6),
                    (7, 'Jewellery Sets', 'jewellery-sets', 'Harmoniously curated necklace and earring sets for festive celebrations and weddings.', 'assets/images/cat-sets.jpg', 1, 7),
                    (8, 'New Arrivals', 'new-arrivals', 'Our freshest seasonal collection featuring couture rose-gold and gemstone creations.', 'assets/images/cat-new.jpg', 1, 8)
                    ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)");
            }
        } catch (Throwable $e) {}

        // 3. Ensure products table has display_order and is_new_arrival
        $hasProdOrder = false;
        $hasProdNew = false;
        try {
            $chk1 = $pdo->query("SHOW COLUMNS FROM `products` LIKE 'display_order'");
            $hasProdOrder = (bool)$chk1->fetch();
            $chk2 = $pdo->query("SHOW COLUMNS FROM `products` LIKE 'is_new_arrival'");
            $hasProdNew = (bool)$chk2->fetch();
        } catch (Throwable $e) {}

        if (!$hasProdOrder) {
            $pdo->exec("ALTER TABLE `products` ADD COLUMN `display_order` INT NOT NULL DEFAULT 0 AFTER `stock`");
            try {
                $pdo->exec("ALTER TABLE `products` ADD INDEX `idx_prod_order` (`status`, `display_order`, `id`)");
            } catch (Throwable $e) {}
        }
        if (!$hasProdNew) {
            $pdo->exec("ALTER TABLE `products` ADD COLUMN `is_new_arrival` TINYINT(1) NOT NULL DEFAULT 0 AFTER `featured`");
            try {
                $pdo->exec("ALTER TABLE `products` ADD INDEX `idx_prod_new` (`status`, `is_new_arrival`, `display_order`)");
            } catch (Throwable $e) {}
        }

        // 4. Ensure default products exist if products table is empty
        try {
            $prodCount = (int)$pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
            if ($prodCount === 0) {
                $pdo->exec("INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `price`, `sale_price`, `stock`, `sku`, `main_image`, `status`, `featured`, `is_new_arrival`, `display_order`) VALUES
                    (1, 1, 'Aura Eternity Rose Pendant Necklace', 'aura-eternity-rose-pendant-necklace', 'Delicately sculpted with infinity motif inspiration in 18k rose gold finish, studded with high-luster zircon gemstones. Designed for both intimate evenings and effortless everyday glamour.', 2899.00, 2299.00, 15, 'YSJ-NECK-001', 'assets/images/prod-neck-1.jpg', 1, 1, 1, 1),
                    (2, 1, 'Celeste Empress Kundan Choker', 'celeste-empress-kundan-choker', 'A regal bridal choker adorned with royal uncut Kundan glass stones, cultured freshwater pearls, and intricate meenakari accents on antique micro-gold polish.', 7499.00, 5999.00, 8, 'YSJ-NECK-002', 'assets/images/prod-neck-2.jpg', 1, 1, 1, 2),
                    (3, 2, 'Seraphina Rose Gold Drop Earrings', 'seraphina-rose-gold-drop-earrings', 'Cascading rose gold drop earrings featuring precision faceted pear-cut crystals that catch the twilight with every delicate sway.', 1999.00, 1599.00, 22, 'YSJ-EAR-001', 'assets/images/prod-ear-1.jpg', 1, 1, 1, 3),
                    (4, 2, 'Noor Diamond Blossom Chandbali Jhumkas', 'noor-diamond-blossom-chandbali-jhumkas', 'Timeless crescent silhouettes featuring floral cluster studs, micro-pearl tassels, and hand-finished rhodium borders.', 3499.00, 2799.00, 12, 'YSJ-EAR-002', 'assets/images/prod-ear-2.jpg', 1, 0, 1, 4),
                    (5, 3, 'Infinity Solitaire Tennis Bracelet', 'infinity-solitaire-tennis-bracelet', 'A continuous stream of hand-set pavé stones cradled in a dual-tone rose and wine gold setting with a secure double safety clasp.', 3299.00, 2699.00, 18, 'YSJ-BRAC-001', 'assets/images/prod-brac-1.jpg', 1, 1, 0, 5),
                    (6, 3, 'Elysian Minimalist Charm Cuff', 'elysian-minimalist-charm-cuff', 'Modern open cuff bracelet accented by twin brilliant-cut solitaires. Flexible fit designed for effortless luxury stacking.', 2199.00, 1799.00, 25, 'YSJ-BRAC-002', 'assets/images/prod-brac-2.jpg', 1, 0, 0, 6),
                    (7, 4, 'Everlasting Love Rose Gold Eternity Ring', 'everlasting-love-rose-gold-eternity-ring', 'Symbolizing infinite devotion with an intertwined double band motif inspired by our signature brand infinity crest.', 1899.00, 1499.00, 30, 'YSJ-RING-001', 'assets/images/prod-ring-1.jpg', 1, 1, 0, 7),
                    (8, 4, 'Royal Solitaire Crown Ring', 'royal-solitaire-crown-ring', 'Features a prominent 2-carat look cushion cut centerpiece held securely in a six-prong crown gallery with micro-pave shank.', 2499.00, 1999.00, 14, 'YSJ-RING-002', 'assets/images/prod-ring-2.jpg', 1, 1, 0, 8),
                    (9, 5, 'Gulmohar Handcrafted Kundan Kada Bangles', 'gulmohar-handcrafted-kundan-kada-bangles', 'Pair of openable artisanal kadas with antique copper-gold plating and ruby red cabochons. Fits wrists 2.4 to 2.8 comfortably.', 4599.00, 3899.00, 10, 'YSJ-BANG-001', 'assets/images/prod-bang-1.jpg', 1, 0, 0, 9),
                    (10, 5, 'Mirabelle Sleek Rose Gold Bangle Set', 'mirabelle-sleek-rose-gold-bangle-set', 'Set of 4 ultra-chic slim bangles with diamond-cut facets that catch the light effortlessly. Ideal for workplace chic and festive layering.', 2799.00, 2199.00, 20, 'YSJ-BANG-002', 'assets/images/prod-bang-2.jpg', 1, 0, 0, 10),
                    (11, 6, 'Venetian Box Rose Gold Rope Chain', 'venetian-box-rose-gold-rope-chain', 'Silky smooth 18-inch Italian box chain with radiant rose tint and anti-tarnish micro-coating. Lobster clasp closure.', 1699.00, 1299.00, 35, 'YSJ-CHN-001', 'assets/images/prod-chn-1.jpg', 1, 0, 0, 11),
                    (12, 6, 'Lumina Layered Dual Pendant Chain', 'lumina-layered-dual-pendant-chain', 'Double strand necklace combining a delicate beaded choker with a longer coin medallion pendant.', 2299.00, 1849.00, 16, 'YSJ-CHN-002', 'assets/images/prod-chn-2.jpg', 1, 0, 0, 12),
                    (13, 7, 'Sultana Heritage Emerald & Pearl Bridal Set', 'sultana-heritage-emerald-pearl-bridal-set', 'Grand festive ensemble comprising a multi-tier necklace, matching statement earrings, and maang tikka studded with emerald green drop stones.', 9999.00, 7999.00, 6, 'YSJ-SET-001', 'assets/images/prod-set-1.jpg', 1, 1, 0, 13),
                    (14, 7, 'Aditi Rose Solitaire Necklace & Earring Set', 'aditi-rose-solitaire-necklace-earring-set', 'Understated elegance for cocktail parties, engagements, and formal dinners. 18k rose gold finish with radiant zircon halos.', 4999.00, 3999.00, 15, 'YSJ-SET-002', 'assets/images/prod-set-2.jpg', 1, 1, 0, 14),
                    (15, 8, 'Valerie Sculpted Butterfly Ring', 'valerie-sculpted-butterfly-ring', 'Whimsical spring collection release featuring 3D layered wings sparkling with pink sapphire and diamond colored stones.', 2199.00, 1749.00, 20, 'YSJ-NEW-001', 'assets/images/prod-new-1.jpg', 1, 1, 0, 15),
                    (16, 8, 'Aria Lariat Floating Pearl Choker', 'aria-lariat-floating-pearl-choker', 'Contemporary bohemian silhouette with baroque pearl dropper resting along the collarbone.', 2599.00, 2099.00, 18, 'YSJ-NEW-002', 'assets/images/prod-new-2.jpg', 1, 1, 0, 16)
                    ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)");
            }
        } catch (Throwable $e) {}

        // 5. Ensure product_variants table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS `product_variants` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT UNSIGNED NOT NULL,
            `variant_type` VARCHAR(50) NOT NULL DEFAULT 'Option',
            `variant_value` VARCHAR(100) NOT NULL,
            `sku` VARCHAR(60) DEFAULT NULL,
            `price` DECIMAL(10,2) DEFAULT NULL,
            `stock` INT NOT NULL DEFAULT 10,
            `status` TINYINT(1) NOT NULL DEFAULT 1,
            `display_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_pv_prod` (`product_id`, `status`, `display_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 6. Ensure cart_items and order_items have variant columns
        try {
            $chkC = $pdo->query("SHOW COLUMNS FROM `cart_items` LIKE 'variant_id'");
            if (!$chkC->fetch()) {
                $pdo->exec("ALTER TABLE `cart_items` ADD COLUMN `variant_id` INT UNSIGNED DEFAULT NULL AFTER `product_id`, ADD COLUMN `variant_info` VARCHAR(255) DEFAULT NULL AFTER `variant_id`");
            }
        } catch (Throwable $e) {}

        try {
            $chkO = $pdo->query("SHOW COLUMNS FROM `order_items` LIKE 'variant_id'");
            if (!$chkO->fetch()) {
                $pdo->exec("ALTER TABLE `order_items` ADD COLUMN `variant_id` INT UNSIGNED DEFAULT NULL AFTER `product_id`, ADD COLUMN `variant_info` VARCHAR(255) DEFAULT NULL AFTER `variant_id`");
            }
        } catch (Throwable $e) {}

    } catch (Throwable $e) {
        error_log("Schema auto-migration notice: " . $e->getMessage());
    }
}
