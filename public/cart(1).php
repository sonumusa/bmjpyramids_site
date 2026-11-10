<?php
require_once __DIR__ . '/../includes/functions.php';
$pageKey = 'cart';
$seo = ['title' => 'Quote Cart | BMJ Pyramids LLP', 'description' => 'Review selected products and GST before sending a quote request.', 'keywords'=>'', 'og_image'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['post_action'] ?? 'update';
    if ($action === 'update') {
        cart_update($_POST['qty'] ?? []);
        flash('success', 'Cart updated.');
    } elseif ($action === 'remove') {
        $id = (int)($_POST['product_id'] ?? 0);
        unset($_SESSION['cart'][$id]);
        flash('success', 'Product removed.');
    } elseif ($action === 'clear') {
        cart_clear();
        flash('success', 'Cart cleared.');
    }
    redirect(frontend_url('cart'));
}

$items = cart_items();
$totals = cart_totals($items);
include __DIR__ . '/_header.php';
?>
<header class="page-header"><div class="container"><h1 class="display-6 fw-bold mb-0">Quote Cart</h1><p class="mb-0 mt-2 opacity-75">Review products before checkout. This creates a quote request, not online payment.</p></div></header>
<section class="py-5">
  <div class="container">
    <?php if (!$items): ?>
      <div class="soft-card p-5 text-center"><h2 class="h4 fw-bold">Your cart is empty</h2><p class="text-muted">Add products to request a quote from BMJ Pyramids LLP.</p><a href="<?= e(frontend_url('shop')) ?>" class="btn btn-primary">Browse Products</a></div>
    <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="post_action" value="update">
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="card-soft p-3">
              <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>GST</th><th>Total</th><th></th></tr></thead><tbody>
                <?php foreach ($items as $item): $p=$item['product']; ?>
                  <tr>
                    <td><div class="d-flex gap-3 align-items-center"><img class="cart-img" src="<?= e(get_primary_image($p)) ?>" alt="<?= e($p['name']) ?>"><div><strong><a class="text-dark" href="<?= e(product_url($p['slug'])) ?>"><?= e($p['name']) ?></a></strong><br><small class="text-muted"><?= e($p['sku']) ?> · Stock <?= (int)$p['stock_quantity'] ?></small></div></div></td>
                    <td><?= e(format_price($p['price'])) ?></td>
                    <td><input type="number" min="1" max="<?= (int)$p['stock_quantity'] ?>" name="qty[<?= (int)$p['id'] ?>]" value="<?= (int)$item['qty'] ?>" class="form-control" style="width:90px"></td>
                    <td><?= e(format_price($item['gst'])) ?></td>
                    <td><strong><?= e(format_price($item['total'])) ?></strong></td>
                    <td><button formaction="" name="post_action" value="remove" class="btn btn-sm btn-outline-danger" onclick="this.form.product_id.value='<?= (int)$p['id'] ?>'">Remove</button></td>
                  </tr>
                <?php endforeach; ?>
              </tbody></table></div>
              <input type="hidden" name="product_id" value="">
              <div class="d-flex gap-2 justify-content-between flex-wrap"><a href="<?= e(frontend_url('shop')) ?>" class="btn btn-outline-primary">Continue Shopping</a><div><button class="btn btn-outline-secondary">Update Cart</button><button name="post_action" value="clear" class="btn btn-outline-danger" data-confirm="Clear cart?">Clear</button></div></div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="totals-card p-4">
              <h2 class="h5 fw-bold mb-3">Order Summary</h2>
              <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong><?= e(format_price($totals['subtotal'])) ?></strong></div>
              <div class="d-flex justify-content-between mb-2"><span>GST (<?= GST_RATE ?>%)</span><strong><?= e(format_price($totals['gst'])) ?></strong></div>
              <hr><div class="d-flex justify-content-between fs-5 mb-3"><span>Total</span><strong><?= e(format_price($totals['grand'])) ?></strong></div>
              <a href="<?= e(frontend_url('checkout')) ?>" class="btn btn-primary w-100 btn-lg">Proceed to Quote Checkout</a>
              <p class="small text-muted mt-3 mb-0">Stock will be deducted after quote request submission.</p>
            </div>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
