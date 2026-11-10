<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_capability('products_view');
$pageTitle = 'Products';
$canWrite = admin_can('products_write');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (!$canWrite) {
        http_response_code(403);
        die('Read-only role cannot modify products.');
    }

    $postAction = $_POST['post_action'] ?? 'save_product';

    try {
        if ($postAction === 'delete_product') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = db()->prepare('SELECT image_path FROM product_images WHERE product_id = ?');
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll() as $img) {
                @unlink(UPLOAD_PATH . '/' . $img['image_path']);
            }
            db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            flash('success', 'Product deleted.');
            redirect(admin_url('products.php'));
        }

        if ($postAction === 'delete_image') {
            $imageId = (int)($_POST['image_id'] ?? 0);
            $productId = (int)($_POST['product_id'] ?? 0);
            $stmt = db()->prepare('SELECT image_path FROM product_images WHERE id = ? AND product_id = ?');
            $stmt->execute([$imageId, $productId]);
            $img = $stmt->fetch();
            if ($img) {
                @unlink(UPLOAD_PATH . '/' . $img['image_path']);
                db()->prepare('DELETE FROM product_images WHERE id = ?')->execute([$imageId]);
                flash('success', 'Image removed.');
            }
            redirect(admin_url('products.php?action=edit&id=' . $productId));
        }

        if ($postAction === 'set_primary') {
            $imageId = (int)($_POST['image_id'] ?? 0);
            $productId = (int)($_POST['product_id'] ?? 0);
            db()->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?')->execute([$productId]);
            db()->prepare('UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?')->execute([$imageId, $productId]);
            flash('success', 'Primary image updated.');
            redirect(admin_url('products.php?action=edit&id=' . $productId));
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            throw new RuntimeException('Product name is required.');
        }
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $slug = unique_slug('products', trim($_POST['slug'] ?? '') ?: $name, $id ?: null);
        $sku = trim($_POST['sku'] ?? '');
        $price = max(0, (float)($_POST['price'] ?? 0));
        $stock = max(0, (int)($_POST['stock_quantity'] ?? 0));
        $lowStock = max(0, (int)($_POST['low_stock_threshold'] ?? LOW_STOCK_DEFAULT));
        $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
        $featured = isset($_POST['featured']) ? 1 : 0;
        $shortDescription = trim($_POST['short_description'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');
        $keywords = trim($_POST['keywords'] ?? '');

        if ($id) {
            $stmt = db()->prepare('UPDATE products SET category_id=?, sku=?, name=?, slug=?, short_description=?, description=?, price=?, gst_rate=?, stock_quantity=?, low_stock_threshold=?, status=?, featured=?, meta_title=?, meta_description=?, keywords=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$categoryId, $sku, $name, $slug, $shortDescription, $description, $price, GST_RATE, $stock, $lowStock, $status, $featured, $metaTitle, $metaDescription, $keywords, $id]);
            $productId = $id;
        } else {
            $stmt = db()->prepare('INSERT INTO products (category_id, sku, name, slug, short_description, description, price, gst_rate, stock_quantity, low_stock_threshold, status, featured, meta_title, meta_description, keywords, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
            $stmt->execute([$categoryId, $sku, $name, $slug, $shortDescription, $description, $price, GST_RATE, $stock, $lowStock, $status, $featured, $metaTitle, $metaDescription, $keywords, admin_current_user()['id'] ?? null]);
            $productId = (int)db()->lastInsertId();
        }

        $existingImagesStmt = db()->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ?');
        $existingImagesStmt->execute([$productId]);
        $existingCount = (int)$existingImagesStmt->fetchColumn();
        $sortOrder = $existingCount;
        foreach (normalize_files_array($_FILES['images'] ?? []) as $index => $file) {
            $path = upload_product_image($file, $name);
            if ($path) {
                $isPrimary = ($existingCount === 0 && $index === 0) ? 1 : 0;
                db()->prepare('INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary) VALUES (?,?,?,?,?)')->execute([$productId, $path, $name, $sortOrder++, $isPrimary]);
            }
        }

        flash('success', 'Product saved successfully.');
        redirect(admin_url('products.php?action=edit&id=' . $productId));
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
        redirect(admin_url('products.php'));
    }
}

$action = $_GET['action'] ?? 'list';
$categories = get_categories_flat(false);

if ($action === 'add' || $action === 'edit') {
    $product = [
        'id' => 0, 'category_id' => '', 'sku' => '', 'name' => '', 'slug' => '', 'short_description' => '', 'description' => '',
        'price' => '0.00', 'stock_quantity' => 0, 'low_stock_threshold' => LOW_STOCK_DEFAULT, 'status' => 'active', 'featured' => 0,
        'meta_title' => '', 'meta_description' => '', 'keywords' => ''
    ];
    $images = [];
    if ($action === 'edit') {
        $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([(int)($_GET['id'] ?? 0)]);
        $product = $stmt->fetch();
        if (!$product) {
            flash('danger', 'Product not found.');
            redirect(admin_url('products.php'));
        }
        $stmt = db()->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order, id');
        $stmt->execute([(int)$product['id']]);
        $images = $stmt->fetchAll();
    }
    include __DIR__ . '/_header.php';
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div><a href="<?= e(admin_url('products.php')) ?>" class="btn btn-sm btn-outline-secondary">← Back to products</a></div>
      <?php if (!$canWrite): ?><span class="readonly-overlay">Viewer mode: product editing disabled.</span><?php endif; ?>
    </div>
    <form method="post" enctype="multipart/form-data" class="card-soft p-4" onsubmit="if(window.syncEditor){syncEditor();}">
      <?= csrf_field() ?>
      <input type="hidden" name="post_action" value="save_product">
      <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
      <div class="row g-4">
        <div class="col-lg-8">
          <h2 class="h5 fw-bold mb-3"><?= $action === 'add' ? 'Add Product' : 'Edit Product' ?></h2>
          <div class="row g-3">
            <div class="col-md-8"><label class="form-label">Product Name *</label><input name="name" class="form-control" required value="<?= e($product['name']) ?>" <?= !$canWrite ? 'disabled' : '' ?>></div>
            <div class="col-md-4"><label class="form-label">SKU</label><input name="sku" class="form-control" value="<?= e($product['sku']) ?>" <?= !$canWrite ? 'disabled' : '' ?>></div>
            <div class="col-md-6"><label class="form-label">Slug</label><input name="slug" class="form-control" value="<?= e($product['slug']) ?>" placeholder="auto-generated" <?= !$canWrite ? 'disabled' : '' ?>></div>
            <div class="col-md-6"><label class="form-label">Category</label><select name="category_id" class="form-select" <?= !$canWrite ? 'disabled' : '' ?>><option value="">-- Select category --</option><?= render_category_options($categories, $product['category_id'] ? (int)$product['category_id'] : null) ?></select></div>
            <div class="col-md-4"><label class="form-label">Price before GST</label><input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= e($product['price']) ?>" <?= !$canWrite ? 'disabled' : '' ?>></div>
            <div class="col-md-4"><label class="form-label">Stock Quantity</label><input type="number" min="0" name="stock_quantity" class="form-control" value="<?= (int)$product['stock_quantity'] ?>" <?= !$canWrite ? 'disabled' : '' ?>></div>
            <div class="col-md-4"><label class="form-label">Low Stock Alert</label><input type="number" min="0" name="low_stock_threshold" class="form-control" value="<?= (int)$product['low_stock_threshold'] ?>" <?= !$canWrite ? 'disabled' : '' ?>></div>
            <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select" <?= !$canWrite ? 'disabled' : '' ?>><option value="active" <?= $product['status']==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= $product['status']==='inactive'?'selected':'' ?>>Inactive</option></select></div>
            <div class="col-md-6 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="featured" id="featured" <?= $product['featured'] ? 'checked' : '' ?> <?= !$canWrite ? 'disabled' : '' ?>><label class="form-check-label" for="featured">Featured on home page</label></div></div>
            <div class="col-12"><label class="form-label">Short Description</label><textarea name="short_description" class="form-control" rows="3" <?= !$canWrite ? 'disabled' : '' ?>><?= e($product['short_description']) ?></textarea></div>
            <div class="col-12">
              <label class="form-label">Product Description</label>
              <div class="border rounded-3 bg-white">
                <div class="p-2 border-bottom bg-light">
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.execCommand('bold')"><b>B</b></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.execCommand('italic')"><i>I</i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.execCommand('insertUnorderedList')">• List</button>
                </div>
                <div id="editor" contenteditable="<?= $canWrite ? 'true' : 'false' ?>" class="p-3" style="min-height:180px"><?= $product['description'] ?></div>
              </div>
              <textarea name="description" id="description" class="d-none"><?= e($product['description']) ?></textarea>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="mb-4">
            <h3 class="h6 fw-bold">Product Images</h3>
            <div data-drop-zone class="border border-2 border-dashed rounded-4 p-4 text-center bg-light" style="cursor:pointer">
              <strong>Click or drop images</strong><br><span class="text-muted small">JPG, PNG, WebP, GIF. Converted to WebP when supported.</span>
              <input data-file-input type="file" name="images[]" accept="image/*" multiple class="d-none" <?= !$canWrite ? 'disabled' : '' ?>>
            </div>
            <?php if ($images): ?><div class="mt-3"><?php foreach ($images as $img): ?><span class="img-chip <?= $img['is_primary'] ? 'primary' : '' ?>"><img src="<?= e(upload_url($img['image_path'])) ?>" alt="<?= e($img['alt_text']) ?>"><br><?php if ($canWrite): ?><button type="button" class="btn btn-sm btn-link p-0" onclick="submitImageAction('set_primary', <?= (int)$img['id'] ?>, <?= (int)$product['id'] ?>)">Primary</button><button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="if(confirm('Delete this image?')) submitImageAction('delete_image', <?= (int)$img['id'] ?>, <?= (int)$product['id'] ?>)">Delete</button><?php endif; ?></span><?php endforeach; ?></div><?php endif; ?>
          </div>
          <div class="mb-4">
            <h3 class="h6 fw-bold">SEO Fields</h3>
            <label class="form-label">Meta Title</label><input name="meta_title" class="form-control mb-2" value="<?= e($product['meta_title']) ?>" maxlength="160" <?= !$canWrite ? 'disabled' : '' ?>>
            <label class="form-label">Meta Description</label><textarea name="meta_description" class="form-control mb-2" rows="4" maxlength="255" <?= !$canWrite ? 'disabled' : '' ?>><?= e($product['meta_description']) ?></textarea>
            <label class="form-label">Keywords</label><input name="keywords" class="form-control" value="<?= e($product['keywords']) ?>" <?= !$canWrite ? 'disabled' : '' ?>>
          </div>
          <?php if ($canWrite): ?><button class="btn btn-primary w-100 py-2">Save Product</button><?php endif; ?>
        </div>
      </div>
    </form>
    <?php if ($canWrite): ?>
    <form method="post" id="image-action-form" class="d-none">
      <?= csrf_field() ?>
      <input type="hidden" name="post_action" id="image_post_action">
      <input type="hidden" name="image_id" id="image_id">
      <input type="hidden" name="product_id" id="image_product_id">
    </form>
    <?php endif; ?>
    <script>
      function syncEditor(){document.getElementById('description').value=document.getElementById('editor').innerHTML;}
      function submitImageAction(action, imageId, productId){
        document.getElementById('image_post_action').value = action;
        document.getElementById('image_id').value = imageId;
        document.getElementById('image_product_id').value = productId;
        document.getElementById('image-action-form').submit();
      }
    </script>
    <?php
    include __DIR__ . '/_footer.php';
    exit;
}

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$categoryId = (int)($_GET['category_id'] ?? 0);
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.slug LIKE ?)';
    $params[] = "%{$q}%"; $params[] = "%{$q}%"; $params[] = "%{$q}%";
}
if (in_array($status, ['active', 'inactive'], true)) {
    $where[] = 'p.status = ?';
    $params[] = $status;
}
if ($categoryId) {
    $where[] = 'p.category_id = ?';
    $params[] = $categoryId;
}
$sql = "SELECT p.*, c.name AS category_name, (SELECT image_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS primary_image FROM products p LEFT JOIN categories c ON c.id=p.category_id";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY p.id DESC LIMIT 300';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
include __DIR__ . '/_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <form class="d-flex gap-2 flex-wrap" method="get">
    <input class="form-control" style="width:230px" name="q" value="<?= e($q) ?>" placeholder="Search name or SKU">
    <select name="category_id" class="form-select" style="width:230px"><option value="0">All categories</option><?= render_category_options($categories, $categoryId ?: null) ?></select>
    <select name="status" class="form-select" style="width:150px"><option value="">All status</option><option value="active" <?= $status==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option></select>
    <button class="btn btn-outline-primary">Filter</button>
  </form>
  <?php if ($canWrite): ?><a href="<?= e(admin_url('products.php?action=add')) ?>" class="btn btn-primary">+ Add Product</a><?php endif; ?>
</div>
<div class="card-soft p-3">
  <div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>Image</th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <?php [$stockText, $stockClass] = stock_label((int)$p['stock_quantity']); ?>
      <tr>
        <td><img class="thumb" src="<?= e(get_primary_image($p)) ?>" alt=""></td>
        <td><strong><?= e($p['name']) ?></strong><br><small class="text-muted"><?= e($p['sku']) ?> · <?= e($p['slug']) ?></small></td>
        <td><?= e($p['category_name'] ?? '—') ?></td>
        <td><?= e(format_price($p['price'])) ?><br><small class="text-muted">+<?= GST_RATE ?>% GST</small></td>
        <td><span class="badge bg-<?= e($stockClass) ?>"><?= e($stockText) ?></span></td>
        <td><span class="badge bg-<?= $p['status']==='active'?'success':'secondary' ?>"><?= e($p['status']) ?></span><?= $p['featured'] ? '<br><span class="badge bg-info text-dark mt-1">Featured</span>' : '' ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="<?= e(admin_url('products.php?action=edit&id=' . $p['id'])) ?>"><?= $canWrite ? 'Edit' : 'View' ?></a>
          <?php if ($canWrite): ?><form method="post" class="d-inline" data-confirm="Delete this product?"><?= csrf_field() ?><input type="hidden" name="post_action" value="delete_product"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$products): ?><tr><td colspan="7" class="text-center text-muted py-5">No products found.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
