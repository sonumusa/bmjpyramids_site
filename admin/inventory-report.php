<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_capability('inventory_view');
$pageTitle = 'Inventory Report';

$q = trim($_GET['q'] ?? '');
$stockFilter = $_GET['stock'] ?? '';
$categoryId = (int)($_GET['category_id'] ?? 0);
$export = ($_GET['export'] ?? '') === 'csv';

$categories = get_categories_flat(false);
$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}

if ($categoryId > 0) {
    $ids = get_descendant_category_ids($categoryId);
    $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
    foreach ($ids as $id) {
        $params[] = $id;
    }
}

if ($stockFilter === 'low') {
    $where[] = 'p.stock_quantity > 0 AND p.stock_quantity <= p.low_stock_threshold';
} elseif ($stockFilter === 'out') {
    $where[] = 'p.stock_quantity <= 0';
} elseif ($stockFilter === 'available') {
    $where[] = 'p.stock_quantity > 0';
}

$sql = "SELECT
            p.id,
            p.sku,
            p.name,
            p.price,
            p.stock_quantity,
            p.low_stock_threshold,
            p.status AS product_status,
            c.name AS category_name,
            COALESCE(SUM(CASE WHEN o.status = 'pending' THEN oi.qty ELSE 0 END), 0) AS pending_qty,
            COALESCE(SUM(CASE WHEN o.status = 'processing' THEN oi.qty ELSE 0 END), 0) AS processing_qty,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN oi.qty ELSE 0 END), 0) AS completed_qty,
            COALESCE(SUM(CASE WHEN o.status = 'cancelled' THEN oi.qty ELSE 0 END), 0) AS cancelled_qty,
            COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN oi.qty ELSE 0 END), 0) AS active_order_qty,
            COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN oi.line_subtotal ELSE 0 END), 0) AS active_order_value
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN order_items oi ON oi.product_id = p.id
        LEFT JOIN orders o ON o.id = oi.order_id";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' GROUP BY p.id, p.sku, p.name, p.price, p.stock_quantity, p.low_stock_threshold, p.status, c.name ORDER BY p.stock_quantity ASC, p.name ASC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$summary = [
    'products' => count($rows),
    'current_stock' => 0,
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'low_stock' => 0,
    'out_stock' => 0,
    'active_order_value' => 0,
];
foreach ($rows as $row) {
    $summary['current_stock'] += (int)$row['stock_quantity'];
    $summary['pending'] += (int)$row['pending_qty'];
    $summary['processing'] += (int)$row['processing_qty'];
    $summary['completed'] += (int)$row['completed_qty'];
    $summary['cancelled'] += (int)$row['cancelled_qty'];
    $summary['active_order_value'] += (float)$row['active_order_value'];
    if ((int)$row['stock_quantity'] <= 0) {
        $summary['out_stock']++;
    } elseif ((int)$row['stock_quantity'] <= (int)$row['low_stock_threshold']) {
        $summary['low_stock']++;
    }
}

if ($export) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventory-report-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['SKU', 'Product', 'Category', 'Status', 'Current Stock', 'Low Stock Alert', 'Pending Qty', 'Processing Qty', 'Completed Qty', 'Cancelled Qty', 'Active Order Qty', 'Active Order Value']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['sku'],
            $row['name'],
            $row['category_name'],
            $row['product_status'],
            (int)$row['stock_quantity'],
            (int)$row['low_stock_threshold'],
            (int)$row['pending_qty'],
            (int)$row['processing_qty'],
            (int)$row['completed_qty'],
            (int)$row['cancelled_qty'],
            (int)$row['active_order_qty'],
            number_format((float)$row['active_order_value'], 2, '.', ''),
        ]);
    }
    fclose($out);
    exit;
}

include __DIR__ . '/_header.php';
?>
<div class="alert alert-info">
  <strong>How to read this report:</strong> stock is deducted when a quote order is submitted. If an order is changed to <strong>Cancelled</strong>, stock is restored automatically. Pending + Processing + Completed quantities show order movement by status.
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="metric"><div class="text-muted">Products Shown</div><div class="value"><?= (int)$summary['products'] ?></div><small>after filters</small></div></div>
  <div class="col-md-3"><div class="metric"><div class="text-muted">Current Stock Units</div><div class="value"><?= (int)$summary['current_stock'] ?></div><small>available inventory</small></div></div>
  <div class="col-md-3"><div class="metric"><div class="text-muted">Pending / Processing</div><div class="value"><?= (int)($summary['pending'] + $summary['processing']) ?></div><small><?= (int)$summary['pending'] ?> pending, <?= (int)$summary['processing'] ?> processing</small></div></div>
  <div class="col-md-3"><div class="metric"><div class="text-muted">Low / Out Stock</div><div class="value text-warning"><?= (int)($summary['low_stock'] + $summary['out_stock']) ?></div><small><?= (int)$summary['low_stock'] ?> low, <?= (int)$summary['out_stock'] ?> out</small></div></div>
</div>

<div class="card-soft p-3 mb-4">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Search</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="SKU or product name"></div>
    <div class="col-md-3"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="0">All categories</option><?= render_category_options($categories, $categoryId ?: null) ?></select></div>
    <div class="col-md-2"><label class="form-label">Stock</label><select name="stock" class="form-select"><option value="">Any</option><option value="available" <?= $stockFilter === 'available' ? 'selected' : '' ?>>Available</option><option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low stock</option><option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Out of stock</option></select></div>
    <div class="col-md-4 d-flex gap-2"><button class="btn btn-primary">Filter</button><a class="btn btn-outline-secondary" href="<?= e(admin_url('inventory-report.php')) ?>">Reset</a><a class="btn btn-outline-primary" href="<?= e(admin_url('inventory-report.php?' . http_build_query(array_merge($_GET, ['export' => 'csv'])))) ?>">Export CSV</a></div>
  </form>
</div>

<div class="card-soft p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th>Current Stock</th>
          <th>Pending</th>
          <th>Processing</th>
          <th>Completed</th>
          <th>Cancelled</th>
          <th>Active Order Qty</th>
          <th>Active Order Value</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <?php list($stockText, $stockClass) = stock_label((int)$row['stock_quantity']); ?>
          <tr>
            <td><strong><?= e($row['name']) ?></strong><br><small class="text-muted"><?= e($row['sku']) ?></small></td>
            <td><?= e($row['category_name'] ?: '—') ?></td>
            <td><span class="badge bg-<?= e($stockClass) ?>"><?= e($stockText) ?></span><br><small class="text-muted">Alert: <?= (int)$row['low_stock_threshold'] ?></small></td>
            <td><?= (int)$row['pending_qty'] ?></td>
            <td><?= (int)$row['processing_qty'] ?></td>
            <td><?= (int)$row['completed_qty'] ?></td>
            <td><?= (int)$row['cancelled_qty'] ?></td>
            <td><strong><?= (int)$row['active_order_qty'] ?></strong></td>
            <td><?= e(format_price($row['active_order_value'])) ?></td>
            <td><span class="badge bg-<?= $row['product_status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($row['product_status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="10" class="text-center text-muted py-5">No inventory records found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
