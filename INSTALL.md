# BMJ Pyramids LLP Website - Installation Guide

Production-ready PHP/MySQL B2B quote catalogue with admin panel, live stock, 18% GST and advanced SEO basics.

## 1) Server Requirements

- PHP 7.4+ / PHP 8.x
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `.htaccess` and `mod_rewrite` enabled for clean URLs
- PHP extensions: PDO MySQL, GD recommended for WebP image conversion

## 2) Upload Files

1. Upload the `bmj-pyramids` folder contents to your domain document root, normally `public_html/`.
2. Keep the same folder structure: `admin/`, `public/`, `includes/`, `uploads/`, etc.
3. Set permissions:
   - `uploads/` = `755`
   - `uploads/products/` = `755`
   - `logs/` = `755`

## 3) Create Database

In cPanel:

1. Open **MySQL Databases**.
2. Create database, e.g. `bmj_db`.
3. Create a database user and password.
4. Add the user to the database with **All Privileges**.

## 4) Import SQL

1. Open **phpMyAdmin**.
2. Select your database.
3. Click **Import**.
4. Upload `database.sql`.
5. Confirm import.

## 5) Configure Database Connection

Edit `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'bmj_db');
define('DB_USER', 'your_cpanel_user');
define('DB_PASS', 'your_cpanel_password');
```

Also set your live domain when ready:

```php
define('APP_URL', 'https://www.yourdomain.com');
```

If clean URLs do not work, set:

```php
define('CLEAN_URLS', false);
```

## 6) Login to Admin

Visit:

```text
https://yourdomain.com/admin/login.php
```

Demo admin logins included:

```text
Super Admin: admin@bmj.com   / admin123
Manager:     manager@bmj.com / manager123
Viewer:      viewer@bmj.com  / viewer123
```

Immediately change or delete these demo accounts from **Admin > Team Roles** before going live.


## Demo Data Included

The database import includes demo data so you can understand the workflow quickly:

- 9 demo categories using 3-level structure
- 12 demo products with SVG demo images
- 1 demo customer account
- 2 demo quote orders: one pending and one completed
- 1 demo contact inquiry stored in the database
- Demo stock deduction on products used in sample orders

Demo customer login:

```text
URL:      https://yourdomain.com/login
Email:    customer@bmj.com
Password: customer123
```

See `DEMO_DATA.md` for a guided walkthrough.

## 7) Configure Company Details

Go to:

```text
Admin > SEO Tools > Company Settings
```

Update phone, email, WhatsApp, address and years in business.

## 8) Configure Email Later

Basic PHP `mail()` logging is ready in `includes/mail.php`. All outgoing messages are logged to:

```text
logs/mail.log
```

When SMTP credentials are available, update constants in `includes/config.php` and connect PHPMailer or your hosting SMTP library inside `includes/mail.php`.

## 9) SEO Launch Steps

1. Update `robots.txt` sitemap domain.
2. Set `APP_URL` in `includes/config.php`.
3. Go to **Admin > SEO Tools** and generate sitemap.
4. Submit `/sitemap.xml` in Google Search Console.
5. Edit product/category meta titles and descriptions.

## 10) Daily Workflow

- Add/edit categories in **Admin > Categories**.
- Add products and stock in **Admin > Products**.
- Customer submits quote request at checkout.
- Admin receives/logs order and views it in **Admin > Orders**.
- Super Admin changes status: Pending → Processing → Completed/Cancelled.
- Cancelling an order restores stock automatically.
