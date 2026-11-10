<?php
require_once __DIR__ . '/../includes/functions.php';
$pageKey = 'shop';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['post_action'] ?? '') === 'add_to_cart') {
    require_csrf();
    try {
        cart_add((int)$_POST['product_id'], (int)($_POST['qty'] ?? 1));
        flash('success', 'Product added to cart.');
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }
    redirect($_SERVER['HTTP_REFERER'] ?? frontend_url('shop'));
}

$categories = get_categories_flat(true);
$categorySlug = trim($_GET['category'] ?? '');
$selectedCategory = null;
if ($categorySlug !== '') {
    $stmt = db()->prepare("SELECT * FROM categories WHERE slug = ? AND status='active' LIMIT 1");
    $stmt->execute([$categorySlug]);
    $selectedCategory = $stmt->fetch() ?: null;
}

$q = trim($_GET['q'] ?? '');
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$stock = $_GET['stock'] ?? '';
$sort = $_GET['sort'] ?? 'latest';
$view = $_GET['view'] ?? 'grid';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$where = ["p.status='active'"];
$params = [];

if ($selectedCategory) {
    $ids = get_descendant_category_ids((int)$selectedCategory['id']);
    $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
    foreach ($ids as $id) { $params[] = $id; }
}
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.short_description LIKE ?)';
    $params[] = "%{$q}%"; $params[] = "%{$q}%"; $params[] = "%{$q}%";
}
if ($minPrice !== '' && is_numeric($minPrice)) {
    $where[] = 'p.price >= ?';
    $params[] = (float)$minPrice;
}
if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $where[] = 'p.price <= ?';
    $params[] = (float)$maxPrice;
}
if ($stock === 'available') {
    $where[] = 'p.stock_quantity > 0';
} elseif ($stock === 'low') {
    $where[] = 'p.stock_quantity > 0 AND p.stock_quantity <= p.low_stock_threshold';
}

$orderBy = 'p.id DESC';
if ($sort === 'price_asc') $orderBy = 'p.price ASC';
if ($sort === 'price_desc') $orderBy = 'p.price DESC';
if ($sort === 'name') $orderBy = 'p.name ASC';

$countSql = 'SELECT COUNT(*) FROM products p WHERE ' . implode(' AND ', $where);
$stmt = db()->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.*, c.name AS category_name, (SELECT image_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS primary_image
        FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE " . implode(' AND ', $where) . " ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

if ($selectedCategory) {
    $seo = [
        'title' => ($selectedCategory['meta_title'] ?: $selectedCategory['name'] . ' | BMJ Pyramids LLP'),
        'description' => ($selectedCategory['meta_description'] ?: ($selectedCategory['description'] ?: 'Browse ' . $selectedCategory['name'] . ' products from BMJ Pyramids LLP.')),
        'keywords' => '', 'og_image' => ''
    ];
    $canonicalUrl = category_url($selectedCategory['slug']);
} else {
    $seo = get_seo('shop', 'Shop Industrial Products | BMJ Pyramids LLP', 'Browse industrial tools, bearings and B2B products with stock tracking and GST quote requests.');
    $canonicalUrl = frontend_url('shop');
}

include __DIR__ . '/_header.php';
?>
<header class="page-header">
  <div class="container">
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-2"><li class="breadcrumb-item"><a href="<?= e(frontend_url('index')) ?>">Home</a></li><li class="breadcrumb-item active">Shop</li></ol></nav>
    <h1 class="display-6 fw-bold mb-0"><?= e($selectedCategory['name'] ?? 'Industrial Product Catalogue') ?></h1>
    <p class="mb-0 mt-2 opacity-75"><?= e($selectedCategory['description'] ?? 'Filter products by category, price and live stock availability.') ?></p>
  </div>
</header>
<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-3">
        <form class="filter-box" method="get" action="<?= e(frontend_url('shop')) ?>">
          <h2 class="h5 fw-bold mb-3">Filters</h2>
          <div class="mb-3"><label class="form-label">Search</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="Product or SKU"></div>
          <div class="mb-3"><label class="form-label">Category</label><select name="category" class="form-select"><option value="">All categories</option><?php foreach ($categories as $cat): ?><?php $depth = count(category_breadcrumbs((int)$cat['id'])) - 1; ?><option value="<?= e($cat['slug']) ?>" <?= ($selectedCategory && $selectedCategory['id']==$cat['id'])?'selected':'' ?>><?= e(str_repeat('— ', max(0,$depth)) . $cat['name']) ?></option><?php endforeach; ?></select></div>
          <div class="row g-2"><div class="col-6"><label class="form-label">Min Price</label><input type="number" step="0.01" name="min_price" class="form-control" value="<?= e($minPrice) ?>"></div><div class="col-6"><label class="form-label">Max Price</label><input type="number" step="0.01" name="max_price" class="form-control" value="<?= e($maxPrice) ?>"></div></div>
          <div class="mb-3 mt-3"><label class="form-label">Stock</label><select name="stock" class="form-select"><option value="">Any</option><option value="available" <?= $stock==='available'?'selected':'' ?>>Available</option><option value="low" <?= $stock==='low'?'selected':'' ?>>Low stock</option></select></div>
          <div class="mb-3"><label class="form-label">Sort</label><select name="sort" class="form-select"><option value="latest" <?= $sort==='latest'?'selected':'' ?>>Latest</option><option value="name" <?= $sort==='name'?'selected':'' ?>>Name</option><option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price low to high</option><option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price high to low</option></select></div>
          <input type="hidden" name="view" value="<?= e($view) ?>">
          <button class="btn btn-primary w-100">Apply Filters</button><a href="<?= e(frontend_url('shop')) ?>" class="btn btn-link w-100 mt-2">Reset</a>
        </form>
      </div>
      <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3"><p class="mb-0 text-muted">Showing <?= count($products) ?> of <?= $total ?> products</p><div class="btn-group"><a class="btn btn-sm <?= $view==='grid'?'btn-primary':'btn-outline-primary' ?>" href="?<?= e(http_build_query(array_merge($_GET, ['view'=>'grid']))) ?>">Grid</a><a class="btn btn-sm <?= $view==='list'?'btn-primary':'btn-outline-primary' ?>" href="?<?= e(http_build_query(array_merge($_GET, ['view'=>'list']))) ?>">List</a></div></div>
        <?php if ($view === 'list'): ?>
          <div class="vstack gap-3">
            <?php foreach ($products as $p): ?><?php [$stockText, $stockClass] = stock_label((int)$p['stock_quantity']); ?><div class="soft-card p-3"><div class="row g-3 align-items-center"><div class="col-md-3"><img class="w-100 rounded-3" style="height:150px;object-fit:cover" src="<?= e(get_primary_image($p)) ?>" alt="<?= e($p['name']) ?>"></div><div class="col-md-6"><div class="sku"><?= e($p['sku']) ?></div><h2 class="h5 fw-bold"><a href="<?= e(product_url($p['slug'])) ?>" class="text-dark"><?= e($p['name']) ?></a></h2><p class="text-muted mb-1"><?= e($p['short_description']) ?></p><span class="badge bg-<?= e($stockClass) ?>"><?= e($stockText) ?></span></div><div class="col-md-3 text-md-end"><div class="price mb-2"><?= e(format_price($p['price'])) ?></div><small class="text-muted">+<?= GST_RATE ?>% GST</small><form method="post" class="mt-3"><?= csrf_field() ?><input type="hidden" name="post_action" value="add_to_cart"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"><button class="btn btn-primary" <?= (int)$p['stock_quantity']<=0?'disabled':'' ?>>Request Quote</button></form></div></div></div><?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($products as $p): ?><?php [$stockText, $stockClass] = stock_label((int)$p['stock_quantity']); ?><div class="col-md-6 col-xl-4"><div class="soft-card product-card"><a href="<?= e(product_url($p['slug'])) ?>"><img src="<?= e(get_primary_image($p)) ?>" alt="<?= e($p['name']) ?>"></a><div class="p-3"><div class="sku"><?= e($p['sku']) ?></div><h2 class="h6 fw-bold"><a class="text-dark" href="<?= e(product_url($p['slug'])) ?>"><?= e($p['name']) ?></a></h2><p class="text-muted small mb-2"><?= e(excerpt_text($p['short_description'] ?? '', 80)) ?></p><div class="d-flex justify-content-between align-items-center mb-3"><span class="price"><?= e(format_price($p['price'])) ?></span><span class="badge bg-<?= e($stockClass) ?> badge-stock"><?= e($stockText) ?></span></div><form method="post" class="d-grid"><?= csrf_field() ?><input type="hidden" name="post_action" value="add_to_cart"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"><button class="btn btn-outline-primary" <?= (int)$p['stock_quantity']<=0?'disabled':'' ?>>Add to Quote Cart</button></form></div></div></div><?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if (!$products): ?><div class="alert alert-info">No products match your filters.</div><?php endif; ?>
        <?php if ($totalPages > 1): ?><nav class="mt-4"><ul class="pagination"><?php for ($i=1; $i<=$totalPages; $i++): ?><li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="?<?= e(http_build_query(array_merge($_GET, ['page'=>$i]))) ?>"><?= $i ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
