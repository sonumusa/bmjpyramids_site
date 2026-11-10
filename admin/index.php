<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_capability('dashboard_view');
$pageTitle = 'Dashboard';

$metrics = [
    'products' => (int)db()->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'active_products' => (int)db()->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn(),
    'orders' => (int)db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending_orders' => (int)db()->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(),
    'low_stock' => (int)db()->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= low_stock_threshold AND status='active'")->fetchColumn(),
];
$recentOrders = db()->query('SELECT id, order_number, customer_name, grand_total, status, created_at FROM orders ORDER BY id DESC LIMIT 8')->fetchAll();
$lowStock = db()->query("SELECT sku, name, stock_quantity, low_stock_threshold FROM products WHERE stock_quantity <= low_stock_threshold AND status='active' ORDER BY stock_quantity ASC LIMIT 8")->fetchAll();
include __DIR__ . '/_header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="metric"><div class="text-muted">All Products</div><div class="value"><?= $metrics['products'] ?></div><small><?= $metrics['active_products'] ?> active</small></div></div>
  <div class="col-md-3"><div class="metric"><div class="text-muted">Total Orders</div><div class="value"><?= $metrics['orders'] ?></div><small><?= $metrics['pending_orders'] ?> pending</small></div></div>
  <div class="col-md-3"><div class="metric"><div class="text-muted">Low Stock Alerts</div><div class="value text-warning"><?= $metrics['low_stock'] ?></div><small>Needs reorder</small></div></div>
  <div class="col-md-3"><div class="metric"><div class="text-muted">GST</div><div class="value"><?= GST_RATE ?>%</div><small>Fixed on checkout</small></div></div>
</div>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card-soft p-3">
      <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0 fw-bold">Recent Quote Orders</h2><a href="<?= e(admin_url('orders.php')) ?>" class="btn btn-sm btn-outline-primary">View all</a></div>
      <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead><tbody>
      <?php foreach ($recentOrders as $order): ?><tr><td><a href="<?= e(admin_url('orders.php?id=' . $order['id'])) ?>"><?= e($order['order_number']) ?></a></td><td><?= e($order['customer_name']) ?></td><td><?= e(format_price($order['grand_total'])) ?></td><td><span class="badge bg-secondary"><?= e(ucfirst($order['status'])) ?></span></td><td><?= e(date('d M Y', strtotime($order['created_at']))) ?></td></tr><?php endforeach; ?>
      <?php if (!$recentOrders): ?><tr><td colspan="5" class="text-muted text-center py-4">No orders yet.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-soft p-3">
      <h2 class="h5 fw-bold mb-3">Low Stock Products</h2>
      <div class="table-responsive"><table class="table align-middle"><thead><tr><th>SKU</th><th>Product</th><th>Stock</th></tr></thead><tbody>
      <?php foreach ($lowStock as $p): ?><tr><td><?= e($p['sku']) ?></td><td><?= e($p['name']) ?></td><td><span class="badge bg-warning text-dark"><?= (int)$p['stock_quantity'] ?> / <?= (int)$p['low_stock_threshold'] ?></span></td></tr><?php endforeach; ?>
      <?php if (!$lowStock): ?><tr><td colspan="3" class="text-muted text-center py-4">All stock levels look good.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
