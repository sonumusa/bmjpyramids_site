<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_capability('orders_view');
$pageTitle = 'Orders';
$canWrite = admin_can('orders_write');
$statuses = ['pending', 'processing', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (!$canWrite) {
        http_response_code(403);
        die('Your role can view orders but cannot change order status.');
    }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? 'pending';
    if (!in_array($newStatus, $statuses, true)) {
        $newStatus = 'pending';
    }

    try {
        $pdo = db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        $oldStatus = $order['status'];
        if ($oldStatus !== $newStatus) {
            $items = $pdo->prepare('SELECT product_id, qty FROM order_items WHERE order_id = ? AND product_id IS NOT NULL');
            $items->execute([$orderId]);
            $orderItems = $items->fetchAll();

            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                foreach ($orderItems as $item) {
                    $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?')->execute([(int)$item['qty'], (int)$item['product_id']]);
                }
            }

            if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                foreach ($orderItems as $item) {
                    $check = $pdo->prepare('SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE');
                    $check->execute([(int)$item['product_id']]);
                    if ((int)$check->fetchColumn() < (int)$item['qty']) {
                        throw new RuntimeException('Not enough stock to reactivate this cancelled order.');
                    }
                }
                foreach ($orderItems as $item) {
                    $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?')->execute([(int)$item['qty'], (int)$item['product_id']]);
                }
            }

            $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$newStatus, $orderId]);
        }
        $pdo->commit();
        flash('success', 'Order status updated.');
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash('danger', $e->getMessage());
    }
    redirect(admin_url('orders.php?id=' . $orderId));
}

if (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        flash('danger', 'Order not found.');
        redirect(admin_url('orders.php'));
    }
    $stmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    include __DIR__ . '/_header.php';
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <a href="<?= e(admin_url('orders.php')) ?>" class="btn btn-sm btn-outline-secondary">← Back to orders</a>
      <a href="javascript:window.print()" class="btn btn-sm btn-outline-primary">Print</a>
    </div>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card-soft p-4">
          <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
            <div><h2 class="h4 fw-bold mb-0">Order <?= e($order['order_number']) ?></h2><small class="text-muted">Placed <?= e(date('d M Y h:i A', strtotime($order['created_at']))) ?></small></div>
            <span class="badge bg-secondary align-self-start fs-6"><?= e(ucfirst($order['status'])) ?></span>
          </div>
          <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Product</th><th>SKU</th><th>Price</th><th>Qty</th><th>GST</th><th>Total</th></tr></thead><tbody>
          <?php foreach ($items as $item): ?><tr><td><?= e($item['product_name']) ?></td><td><?= e($item['sku']) ?></td><td><?= e(format_price($item['unit_price'])) ?></td><td><?= (int)$item['qty'] ?></td><td><?= e(format_price($item['gst_amount'])) ?></td><td><?= e(format_price($item['line_total'])) ?></td></tr><?php endforeach; ?>
          </tbody></table></div>
          <div class="row justify-content-end"><div class="col-md-5"><table class="table"><tr><th>Subtotal</th><td class="text-end"><?= e(format_price($order['subtotal'])) ?></td></tr><tr><th>GST (<?= GST_RATE ?>%)</th><td class="text-end"><?= e(format_price($order['gst_amount'])) ?></td></tr><tr class="fs-5"><th>Grand Total</th><td class="text-end fw-bold"><?= e(format_price($order['grand_total'])) ?></td></tr></table></div></div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card-soft p-4 mb-4">
          <h3 class="h5 fw-bold">Customer Details</h3>
          <p class="mb-1"><strong><?= e($order['customer_name']) ?></strong></p>
          <p class="mb-1"><?= e($order['company_name']) ?></p>
          <p class="mb-1"><a href="mailto:<?= e($order['customer_email']) ?>"><?= e($order['customer_email']) ?></a></p>
          <p class="mb-1"><a href="tel:<?= e($order['customer_phone']) ?>"><?= e($order['customer_phone']) ?></a></p>
          <p class="mb-0 text-muted"><?= nl2br(e($order['shipping_address'])) ?><br><?= e($order['city']) ?></p>
          <?php if ($order['notes']): ?><hr><strong>Notes</strong><p><?= nl2br(e($order['notes'])) ?></p><?php endif; ?>
        </div>
        <div class="card-soft p-4">
          <h3 class="h5 fw-bold">Status</h3>
          <?php if (!$canWrite): ?><div class="readonly-overlay mb-3">Your role can view orders but cannot change status.</div><?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
            <select name="status" class="form-select mb-3" <?= !$canWrite ? 'disabled' : '' ?>><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $order['status']===$status?'selected':'' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select>
            <?php if ($canWrite): ?><button class="btn btn-primary w-100">Update Status</button><?php endif; ?>
          </form>
        </div>
      </div>
    </div>
    <?php
    include __DIR__ . '/_footer.php';
    exit;
}

$status = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');
$where = [];
$params = [];
if (in_array($status, $statuses, true)) {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ? OR customer_phone LIKE ?)';
    $params[] = "%{$q}%"; $params[] = "%{$q}%"; $params[] = "%{$q}%"; $params[] = "%{$q}%";
}
$sql = 'SELECT * FROM orders';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC LIMIT 300';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
include __DIR__ . '/_header.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <form class="d-flex flex-wrap gap-2" method="get">
    <input class="form-control" name="q" style="width:260px" value="<?= e($q) ?>" placeholder="Search orders/customer">
    <select name="status" class="form-select" style="width:170px"><option value="">All statuses</option><?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= $status===$s?'selected':'' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select>
    <button class="btn btn-outline-primary">Filter</button>
  </form>
</div>
<div class="card-soft p-3">
  <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Order</th><th>Customer</th><th>Contact</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
  <?php foreach ($orders as $order): ?><tr><td><strong><?= e($order['order_number']) ?></strong></td><td><?= e($order['customer_name']) ?><br><small class="text-muted"><?= e($order['company_name']) ?></small></td><td><?= e($order['customer_phone']) ?><br><small><?= e($order['customer_email']) ?></small></td><td><?= e(format_price($order['grand_total'])) ?></td><td><span class="badge bg-secondary"><?= e(ucfirst($order['status'])) ?></span></td><td><?= e(date('d M Y', strtotime($order['created_at']))) ?></td><td class="text-end"><a href="<?= e(admin_url('orders.php?id=' . $order['id'])) ?>" class="btn btn-sm btn-outline-primary">View</a></td></tr><?php endforeach; ?>
  <?php if (!$orders): ?><tr><td colspan="7" class="text-center text-muted py-5">No orders found.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
