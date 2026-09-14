# YoursSpeciallyJewellery — Premium Production Ecommerce Platform

A luxury, fully functional PHP 8+ and MySQL ecommerce application handcrafted for **YoursSpeciallyJewellery**, designed to deploy seamlessly onto **Hostinger Premium Web Hosting** (`public_html`) and manage inventory, hero banners, orders, and clients dynamically.

---

## Brand Visual Identity & Color System
The visual system is derived directly from the brand logo (`YoursSpeciallyJewellery logo.jpeg`):
- **Primary (Deep Wine / Burgundy):** `#5B1B36`
- **Secondary (Rose / Copper):** `#C9654E`
- **Background (Soft Blush Pink):** `#FADCD9`
- **Supporting Background (Warm Ivory / Cream):** `#FFF9F7`
- **Text (Deep Wine / Charcoal):** `#2C101E`
- **Accent (Champagne Gold):** `#C5A059`
- **Typography:** *Cormorant Garamond* / *Playfair Display* (Display Serif) + *Inter* (Body Sans-serif)

---

## Technology Stack
- **Frontend:** Semantic HTML5, Vanilla CSS3 (Custom Luxury Design Tokens), Vanilla JavaScript (ES6+)
- **Backend:** PHP 8+ (PDO with prepared statements, object-oriented payment gateway layer, session protection)
- **Database:** MySQL 5.7+ / 8.0+ / MariaDB (Importable directly via phpMyAdmin)
- **Hosting Environment:** Hostinger Premium Web Hosting (`public_html` / Apache / LiteSpeed)

---

## Directory & File Structure

```text
d:/your jewllery/
├── index.php                 # Luxury Homepage (Hero Banner, Categories, Featured, Reviews)
├── products.php              # Product catalog (Search, Filters, Price Range, Sorting)
├── product.php               # Product details (Gallery, Zoom, WhatsApp Enquiry, Buy Now, Schema)
├── cart.php                  # Shopping Bag (Stock verification, Free shipping progress, Coupons)
├── checkout.php              # Secure Checkout (Address form, Order summary, Payment selection)
├── order-success.php         # Order confirmation & printable receipt
├── about.php                 # Brand heritage, craftsmanship & philosophy
├── contact.php               # Boutique concierge, WhatsApp direct chat & contact form
├── login.php                 # Customer login portal
├── register.php              # Customer registration
├── logout.php                # Customer session sign-out
├── account.php               # Customer portfolio dashboard & saved addresses
├── orders.php                # Customer order history & tracking
├── privacy-policy.php        # Privacy policy
├── terms.php                 # Terms & conditions
├── refund-policy.php         # Return & refund policy
├── shipping-policy.php       # Insured pan-India shipping policy
│
├── admin/                    # Administrative Boutique Console
│   ├── index.php             # Router to dashboard/login
│   ├── login.php             # Admin authentication (bcrypt verify)
│   ├── logout.php            # Admin session termination
│   ├── dashboard.php         # Executive stats (Sales, Orders, Clients, Low Stock alerts)
│   ├── products.php          # Product catalog management (Filter, Search, Status)
│   ├── add-product.php       # Product creation with secure image upload
│   ├── edit-product.php      # Product modifier with image preview
│   ├── categories.php        # Category CRUD & status toggle
│   ├── orders.php            # Order fulfillment list (Filter by status)
│   ├── order-details.php     # Order inspector & status controller (Pending/Shipped/Paid)
│   ├── customers.php         # Client directory & order metrics
│   ├── banners.php           # Dynamic Hero Offer Banner System (Scheduling & Carousel)
│   ├── settings.php          # Boutique identity, WhatsApp number, Shipping rates & Password
│   └── includes/             # Admin sidebar, header, footer
│
├── api/                      # RESTful Backend API Endpoints
│   ├── products.php          # Product query & search API
│   ├── cart.php              # AJAX cart actions & coupon validation
│   ├── checkout.php          # Pre-checkout validation
│   ├── orders.php            # Order tracking endpoint
│   ├── auth.php              # Asynchronous auth state
│   └── banners.php           # Active hero banners endpoint
│
├── payment/                  # Payment Gateway Architecture Layer
│   ├── gateway.php           # Interface & Razorpay/Cashfree gateway implementation
│   ├── create-order.php      # Server-side gateway order initializer
│   ├── verify-payment.php    # Server-side HMAC SHA256 cryptographic signature validator
│   └── webhook.php           # Asynchronous payment capture webhook receiver
│
├── config/                   # Core Configuration & Database
│   ├── database.php          # Hostinger PDO database connection handler
│   ├── database.production.php.example # Production database credentials template
│   └── config.php            # Global site constants, CSRF, price formatter, file uploader
│
├── includes/                 # Common Client Templates
│   ├── header.php            # Master header with announcement bar, nav & cart badge
│   ├── footer.php            # 4-column footer with social links & WhatsApp button
│   └── auth.php              # Customer/Admin auth and cart session merging
│
├── assets/
│   ├── css/
│   │   ├── style.css         # Master luxury design stylesheet
│   │   └── admin.css         # Administrative console stylesheet
│   ├── js/
│   │   ├── main.js           # Hero slider, mobile drawer, AJAX bag, image gallery zoom
│   │   └── admin.js          # File preview, slug generator, delete confirmations
│   ├── images/               # Brand logo, categories, products, hero banners
│   └── uploads/              # Dynamic uploaded files (products, categories, banners)
│
├── database.sql              # Complete MySQL schema & initial seed data
├── .htaccess                 # Security headers, script execution blocks, caching
├── robots.txt                # Search engine crawl rules
├── sitemap.xml               # SEO XML Sitemap
├── README.md                 # Technical Architecture Documentation
└── README-HOSTINGER.md       # 14-Step Beginner Deployment Guide
```

---

## Security Architecture
1. **SQL Injection Defense:** All queries without exception utilize PDO prepared statements with strict parameter binding.
2. **Cross-Site Scripting (XSS):** All dynamic browser outputs are sanitized with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
3. **Cross-Site Request Forgery (CSRF):** Forms transmit cryptographic session tokens validated server-side.
4. **Malicious File Upload Defense:**
   - File extensions validated against whitelist (`jpg`, `jpeg`, `png`, `webp`).
   - MIME types inspected with `finfo(FILEINFO_MIME_TYPE)`.
   - File names sanitized with cryptographically secure random tokens.
   - Script execution inside `/assets/uploads/` is strictly denied in `.htaccess`.
5. **Password Encryption:** Passwords are cryptographically salted and hashed using `password_hash(..., PASSWORD_BCRYPT)`.
6. **Payment Security:** Client-reported payment states are never trusted blindly; HMAC SHA-256 signatures are verified server-side.

---

## Default Administrative Credentials
- **Admin Portal URL:** `http://yourdomain.com/admin/login.php`
- **Default Email:** `admin@yoursspecially.com`
- **Default Password:** `Admin@123`
*(Please update this password immediately upon logging in via Admin &rarr; Site Settings).*
