# Hostinger Premium Web Hosting Deployment Guide
### Complete 14-Step Beginner-Friendly Tutorial for YoursSpeciallyJewellery

Welcome! This guide is written step-by-step for anyone who has never hosted a website before. By following these steps, you will have your luxury jewellery boutique running live on your custom domain with Hostinger.

---

### Prerequisites
1. An active **Hostinger Premium Web Hosting** plan.
2. A registered domain name connected to your Hostinger plan (e.g., `yoursspecially.com`).
3. Your website project folder (`d:/your jewllery/`).

---

### STEP 1: Create a MySQL Database in Hostinger
1. Log in to your **Hostinger hPanel** ([hpanel.hostinger.com](https://hpanel.hostinger.com)).
2. Under your hosting account, click on **Databases** &rarr; **Management**.
3. Locate the **Create a New MySQL Database and Database User** section.
4. Enter a name for your database (for example, `ysj_store`).
   > *Note: Hostinger automatically adds a prefix like `u123456789_ysj_store`.*

---

### STEP 2: Create a Database User
1. In the same form, enter a **Username** (e.g., `ysj_admin`).
   > *Hostinger will format this as `u123456789_ysj_admin`.*
2. Enter a **Strong Password** (e.g., `LuxuryGems#2026!`).
   > *Write down the Database Name, Username, and Password in a safe place. You will need them in Step 7.*

---

### STEP 3: Assign User Permissions
1. Click the **Create** button.
2. Hostinger automatically assigns full administrative privileges (`ALL PRIVILEGES`) to your newly created user for this database.

---

### STEP 4: Open phpMyAdmin
1. In Hostinger hPanel under **Databases** &rarr; **Management**, scroll down to the **List of Current MySQL Databases And Users**.
2. Find your new database and click the **Enter phpMyAdmin** button next to it.
3. phpMyAdmin will open in a new browser tab.

---

### STEP 5: Import `database.sql`
1. Inside phpMyAdmin, make sure your database name is selected in the left sidebar.
2. Click the **Import** tab on the top navigation bar.
3. Under **File to import**, click **Choose File** (or Browse) and select `database.sql` from your project folder (`d:/your jewllery/database.sql`).
4. Keep all other settings as default (Format: SQL).
5. Scroll down and click the **Import** (or **Go**) button.
6. You will see green success alerts: *"Import has been successfully finished, 13 tables created."*

---

### STEP 6: Upload Website Files into `public_html`
1. On your computer, open your project folder: `d:\your jewllery`.
2. Select all files and folders inside `d:\your jewllery` (do not select the parent folder itself, select the files inside):
   - `index.php`, `products.php`, `product.php`, `cart.php`, `checkout.php` ...
   - folders: `admin`, `api`, `config`, `includes`, `payment`, `assets`
   - `.htaccess`, `robots.txt`, `sitemap.xml`
3. Right-click and choose **Compress to ZIP file** (name it `website.zip`).
4. Back in Hostinger hPanel, go to **Files** &rarr; **File Manager** (access files of your domain).
5. Open the `public_html` directory.
6. Click the **Upload** icon (top right arrow), select `website.zip`, and wait for the upload to complete.
7. Right-click `website.zip` inside `public_html` and select **Extract**.
8. Choose `public_html` as the extraction destination so all files sit directly inside `public_html`.
9. Once extracted, you can delete `website.zip` to save space.

---

### STEP 7: Update Database Configuration
1. Still inside Hostinger **File Manager**, navigate into the `config` folder.
2. Right-click on `database.php` and choose **Edit**.
3. Locate lines 15–18:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'u123456789_ysj_store'); // Replace with your Hostinger DB Name from Step 1
   define('DB_USER', 'u123456789_ysj_admin'); // Replace with your Hostinger DB User from Step 2
   define('DB_PASS', 'YourStrongPassword#2026'); // Replace with your DB Password from Step 2
   ```
4. Update the values with the exact credentials from Steps 1 & 2.
5. Click **Save & Close**.

---

### STEP 8: Open Your Website
1. Open a new browser tab and type your domain:
   `https://yourdomain.com`
2. You will see your jewellery store live with:
   - Your uploaded brand logo
   - Deep Wine, Rose Copper, Soft Blush Pink and Champagne Gold palette
   - Dynamic Hero Promotional Carousel
   - 8 Jewellery Categories
   - 16 Handcrafted Featured Products
   - Floating WhatsApp Concierge button (`9940474469`)
   - Instagram Atelier link (`@yours__specially`)

---

### STEP 9: Open `/admin`
1. In your browser, navigate to:
   `https://yourdomain.com/admin`
2. You will be greeted by the administrative portal.
3. Sign in with the default credentials:
   - **Email:** `admin@yoursspecially.com`
   - **Password:** `Admin@123`
4. Click **Authenticate Access**. You will arrive at the executive dashboard.

---

### STEP 10: Create / Change Admin Password
1. In the admin sidebar, click **Site Settings**.
2. Scroll to the **Administrator Security & Password** card.
3. Enter:
   - **Current Password:** `Admin@123`
   - **New Password:** Enter your new personal strong password
   - **Confirm New Password:** Re-enter your password
4. Click **Update Admin Password**.

---

### STEP 11: Test Product & Banner Creation
1. Go to **Admin &rarr; Products** &rarr; **Add New Product**.
   - Enter a title, choose a category, input price, SKU, and upload a jewellery photo.
   - Click **Publish Jewellery Creation**.
2. Go to **Admin &rarr; Hero Banners**:
   - Change the headline, offer discount percentage (e.g., *"Up to 30% OFF"*), or button link.
   - Click **Save**.
3. Visit your homepage (`https://yourdomain.com`) in another tab to verify the new product and banner are live immediately!

---

### STEP 12: Test Shopping Bag & Checkout
1. On your live store, click on any jewellery creation.
2. Test **Add to Bag** & check the cart counter badge.
3. Go to **Bag** (`/cart.php`), test applying promo voucher code: `WELCOME10` or `SPARKLE500`.
4. Click **Proceed to Secure Checkout**.
5. Fill in test customer shipping information, choose **Cash On Delivery (COD)**, and click **Place Order**.
6. Verify you land on the luxury **Order Confirmation Receipt** with an assigned order number like `YSJ-2026XXXX-XXXX`.
7. Go back to **Admin &rarr; Orders** to see the new order appear live! Click **Manage** to update its status to *Processing* or *Shipped*.

---

### STEP 13: Connect Your Payment Gateway (Razorpay / Cashfree)
When you receive your Indian Payment Gateway credentials:
1. Open `payment/gateway.php` in Hostinger File Manager.
2. Locate lines 15–20:
   ```php
   // Razorpay Credentials:
   define('RAZORPAY_KEY_ID', 'rzp_live_xxxxxxxxxxxx');
   define('RAZORPAY_KEY_SECRET', 'your_actual_secret_key_here');
   ```
3. Paste your live API Key ID and Key Secret.
4. In your Razorpay Dashboard, set the Webhook URL to:
   `https://yourdomain.com/payment/webhook.php`
5. Save the file. Online checkout will immediately begin collecting payments to your bank account!

---

### STEP 14: Configure SSL / HTTPS
1. In Hostinger hPanel, go to **Security** &rarr; **SSL**.
2. Ensure **Lifetime Free SSL** is active on your domain.
3. Toggle on **Force HTTPS** so all customer connections are encrypted with 256-bit SSL.

---

### You Are Live!
Your luxury jewellery ecommerce store is now completely deployed, secure, and ready for customers across India. For concierge assistance, your store is pre-wired to your WhatsApp (`9940474469`) and Instagram (`@yours__specially`).
