<?php
/**
 * BMJ Pyramids LLP - Core configuration
 * Edit database constants after uploading to hosting.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Karachi');

// Database credentials - update in production.
define('DB_HOST', 'localhost');
define('DB_NAME', 'bmj_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site settings.
define('SITE_NAME', 'BMJ Pyramids LLP');
define('SITE_TAGLINE', 'Industrial Tools, Bearings & B2B Supply Solutions');
define('SITE_EMAIL', 'info@bmjpyramids.com');
define('ADMIN_EMAIL', 'admin@bmj.com');
define('SITE_PHONE', '+92 300 0000000');
define('SITE_ADDRESS', 'Pakistan');
define('GST_RATE', 18.00);
define('LOW_STOCK_DEFAULT', 5);

// Leave blank for automatic domain detection, or set e.g. https://www.bmjpyramids.com
define('APP_URL', '');
// Clean URLs require Apache mod_rewrite and the included .htaccess.
define('CLEAN_URLS', true);

// Mail settings. Configure later when SMTP details are available.
define('SMTP_ENABLED', false);
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_SECURE', 'tls'); // tls or ssl

// Paths.
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('LOG_PATH', ROOT_PATH . '/logs');

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
