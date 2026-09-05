-- ==========================================================
-- YoursSpeciallyJewellery - Database Schema & Initial Data
-- Compatible with MySQL 5.7+ / 8.0+ and Hostinger phpMyAdmin
-- Character Set: utf8mb4 / utf8mb4_unicode_ci
-- ==========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- 1. Admins Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Customers Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_customer_email` (`email`),
  INDEX `idx_customer_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Categories Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_cat_slug` (`slug`),
  INDEX `idx_cat_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Products Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL UNIQUE,
  `description` LONGTEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `sale_price` DECIMAL(10,2) DEFAULT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `main_image` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_prod_cat` (`category_id`),
  INDEX `idx_prod_slug` (`slug`),
  INDEX `idx_prod_sku` (`sku`),
  INDEX `idx_prod_status` (`status`),
  INDEX `idx_prod_featured` (`featured`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Product Images Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_pi_prod` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Carts Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `carts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_cart_cust` (`customer_id`),
  INDEX `idx_cart_session` (`session_id`),
  CONSTRAINT `fk_carts_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Cart Items Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cart_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_ci_cart` (`cart_id`),
  INDEX `idx_ci_prod` (`product_id`),
  CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Addresses Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `addresses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address_line_1` VARCHAR(255) NOT NULL,
  `address_line_2` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `pincode` VARCHAR(20) NOT NULL,
  `country` VARCHAR(80) NOT NULL DEFAULT 'India',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_addr_cust` (`customer_id`),
  CONSTRAINT `fk_addresses_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Orders Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `shipping_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cod',
  `payment_status` ENUM('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `order_status` ENUM('Pending','Confirmed','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `customer_name` VARCHAR(120) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_order_num` (`order_number`),
  INDEX `idx_order_cust` (`customer_id`),
  INDEX `idx_order_status` (`order_status`),
  INDEX `idx_order_pay_status` (`payment_status`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. Order Items Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_oi_order` (`order_id`),
  INDEX `idx_oi_prod` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 11. Banners Table (Hero Promotional Banners)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `banners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `subtitle` VARCHAR(255) DEFAULT NULL,
  `image` VARCHAR(255) NOT NULL,
  `button_text` VARCHAR(60) DEFAULT 'Shop Now',
  `button_link` VARCHAR(255) DEFAULT 'products.php',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_banner_status` (`status`),
  INDEX `idx_banner_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 12. Coupons Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `minimum_order` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `maximum_discount` DECIMAL(10,2) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `usage_limit` INT DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_coupon_code` (`code`),
  INDEX `idx_coupon_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 13. Site Settings Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` LONGTEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- DEFAULT INITIAL DATA / SEED DATA
-- ==========================================================

-- Default Admin User (Password: Admin@123)
INSERT INTO `admins` (`id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Store Administrator', 'admin@yoursspecially.com', '$2y$10$26MpmW0wlJMIIsO8/BoGRe4tso9pEiR3mxHWQX32Yznd4dvgzSXUe', NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Default Site Settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'YoursSpeciallyJewellery'),
('site_tagline', 'Handcrafted Luxury & Timeless Elegance'),
('logo_url', 'assets/images/logo.jpeg'),
('whatsapp_number', '9940474469'),
('instagram_url', 'https://www.instagram.com/yours__specially'),
('contact_email', 'contact@yoursspecially.com'),
('contact_phone', '+91 9940474469'),
('business_address', 'Boutique Studio, Luxury District, Chennai, Tamil Nadu, India'),
('currency_symbol', '₹'),
('currency_code', 'INR'),
('shipping_flat_rate', '99.00'),
('free_shipping_threshold', '999.00'),
('announcement_bar', '✨ Handcrafted Luxury Jewellery • Free Shipping on Orders Above ₹999 Across India ✨'),
('tax_percentage', '3.00')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Default Categories (8 Core Jewellery Categories)
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `status`) VALUES
(1, 'Necklaces', 'necklaces', 'Timeless chokers, layered pendants, and statement heritage necklaces made to mesmerize.', 'assets/images/cat-necklaces.jpg', 1),
(2, 'Earrings', 'earrings', 'From delicate diamond solitaires to graceful chandelier drops and regal jhumkas.', 'assets/images/cat-earrings.jpg', 1),
(3, 'Bracelets', 'bracelets', 'Artisanal tennis bracelets, charm cuffs, and delicate chain silhouettes.', 'assets/images/cat-bracelets.jpg', 1),
(4, 'Rings', 'rings', 'Eternity bands, romantic solitaire rings, and rose-gold statement pieces.', 'assets/images/cat-rings.jpg', 1),
(5, 'Bangles', 'bangles', 'Exquisite handcrafted bridal kadas, rose gold open bangles, and sleek everyday stacks.', 'assets/images/cat-bangles.jpg', 1),
(6, 'Chains', 'chains', 'Dainty Italian snake chains, box links, and minimalist everyday essentials.', 'assets/images/cat-chains.jpg', 1),
(7, 'Jewellery Sets', 'jewellery-sets', 'Harmoniously curated necklace and earring sets for festive celebrations and weddings.', 'assets/images/cat-sets.jpg', 1),
(8, 'New Arrivals', 'new-arrivals', 'Our freshest seasonal collection featuring couture rose-gold and gemstone creations.', 'assets/images/cat-new.jpg', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Default Hero Promotional Banners
INSERT INTO `banners` (`id`, `title`, `subtitle`, `image`, `button_text`, `button_link`, `status`, `start_date`, `end_date`, `sort_order`) VALUES
(1, 'Festive Jewellery Collection', 'Drape yourself in handcrafted grandeur with up to 30% off our couture festive edit.', 'assets/images/banner-hero-1.jpg', 'Shop Festive Edit', 'products.php?category=jewellery-sets', 1, '2026-01-01', '2027-12-31', 1),
(2, 'Eternal Rose Gold & Diamond Brilliance', 'Subtle romantic silhouettes designed for the contemporary woman of grace.', 'assets/images/banner-hero-2.jpg', 'Explore Collection', 'products.php?category=necklaces', 1, '2026-01-01', '2027-12-31', 2),
(3, 'The Bridal & Trousseau Affair', 'Crafted with passion, blessed with heritage. Make your celebration unforgettable.', 'assets/images/banner-hero-3.jpg', 'Discover Trousseau', 'products.php?category=rings', 1, '2026-01-01', '2027-12-31', 3)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Default Coupons
INSERT INTO `coupons` (`code`, `discount_type`, `discount_value`, `minimum_order`, `maximum_discount`, `start_date`, `end_date`, `usage_limit`, `status`) VALUES
('WELCOME10', 'percentage', 10.00, 999.00, 1000.00, '2026-01-01', '2027-12-31', 500, 1),
('SPARKLE500', 'fixed', 500.00, 2999.00, 500.00, '2026-01-01', '2027-12-31', 200, 1),
('FESTIVE15', 'percentage', 15.00, 4999.00, 2500.00, '2026-01-01', '2027-12-31', 300, 1)
ON DUPLICATE KEY UPDATE `discount_value` = VALUES(`discount_value`);

-- Default Products (16 Curated Luxury Jewellery Pieces with Realistic Details)
INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `price`, `sale_price`, `stock`, `sku`, `main_image`, `status`, `featured`) VALUES
(1, 1, 'Aura Eternity Rose Pendant Necklace', 'aura-eternity-rose-pendant-necklace', 'Delicately sculpted with infinity motif inspiration in 18k rose gold finish, studded with high-luster zircon gemstones. Designed for both intimate evenings and effortless everyday glamour.', 2899.00, 2299.00, 15, 'YSJ-NECK-001', 'assets/images/prod-neck-1.jpg', 1, 1),
(2, 1, 'Celeste Empress Kundan Choker', 'celeste-empress-kundan-choker', 'A regal bridal choker adorned with royal uncut Kundan glass stones, cultured freshwater pearls, and intricate meenakari accents on antique micro-gold polish.', 7499.00, 5999.00, 8, 'YSJ-NECK-002', 'assets/images/prod-neck-2.jpg', 1, 1),
(3, 2, 'Seraphina Rose Gold Drop Earrings', 'seraphina-rose-gold-drop-earrings', 'Cascading rose gold drop earrings featuring precision faceted pear-cut crystals that catch the twilight with every delicate sway.', 1999.00, 1599.00, 22, 'YSJ-EAR-001', 'assets/images/prod-ear-1.jpg', 1, 1),
(4, 2, 'Noor Diamond Blossom Chandbali Jhumkas', 'noor-diamond-blossom-chandbali-jhumkas', 'Timeless crescent silhouettes featuring floral cluster studs, micro-pearl tassels, and hand-finished rhodium borders.', 3499.00, 2799.00, 12, 'YSJ-EAR-002', 'assets/images/prod-ear-2.jpg', 1, 0),
(5, 3, 'Infinity Solitaire Tennis Bracelet', 'infinity-solitaire-tennis-bracelet', 'A continuous stream of hand-set pavé stones cradled in a dual-tone rose and wine gold setting with a secure double safety clasp.', 3299.00, 2699.00, 18, 'YSJ-BRAC-001', 'assets/images/prod-brac-1.jpg', 1, 1),
(6, 3, 'Elysian Minimalist Charm Cuff', 'elysian-minimalist-charm-cuff', 'Modern open cuff bracelet accented by twin brilliant-cut solitaires. Flexible fit designed for effortless luxury stacking.', 2199.00, 1799.00, 25, 'YSJ-BRAC-002', 'assets/images/prod-brac-2.jpg', 1, 0),
(7, 4, 'Everlasting Love Rose Gold Eternity Ring', 'everlasting-love-rose-gold-eternity-ring', 'Symbolizing infinite devotion with an intertwined double band motif inspired by our signature brand infinity crest.', 1899.00, 1499.00, 30, 'YSJ-RING-001', 'assets/images/prod-ring-1.jpg', 1, 1),
(8, 4, 'Royal Solitaire Crown Ring', 'royal-solitaire-crown-ring', 'Features a prominent 2-carat look cushion cut centerpiece held securely in a six-prong crown gallery with micro-pave shank.', 2499.00, 1999.00, 14, 'YSJ-RING-002', 'assets/images/prod-ring-2.jpg', 1, 1),
(9, 5, 'Gulmohar Handcrafted Kundan Kada Bangles', 'gulmohar-handcrafted-kundan-kada-bangles', 'Pair of openable artisanal kadas with antique copper-gold plating and ruby red cabochons. Fits wrists 2.4 to 2.8 comfortably.', 4599.00, 3899.00, 10, 'YSJ-BANG-001', 'assets/images/prod-bang-1.jpg', 1, 0),
(10, 5, 'Mirabelle Sleek Rose Gold Bangle Set', 'mirabelle-sleek-rose-gold-bangle-set', 'Set of 4 ultra-chic slim bangles with diamond-cut facets that catch the light effortlessly. Ideal for workplace chic and festive layering.', 2799.00, 2199.00, 20, 'YSJ-BANG-002', 'assets/images/prod-bang-2.jpg', 1, 0),
(11, 6, 'Venetian Box Rose Gold Rope Chain', 'venetian-box-rose-gold-rope-chain', 'Silky smooth 18-inch Italian box chain with radiant rose tint and anti-tarnish micro-coating. Lobster clasp closure.', 1699.00, 1299.00, 35, 'YSJ-CHN-001', 'assets/images/prod-chn-1.jpg', 1, 0),
(12, 6, 'Lumina Layered Dual Pendant Chain', 'lumina-layered-dual-pendant-chain', 'Double strand necklace combining a delicate beaded choker with a longer coin medallion pendant.', 2299.00, 1849.00, 16, 'YSJ-CHN-002', 'assets/images/prod-chn-2.jpg', 1, 0),
(13, 7, 'Sultana Heritage Emerald & Pearl Bridal Set', 'sultana-heritage-emerald-pearl-bridal-set', 'Grand festive ensemble comprising a multi-tier necklace, matching statement earrings, and maang tikka studded with emerald green drop stones.', 9999.00, 7999.00, 6, 'YSJ-SET-001', 'assets/images/prod-set-1.jpg', 1, 1),
(14, 7, 'Aditi Rose Solitaire Necklace & Earring Set', 'aditi-rose-solitaire-necklace-earring-set', 'Understated elegance for cocktail parties, engagements, and formal dinners. 18k rose gold finish with radiant zircon halos.', 4999.00, 3999.00, 15, 'YSJ-SET-002', 'assets/images/prod-set-2.jpg', 1, 1),
(15, 8, 'Valerie Sculpted Butterfly Ring', 'valerie-sculpted-butterfly-ring', 'Whimsical spring collection release featuring 3D layered wings sparkling with pink sapphire and diamond colored stones.', 2199.00, 1749.00, 20, 'YSJ-NEW-001', 'assets/images/prod-new-1.jpg', 1, 1),
(16, 8, 'Aria Lariat Floating Pearl Choker', 'aria-lariat-floating-pearl-choker', 'Contemporary bohemian silhouette with baroque pearl dropper resting along the collarbone.', 2599.00, 2099.00, 18, 'YSJ-NEW-002', 'assets/images/prod-new-2.jpg', 1, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Additional Product Gallery Images
INSERT INTO `product_images` (`product_id`, `image_path`, `sort_order`) VALUES
(1, 'assets/images/prod-neck-1.jpg', 1),
(1, 'assets/images/prod-neck-1-alt1.jpg', 2),
(1, 'assets/images/prod-neck-1-alt2.jpg', 3),
(2, 'assets/images/prod-neck-2.jpg', 1),
(2, 'assets/images/prod-neck-2-alt1.jpg', 2),
(3, 'assets/images/prod-ear-1.jpg', 1),
(4, 'assets/images/prod-ear-2.jpg', 1),
(5, 'assets/images/prod-brac-1.jpg', 1),
(7, 'assets/images/prod-ring-1.jpg', 1),
(7, 'assets/images/prod-ring-1-alt1.jpg', 2),
(13, 'assets/images/prod-set-1.jpg', 1),
(13, 'assets/images/prod-set-1-alt1.jpg', 2)
ON DUPLICATE KEY UPDATE `image_path` = VALUES(`image_path`);

COMMIT;
