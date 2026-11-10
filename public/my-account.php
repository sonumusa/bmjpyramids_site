<?php
require_once __DIR__ . '/../includes/auth.php';
$pageKey = 'my-account';
$seo = ['title'=>'My Account | BMJ Pyramids LLP','description'=>'View BMJ Pyramids quote order history and reorder products.','keywords'=>'','og_image'=>''];
require_customer();
$customer = customer_current();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['post_action'] ?? '') === 'reorder') {
    require_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $stmt = db()->prepare('SELECT oi.product_id, oi.qty FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.id=? AND o.customer_id=?');
    $stmt->execute([$orderId, (int)$customer['id']]);
    $added = 0;
    foreach ($stmt->fetchAll() as $row) {
        try { cart_add((int)$row['product_id'], (int)$row['qty']); $added++; } catch (Throwable $e) {}
    }
    flash($added ? 'success' : 'warning', $added ? 'Products added to cart.' : 'No products could be reordered due to stock availability.');
    redirect(frontend_url('cart'));
}

$detail = null; $items = [];
if (isset($_GET['order'])) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE id=? AND customer_id=?');
    $stmt->execute([(int)$_GET['order'], (int)$customer['id']]);
    $detail = $stmt->fetch() ?: null;
    if ($detail) {
        $stmt = db()->prepare('SELECT * FROM order_items WHERE order_id=?');
        $stmt->execute([(int)$detail['id']]);
        $items = $stmt->fetchAll();
    }
}
$stmt = db()->prepare('SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC');
$stmt->execute([(int)$customer['id']]);
$orders = $stmt->fetchAll();
include __DIR__ . '/_header.php';
?>
<header class="page-header"><div class="container"><h1 class="display-6 fw-bold mb-0">My Account</h1><p class="mb-0 mt-2 opacity-75">Welcome, <?= e($customer['name']) ?>.</p></div></header>
<section class="py-5"><div class="container"><div class="row g-4"><div class="col-lg-3"><div class="soft-card p-3 account-sidebar"><a class="active" href="<?= e(frontend_url('my-account')) ?>">Order History</a><a href="<?= e(frontend_url('cart')) ?>">Quote Cart</a><a href="<?= e(frontend_url('logout')) ?>">Logout</a></div></div><div class="col-lg-9">
<?php if ($detail): ?>
  <div class="card-soft p-4 mb-4"><a href="<?= e(frontend_url('my-account')) ?>" class="btn btn-sm btn-outline-secondary mb-3">← All orders</a><h2 class="h4 fw-bold">Order <?= e($detail['order_number']) ?></h2><p class="text-muted">Status: <strong><?= e(ucfirst($detail['status'])) ?></strong> · <?= e(date('d M Y', strtotime($detail['created_at']))) ?></p><div class="table-responsive"><table class="table"><thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Total</th></tr></thead><tbody><?php foreach ($items as $it): ?><tr><td><?= e($it['product_name']) ?></td><td><?= e($it['sku']) ?></td><td><?= (int)$it['qty'] ?></td><td><?= e(format_price($it['line_total'])) ?></td></tr><?php endforeach; ?></tbody></table></div><div class="text-end fs-5 fw-bold">Grand Total: <?= e(format_price($detail['grand_total'])) ?></div></div>
<?php endif; ?>
<div class="card-soft p-4"><h2 class="h4 fw-bold mb-3">Order History</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($orders as $order): ?><tr><td><strong><?= e($order['order_number']) ?></strong></td><td><?= e(date('d M Y', strtotime($order['created_at']))) ?></td><td><?= e(format_price($order['grand_total'])) ?></td><td><span class="badge bg-secondary"><?= e(ucfirst($order['status'])) ?></span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(frontend_url('my-account', ['order'=>$order['id']])) ?>">View</a><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="post_action" value="reorder"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><button class="btn btn-sm btn-outline-secondary">Reorder</button></form></td></tr><?php endforeach; ?><?php if(!$orders): ?><tr><td colspan="5" class="text-center text-muted py-5">No quote orders yet.</td></tr><?php endif; ?></tbody></table></div></div>
</div></div></div></section>
<?php include __DIR__ . '/_footer.php'; ?>
