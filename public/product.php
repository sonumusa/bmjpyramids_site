<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['post_action'] ?? '') === 'add_to_cart') {
    require_csrf();
    try {
        cart_add((int)$_POST['product_id'], (int)($_POST['qty'] ?? 1));
        flash('success', 'Product added to quote cart.');
        redirect(frontend_url('cart'));
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
        redirect($_SERVER['HTTP_REFERER'] ?? frontend_url('shop'));
    }
}

function product_page_log(Throwable $e): void
{
    if (!is_dir(LOG_PATH)) {
        @mkdir(LOG_PATH, 0755, true);
    }
    @file_put_contents(LOG_PATH . '/product-page-error.log', '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
}

$slug = trim($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);
$product = null;
$images = [];
$breadcrumbs = [];
$related = [];
$mainImage = asset_url('images/placeholder-product.svg');
$extraSchema = [];

try {
    if ($slug !== '') {
        $stmt = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.status = 'active' LIMIT 1");
        $stmt->execute([$slug]);
    } elseif ($id > 0) {
        $stmt = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.status = 'active' LIMIT 1");
        $stmt->execute([$id]);
    } else {
        $stmt = null;
    }

    $product = $stmt ? ($stmt->fetch() ?: null) : null;

    if ($product) {
        try {
            $stmt = db()->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order, id');
            $stmt->execute([(int)$product['id']]);
            $images = $stmt->fetchAll();
        } catch (Throwable $imageError) {
            product_page_log($imageError);
            $images = [];
        }

        $mainImage = $images ? upload_url($images[0]['image_path']) : asset_url('images/placeholder-product.svg');

        try {
            $breadcrumbs = category_breadcrumbs(!empty($product['category_id']) ? (int)$product['category_id'] : null);
        } catch (Throwable $breadcrumbError) {
            product_page_log($breadcrumbError);
            $breadcrumbs = [];
        }

        try {
            if (!empty($product['category_id'])) {
                $relatedStmt = db()->prepare("SELECT p.*, (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS primary_image FROM products p WHERE p.status = 'active' AND p.category_id = ? AND p.id <> ? ORDER BY p.id DESC LIMIT 4");
                $relatedStmt->execute([(int)$product['category_id'], (int)$product['id']]);
            } else {
                $relatedStmt = db()->prepare("SELECT p.*, (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS primary_image FROM products p WHERE p.status = 'active' AND p.category_id IS NULL AND p.id <> ? ORDER BY p.id DESC LIMIT 4");
                $relatedStmt->execute([(int)$product['id']]);
            }
            $related = $relatedStmt->fetchAll();
        } catch (Throwable $relatedError) {
            product_page_log($relatedError);
            $related = [];
        }
    }
} catch (Throwable $e) {
    product_page_log($e);
    http_response_code(500);
    $pageKey = 'product_default';
    $seo = ['title' => 'Product Page Error | BMJ Pyramids LLP', 'description' => 'The product page could not be loaded.', 'keywords' => '', 'og_image' => ''];
    include __DIR__ . '/_header.php';
    ?>
    <div class="container py-5">
      <div class="alert alert-danger">
        <h1 class="h4">Product page error</h1>
        <p class="mb-2">The product detail page could not load. The error has been logged in <code>logs/product-page-error.log</code>.</p>
        <a href="<?= e(frontend_url('shop')) ?>" class="btn btn-primary">Return to shop</a>
      </div>
    </div>
    <?php
    include __DIR__ . '/_footer.php';
    exit;
}

if (!$product) {
    http_response_code(404);
    $pageKey = 'product_default';
    $seo = ['title' => 'Product Not Found | BMJ Pyramids LLP', 'description' => 'The product you requested is not available.', 'keywords' => '', 'og_image' => ''];
    include __DIR__ . '/_header.php';
    echo '<div class="container py-5"><div class="alert alert-warning">Product not found. <a href="' . e(frontend_url('shop')) . '">Return to shop</a>.</div></div>';
    include __DIR__ . '/_footer.php';
    exit;
}

$descriptionText = trim(strip_tags((string)(($product['short_description'] ?? '') ?: ($product['description'] ?? ''))));
if ($descriptionText === '') {
    $descriptionText = 'Request quote for ' . $product['name'] . ' from BMJ Pyramids LLP.';
}

$seo = [
    'title' => ($product['meta_title'] ?? '') ?: $product['name'] . ' | BMJ Pyramids LLP',
    'description' => ($product['meta_description'] ?? '') ?: $descriptionText,
    'keywords' => $product['keywords'] ?? '',
    'og_image' => $mainImage,
];
$pageKey = 'product_default';
$canonicalUrl = product_url($product['slug'] ?? ($slug ?: 'product-' . (int)$product['id']));
$availability = (int)($product['stock_quantity'] ?? 0) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';

$extraSchema[] = json_ld([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'sku' => $product['sku'] ?? '',
    'image' => [$mainImage],
    'description' => $descriptionText,
    'brand' => ['@type' => 'Brand', 'name' => 'BMJ Pyramids LLP'],
    'offers' => [
        '@type' => 'Offer',
        'url' => $canonicalUrl,
        'priceCurrency' => 'PKR',
        'price' => (float)$product['price'],
        'availability' => $availability,
    ],
]);

if ($breadcrumbs) {
    $items = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => frontend_url('index')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Shop', 'item' => frontend_url('shop')],
    ];
    $pos = 3;
    foreach ($breadcrumbs as $crumb) {
        $items[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $crumb['name'], 'item' => category_url($crumb['slug'])];
    }
    $items[] = ['@type' => 'ListItem', 'position' => $pos, 'name' => $product['name'], 'item' => $canonicalUrl];
    $extraSchema[] = json_ld(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items]);
}

include __DIR__ . '/_header.php';
list($stockText, $stockClass) = stock_label((int)$product['stock_quantity']);
$gstRate = (float)(($product['gst_rate'] ?? 0) ?: GST_RATE);
$gst = gst_amount((float)$product['price'], $gstRate);
?>
<header class="page-header">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="<?= e(frontend_url('index')) ?>">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= e(frontend_url('shop')) ?>">Shop</a></li>
        <?php foreach ($breadcrumbs as $crumb): ?>
          <li class="breadcrumb-item"><a href="<?= e(category_url($crumb['slug'])) ?>"><?= e($crumb['name']) ?></a></li>
        <?php endforeach; ?>
        <li class="breadcrumb-item active"><?= e($product['name']) ?></li>
      </ol>
    </nav>
    <h1 class="h2 fw-bold mb-0"><?= e($product['name']) ?></h1>
  </div>
</header>
<section class="py-5">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-6">
        <div class="gallery-main"><img data-gallery-main src="<?= e($mainImage) ?>" alt="<?= e($product['name']) ?>"></div>
        <div class="d-flex gap-2 flex-wrap mt-3">
          <?php if ($images): ?>
            <?php foreach ($images as $idx => $img): ?>
              <img data-thumb class="thumb-img <?= $idx === 0 ? 'active' : '' ?>" src="<?= e(upload_url($img['image_path'])) ?>" alt="<?= e($img['alt_text'] ?: $product['name']) ?>">
            <?php endforeach; ?>
          <?php else: ?>
            <img data-thumb class="thumb-img active" src="<?= e(asset_url('images/placeholder-product.svg')) ?>" alt="Product placeholder">
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="sku mb-2"><?= e($product['sku'] ?? '') ?></div>
        <h2 class="section-title h1"><?= e($product['name']) ?></h2>
        <p class="lead text-muted"><?= e($product['short_description'] ?? '') ?></p>
        <div class="d-flex align-items-center gap-3 my-4"><span class="display-6 fw-bold"><?= e(format_price($product['price'])) ?></span><span class="badge bg-<?= e($stockClass) ?> badge-stock fs-6"><?= e($stockText) ?></span></div>
        <div class="soft-card p-3 mb-4">
          <div class="d-flex justify-content-between"><span>Price before Sales Tax/GST</span><strong><?= e(format_price($product['price'])) ?></strong></div>
          <div class="d-flex justify-content-between"><span>Optional Sales Tax/GST (<?= GST_RATE ?>%)</span><strong><?= e(format_price($gst)) ?></strong></div>
          <hr>
          <div class="d-flex justify-content-between fs-5"><span>Quote total per unit if tax applied</span><strong><?= e(format_price((float)$product['price'] + $gst)) ?></strong></div>
          <small class="text-muted">Sales Tax/GST can be checked or unchecked at checkout. Final quote/invoice will be confirmed by BMJ Pyramids LLP.</small>
        </div>
        <form method="post" class="row g-2 align-items-end mb-4">
          <?= csrf_field() ?>
          <input type="hidden" name="post_action" value="add_to_cart"><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
          <div class="col-4"><label class="form-label">Quantity</label><input type="number" min="1" max="<?= max(1, (int)$product['stock_quantity']) ?>" name="qty" class="form-control" value="1"></div>
          <div class="col-8"><button class="btn btn-primary btn-lg w-100" <?= (int)$product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>Add to Quote Cart</button></div>
        </form>
        <div class="d-flex gap-2 flex-wrap"><a href="<?= e(frontend_url('contact', ['product' => $product['slug']])) ?>" class="btn btn-outline-primary">Ask a Question</a><a href="<?= e(frontend_url('shop')) ?>" class="btn btn-link">Continue Browsing</a></div>
      </div>
    </div>
    <div class="row mt-5"><div class="col-lg-8"><h2 class="h4 fw-bold">Product Details</h2><div class="text-muted"><?= $product['description'] ?: '<p>No detailed description added yet.</p>' ?></div></div></div>
  </div>
</section>
<?php if ($related): ?>
<section class="py-5 bg-light">
  <div class="container">
    <h2 class="section-title mb-4">Related products</h2>
    <div class="row g-4">
      <?php foreach ($related as $p): ?>
        <?php list($sText, $sClass) = stock_label((int)$p['stock_quantity']); ?>
        <div class="col-md-6 col-lg-3"><div class="soft-card product-card"><a href="<?= e(product_url($p['slug'])) ?>"><img src="<?= e(get_primary_image($p)) ?>" alt="<?= e($p['name']) ?>"></a><div class="p-3"><div class="sku"><?= e($p['sku']) ?></div><h3 class="h6 fw-bold"><a class="text-dark" href="<?= e(product_url($p['slug'])) ?>"><?= e($p['name']) ?></a></h3><div class="d-flex justify-content-between"><span class="price"><?= e(format_price($p['price'])) ?></span><span class="badge bg-<?= e($sClass) ?>"><?= e($sText) ?></span></div></div></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/_footer.php'; ?>
