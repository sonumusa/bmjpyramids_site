<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mail.php';
$pageKey = 'checkout';
$seo = ['title' => 'Quote Checkout | BMJ Pyramids LLP', 'description' => 'Submit your industrial product quote request with GST summary.', 'keywords'=>'', 'og_image'=>''];

if (isset($_GET['success'])) {
    include __DIR__ . '/_header.php';
    ?>
    <section class="py-5"><div class="container"><div class="soft-card p-5 text-center"><h1 class="fw-bold">Quote request submitted</h1><p class="lead text-muted">Your order number is <strong><?= e($_GET['success']) ?></strong>.</p><p>BMJ Pyramids LLP team will review stock, pricing and delivery details, then contact you with a formal quote/invoice.</p><a href="<?= e(frontend_url('shop')) ?>" class="btn btn-primary">Continue Browsing</a></div></div></section>
    <?php include __DIR__ . '/_footer.php'; exit;
}

$items = cart_items();
if (!$items) {
    flash('warning', 'Your cart is empty.');
    redirect(frontend_url('cart'));
}
$totals = cart_totals($items);
$customer = customer_current();
$errors = [];
$form = [
    'customer_name' => $customer['name'] ?? '',
    'company_name' => $customer['company_name'] ?? '',
    'customer_email' => $customer['email'] ?? '',
    'customer_phone' => $customer['phone'] ?? '',
    'shipping_address' => $customer['address'] ?? '',
    'city' => $customer['city'] ?? '',
    'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($form as $key => $default) {
        $form[$key] = trim($_POST[$key] ?? '');
    }
    foreach (['customer_name', 'customer_email', 'customer_phone', 'shipping_address', 'city'] as $required) {
        if ($form[$required] === '') {
            $errors[] = ucfirst(str_replace('_', ' ', $required)) . ' is required.';
        }
    }
    if ($form['customer_email'] && !filter_var($form['customer_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $freshItems = [];
            $subtotal = 0; $gstTotal = 0;
            foreach ($_SESSION['cart'] ?? [] as $productId => $qty) {
                $qty = max(1, (int)$qty);
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status='active' FOR UPDATE");
                $stmt->execute([(int)$productId]);
                $product = $stmt->fetch();
                if (!$product) {
                    throw new RuntimeException('A product in your cart is no longer available.');
                }
                if ((int)$product['stock_quantity'] < $qty) {
                    throw new RuntimeException($product['name'] . ' has only ' . (int)$product['stock_quantity'] . ' units available.');
                }
                $lineSubtotal = round((float)$product['price'] * $qty, 2);
                $rate = (float)($product['gst_rate'] ?: GST_RATE);
                $lineGst = gst_amount($lineSubtotal, $rate);
                $freshItems[] = ['product'=>$product, 'qty'=>$qty, 'subtotal'=>$lineSubtotal, 'gst'=>$lineGst, 'total'=>$lineSubtotal+$lineGst, 'rate'=>$rate];
                $subtotal += $lineSubtotal;
                $gstTotal += $lineGst;
            }
            if (!$freshItems) {
                throw new RuntimeException('Your cart is empty.');
            }
            $grandTotal = round($subtotal + $gstTotal, 2);
            $orderNumber = 'BMJ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $pdo->prepare('INSERT INTO orders (order_number, customer_id, customer_name, company_name, customer_email, customer_phone, shipping_address, city, subtotal, gst_amount, grand_total, status, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
            $stmt->execute([$orderNumber, $customer['id'] ?? null, $form['customer_name'], $form['company_name'], $form['customer_email'], $form['customer_phone'], $form['shipping_address'], $form['city'], $subtotal, $gstTotal, $grandTotal, 'pending', $form['notes']]);
            $orderId = (int)$pdo->lastInsertId();
            $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, sku, unit_price, qty, gst_rate, line_subtotal, gst_amount, line_total) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stockStmt = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ?, updated_at=NOW() WHERE id = ?');
            foreach ($freshItems as $item) {
                $p = $item['product'];
                $itemStmt->execute([$orderId, $p['id'], $p['name'], $p['sku'], $p['price'], $item['qty'], $item['rate'], $item['subtotal'], $item['gst'], $item['total']]);
                $stockStmt->execute([$item['qty'], $p['id']]);
            }
            $pdo->commit();
            cart_clear();

            $rows = '';
            foreach ($freshItems as $item) {
                $rows .= '<tr><td>' . e($item['product']['name']) . '</td><td>' . (int)$item['qty'] . '</td><td>' . e(format_price($item['total'])) . '</td></tr>';
            }
            $message = '<h2>New Quote Request ' . e($orderNumber) . '</h2><p><strong>Customer:</strong> ' . e($form['customer_name']) . '<br><strong>Email:</strong> ' . e($form['customer_email']) . '<br><strong>Phone:</strong> ' . e($form['customer_phone']) . '</p><table border="1" cellpadding="6" cellspacing="0"><tr><th>Product</th><th>Qty</th><th>Total</th></tr>' . $rows . '</table><p><strong>Grand Total:</strong> ' . e(format_price($grandTotal)) . '</p>';
            send_site_email(ADMIN_EMAIL, 'New quote request ' . $orderNumber, $message, $form['customer_email']);
            send_site_email($form['customer_email'], 'Your BMJ quote request ' . $orderNumber, '<p>Thank you. We received your quote request <strong>' . e($orderNumber) . '</strong>. Our team will contact you soon.</p>' . $message);
            redirect(frontend_url('checkout', ['success' => $orderNumber]));
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
}

include __DIR__ . '/_header.php';
?>
<header class="page-header"><div class="container"><h1 class="display-6 fw-bold mb-0">Quote Checkout</h1><p class="mb-0 mt-2 opacity-75">No online payment. Submit details and BMJ team will prepare the quote.</p></div></header>
<section class="py-5"><div class="container">
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="row g-4">
      <div class="col-lg-7"><div class="card-soft p-4"><h2 class="h5 fw-bold mb-3">Shipping & Contact Details</h2><div class="row g-3"><div class="col-md-6"><label class="form-label">Full Name *</label><input name="customer_name" class="form-control" required value="<?= e($form['customer_name']) ?>"></div><div class="col-md-6"><label class="form-label">Company</label><input name="company_name" class="form-control" value="<?= e($form['company_name']) ?>"></div><div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="customer_email" class="form-control" required value="<?= e($form['customer_email']) ?>"></div><div class="col-md-6"><label class="form-label">Phone *</label><input name="customer_phone" class="form-control" required value="<?= e($form['customer_phone']) ?>"></div><div class="col-12"><label class="form-label">Shipping Address *</label><textarea name="shipping_address" class="form-control" rows="3" required><?= e($form['shipping_address']) ?></textarea></div><div class="col-md-6"><label class="form-label">City *</label><input name="city" class="form-control" required value="<?= e($form['city']) ?>"></div><div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="4" placeholder="Delivery requirements, preferred brands, urgency..."><?= e($form['notes']) ?></textarea></div></div></div></div>
      <div class="col-lg-5"><div class="totals-card p-4"><h2 class="h5 fw-bold mb-3">Quote Summary</h2><?php foreach ($items as $item): $p=$item['product']; ?><div class="d-flex justify-content-between gap-3 mb-2"><span><?= e($p['name']) ?> × <?= (int)$item['qty'] ?></span><strong><?= e(format_price($item['total'])) ?></strong></div><?php endforeach; ?><hr><div class="d-flex justify-content-between"><span>Subtotal</span><strong><?= e(format_price($totals['subtotal'])) ?></strong></div><div class="d-flex justify-content-between"><span>GST (<?= GST_RATE ?>%)</span><strong><?= e(format_price($totals['gst'])) ?></strong></div><hr><div class="d-flex justify-content-between fs-5 mb-3"><span>Total</span><strong><?= e(format_price($totals['grand'])) ?></strong></div><button class="btn btn-primary btn-lg w-100">Submit Quote Request</button><p class="small text-muted mt-3 mb-0">Stock is deducted after submission to keep availability live. Admin can restore stock if an order is cancelled.</p></div></div>
    </div>
  </form>
</div></section>
<?php include __DIR__ . '/_footer.php'; ?>
