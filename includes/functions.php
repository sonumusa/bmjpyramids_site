<?php
require_once __DIR__ . '/config.php';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function app_base_url(): string
{
    if (defined('APP_URL') && APP_URL !== '') {
        return rtrim(APP_URL, '/');
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($isHttps ? 'https://' : 'http://') . $host;
}

function site_url(string $path = ''): string
{
    $base = app_base_url();
    $path = ltrim($path, '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function frontend_url(string $page = 'index', array $params = []): string
{
    $routes = [
        'index' => '',
        'shop' => 'shop',
        'cart' => 'cart',
        'checkout' => 'checkout',
        'login' => 'login',
        'signup' => 'signup',
        'my-account' => 'my-account',
        'about' => 'about',
        'contact' => 'contact',
        'logout' => 'logout',
    ];

    if (CLEAN_URLS && array_key_exists($page, $routes)) {
        $path = $routes[$page];
    } else {
        $path = 'public/' . ($page ?: 'index') . '.php';
    }

    $url = site_url($path);
    if ($params) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
    }
    return $url;
}

function product_url(string $slug): string
{
    return CLEAN_URLS ? site_url('product/' . rawurlencode($slug)) : site_url('public/product.php?slug=' . rawurlencode($slug));
}

function category_url(string $slug): string
{
    return CLEAN_URLS ? site_url('shop/' . rawurlencode($slug)) : site_url('public/shop.php?category=' . rawurlencode($slug));
}

function admin_url(string $path = 'index.php'): string
{
    return site_url('admin/' . ltrim($path, '/'));
}

function asset_url(string $path): string
{
    return site_url('public/assets/' . ltrim($path, '/'));
}

function admin_asset_url(string $path): string
{
    return site_url('admin/assets/' . ltrim($path, '/'));
}

function upload_url(string $path): string
{
    return site_url('uploads/' . ltrim($path, '/'));
}

function current_url(): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    return ($isHttps ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && hash_equals($_SESSION['_csrf_token'] ?? '', $token);
}

function require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
        http_response_code(419);
        die('Invalid session token. Please go back, refresh the page and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

function slugify(string $text): string
{
    $text = trim($text);
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim((string)$text, '-');
    return $text !== '' ? $text : 'item-' . time();
}

function unique_slug(string $table, string $slug, ?int $ignoreId = null): string
{
    $base = slugify($slug);
    $candidate = $base;
    $i = 2;
    $allowed = ['products', 'categories'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Invalid table for slug generation.');
    }

    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$candidate];
        if ($ignoreId) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $stmt = db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $base . '-' . $i++;
    }
}


function excerpt_text(string $text, int $length = 110): string
{
    $text = trim(strip_tags($text));
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $length, $text !== '' && mb_strlen($text) > $length ? '...' : '', 'UTF-8');
    }
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

function format_price($amount): string
{
    return 'Rs. ' . number_format((float)$amount, 2);
}

function gst_amount(float $subtotal, float $rate = GST_RATE): float
{
    return round($subtotal * ($rate / 100), 2);
}

function stock_label(int $qty): array
{
    if ($qty <= 0) {
        return ['Out of stock', 'danger'];
    }
    if ($qty <= LOW_STOCK_DEFAULT) {
        return ['Low stock: ' . $qty, 'warning'];
    }
    return ['In stock: ' . $qty, 'success'];
}

function get_setting(string $key, $default = null)
{
    try {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function set_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute([$key, $value]);
}

function get_seo(string $pageKey, string $fallbackTitle = '', string $fallbackDescription = ''): array
{
    $fallbackTitle = $fallbackTitle ?: SITE_NAME;
    $fallbackDescription = $fallbackDescription ?: SITE_TAGLINE;
    try {
        $stmt = db()->prepare('SELECT * FROM seo_settings WHERE page_key = ? LIMIT 1');
        $stmt->execute([$pageKey]);
        $row = $stmt->fetch();
        if ($row) {
            return [
                'title' => $row['title'] ?: $fallbackTitle,
                'description' => $row['meta_description'] ?: $fallbackDescription,
                'keywords' => $row['keywords'] ?? '',
                'og_image' => $row['og_image'] ?? '',
            ];
        }
    } catch (Throwable $e) {
        // Database may not be installed yet.
    }

    return [
        'title' => $fallbackTitle,
        'description' => $fallbackDescription,
        'keywords' => '',
        'og_image' => '',
    ];
}

function normalize_files_array(array $files): array
{
    $normalized = [];
    if (!isset($files['name'])) {
        return [];
    }
    if (!is_array($files['name'])) {
        return $files['name'] !== '' ? [$files] : [];
    }

    foreach ($files['name'] as $index => $name) {
        if ($name === '') {
            continue;
        }
        $normalized[] = [
            'name' => $files['name'][$index],
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }
    return $normalized;
}

function upload_product_image(array $file, string $nameSeed = 'product'): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Image size must be 5MB or less.');
    }

    $tmp = $file['tmp_name'];
    $info = @getimagesize($tmp);
    if (!$info) {
        throw new RuntimeException('Uploaded file is not a valid image.');
    }

    $mime = $info['mime'] ?? '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WebP and GIF images are allowed.');
    }

    $dir = UPLOAD_PATH . '/products';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $baseName = slugify(pathinfo($file['name'], PATHINFO_FILENAME) ?: $nameSeed);
    $targetName = $baseName . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));

    // Prefer WebP for SEO/speed when GD supports it; otherwise keep original extension.
    if (function_exists('imagewebp') && function_exists('imagecreatetruecolor')) {
        $image = null;
        if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
            $image = @imagecreatefromjpeg($tmp);
        } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
            $image = @imagecreatefrompng($tmp);
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
        } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($tmp);
        }

        if ($image) {
            $target = $dir . '/' . $targetName . '.webp';
            imagewebp($image, $target, 82);
            imagedestroy($image);
            @chmod($target, 0644);
            return 'products/' . basename($target);
        }
    }

    $ext = $allowed[$mime];
    $target = $dir . '/' . $targetName . '.' . $ext;
    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Could not save uploaded image.');
    }
    @chmod($target, 0644);
    return 'products/' . basename($target);
}

function upload_company_logo(array $file): ?string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The logo upload failed. Please try again.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Logo image size must be 5MB or less.');
    }

    $tmp = $file['tmp_name'] ?? '';
    $info = $tmp !== '' ? @getimagesize($tmp) : false;
    if (!$info) {
        throw new RuntimeException('Uploaded logo is not a valid image.');
    }
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $mime = $info['mime'] ?? '';
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WebP and GIF logos are allowed.');
    }

    $dir = UPLOAD_PATH . '/company-logos';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create the company logos upload directory.');
    }

    $baseName = slugify(pathinfo($file['name'] ?? '', PATHINFO_FILENAME) ?: 'company-logo');
    $filename = $baseName . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $target = $dir . '/' . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Could not save uploaded logo.');
    }
    @chmod($target, 0644);
    return 'company-logos/' . $filename;
}

function upload_homepage_slide(array $file): ?string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The slide upload failed. Please try again.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Slide image size must be 5MB or less.');
    }

    $tmp = $file['tmp_name'] ?? '';
    $info = $tmp !== '' ? @getimagesize($tmp) : false;
    if (!$info) {
        throw new RuntimeException('Uploaded slide is not a valid image.');
    }
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $mime = $info['mime'] ?? '';
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WebP and GIF slide images are allowed.');
    }

    $dir = UPLOAD_PATH . '/homepage-slides';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create the homepage slides upload directory.');
    }

    $baseName = slugify(pathinfo($file['name'] ?? '', PATHINFO_FILENAME) ?: 'homepage-slide');
    $filename = $baseName . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $target = $dir . '/' . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Could not save uploaded slide.');
    }
    @chmod($target, 0644);
    return 'homepage-slides/' . $filename;
}

function get_categories_flat(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM categories';
    if ($activeOnly) {
        $sql .= " WHERE status = 'active'";
    }
    $sql .= ' ORDER BY parent_id IS NOT NULL, parent_id, sort_order, name';
    return db()->query($sql)->fetchAll();
}

function category_depth(?int $categoryId): int
{
    if (!$categoryId) {
        return 0;
    }
    $depth = 0;
    $id = $categoryId;
    while ($id) {
        $stmt = db()->prepare('SELECT parent_id FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            break;
        }
        $depth++;
        $id = $row['parent_id'] ? (int)$row['parent_id'] : null;
        if ($depth > 10) {
            break;
        }
    }
    return $depth;
}

function render_category_options(array $categories, ?int $selected = null, ?int $parentId = null, int $level = 0, ?int $excludeId = null): string
{
    $html = '';
    foreach ($categories as $category) {
        if ((int)($category['parent_id'] ?? 0) !== (int)($parentId ?? 0)) {
            continue;
        }
        if ($excludeId && (int)$category['id'] === $excludeId) {
            continue;
        }
        $prefix = str_repeat('— ', $level);
        $html .= '<option value="' . (int)$category['id'] . '"' . ((int)$selected === (int)$category['id'] ? ' selected' : '') . '>' . e($prefix . $category['name']) . '</option>';
        $html .= render_category_options($categories, $selected, (int)$category['id'], $level + 1, $excludeId);
    }
    return $html;
}

function get_descendant_category_ids(int $categoryId): array
{
    $all = get_categories_flat(true);
    $ids = [$categoryId];
    $changed = true;
    while ($changed) {
        $changed = false;
        foreach ($all as $cat) {
            if ($cat['parent_id'] && in_array((int)$cat['parent_id'], $ids, true) && !in_array((int)$cat['id'], $ids, true)) {
                $ids[] = (int)$cat['id'];
                $changed = true;
            }
        }
    }
    return $ids;
}

function category_breadcrumbs(?int $categoryId): array
{
    if (!$categoryId) {
        return [];
    }
    $crumbs = [];
    $id = $categoryId;
    while ($id) {
        $stmt = db()->prepare('SELECT id, parent_id, name, slug FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            break;
        }
        array_unshift($crumbs, $row);
        $id = $row['parent_id'] ? (int)$row['parent_id'] : null;
    }
    return $crumbs;
}

function get_primary_image(?array $product): string
{
    if (!$product) {
        return asset_url('images/placeholder-product.svg');
    }
    $path = $product['primary_image'] ?? $product['image_path'] ?? '';
    return $path ? upload_url($path) : asset_url('images/placeholder-product.svg');
}

function cart_count(): int
{
    return array_sum($_SESSION['cart'] ?? []);
}

function cart_add(int $productId, int $qty = 1): void
{
    $qty = max(1, $qty);
    $stmt = db()->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        throw new RuntimeException('Product not found.');
    }
    if ((int)$product['stock_quantity'] <= 0) {
        throw new RuntimeException('This product is currently out of stock.');
    }
    $current = (int)($_SESSION['cart'][$productId] ?? 0);
    $_SESSION['cart'][$productId] = min((int)$product['stock_quantity'], $current + $qty);
}

function cart_update(array $quantities): void
{
    foreach ($quantities as $productId => $qty) {
        $productId = (int)$productId;
        $qty = max(0, (int)$qty);
        if ($qty === 0) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }
        $stmt = db()->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$productId]);
        $stock = (int)($stmt->fetchColumn() ?: 0);
        if ($stock <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = min($qty, $stock);
        }
    }
}

function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return [];
    }

    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT p.*, (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS primary_image
            FROM products p WHERE p.status = 'active' AND p.id IN ($placeholders)";
    $stmt = db()->prepare($sql);
    $stmt->execute($ids);
    $products = [];
    foreach ($stmt->fetchAll() as $product) {
        $products[(int)$product['id']] = $product;
    }

    $items = [];
    foreach ($cart as $id => $qty) {
        $id = (int)$id;
        if (!isset($products[$id])) {
            unset($_SESSION['cart'][$id]);
            continue;
        }
        $product = $products[$id];
        $qty = min((int)$qty, max(0, (int)$product['stock_quantity']));
        if ($qty <= 0) {
            unset($_SESSION['cart'][$id]);
            continue;
        }
        $_SESSION['cart'][$id] = $qty;
        $lineSubtotal = round((float)$product['price'] * $qty, 2);
        $rate = (float)($product['gst_rate'] ?: GST_RATE);
        $lineGst = gst_amount($lineSubtotal, $rate);
        $items[] = [
            'product' => $product,
            'qty' => $qty,
            'subtotal' => $lineSubtotal,
            'gst' => $lineGst,
            'total' => $lineSubtotal + $lineGst,
        ];
    }
    return $items;
}

function cart_totals(array $items): array
{
    $subtotal = 0;
    $gst = 0;
    foreach ($items as $item) {
        $subtotal += $item['subtotal'];
        $gst += $item['gst'];
    }
    return [
        'subtotal' => round($subtotal, 2),
        'gst' => round($gst, 2),
        'grand' => round($subtotal + $gst, 2),
    ];
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

function generate_sitemap_file(): int
{
    $urls = [];
    $pages = ['', 'shop', 'about', 'contact', 'login', 'signup'];
    foreach ($pages as $page) {
        $urls[] = ['loc' => site_url($page), 'priority' => $page === '' ? '1.0' : '0.7', 'changefreq' => $page === '' ? 'weekly' : 'monthly'];
    }

    $cats = db()->query("SELECT slug, updated_at FROM categories WHERE status = 'active'")->fetchAll();
    foreach ($cats as $cat) {
        $urls[] = ['loc' => category_url($cat['slug']), 'lastmod' => substr($cat['updated_at'] ?: date('Y-m-d'), 0, 10), 'priority' => '0.8', 'changefreq' => 'weekly'];
    }

    $products = db()->query("SELECT slug, updated_at FROM products WHERE status = 'active'")->fetchAll();
    foreach ($products as $product) {
        $urls[] = ['loc' => product_url($product['slug']), 'lastmod' => substr($product['updated_at'] ?: date('Y-m-d'), 0, 10), 'priority' => '0.9', 'changefreq' => 'weekly'];
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    foreach ($urls as $url) {
        $xml .= "  <url>\n";
        $xml .= '    <loc>' . e($url['loc']) . '</loc>' . PHP_EOL;
        if (!empty($url['lastmod'])) {
            $xml .= '    <lastmod>' . e($url['lastmod']) . '</lastmod>' . PHP_EOL;
        }
        $xml .= '    <changefreq>' . e($url['changefreq']) . '</changefreq>' . PHP_EOL;
        $xml .= '    <priority>' . e($url['priority']) . '</priority>' . PHP_EOL;
        $xml .= "  </url>\n";
    }
    $xml .= '</urlset>' . PHP_EOL;
    file_put_contents(ROOT_PATH . '/sitemap.xml', $xml);
    return count($urls);
}

function json_ld(array $data): string
{
    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
}
