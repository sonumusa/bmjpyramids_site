<?php
require_once __DIR__ . '/../includes/functions.php';
$pageKey = 'cart';
$seo = ['title' => 'Quote Cart | BMJ Pyramids LLP', 'description' => 'Review selected products and optional Sales Tax/GST before sending a quote request.', 'keywords'=>'', 'og_image'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['post_action'] ?? 'update';

    // Save Sales Tax/GST choice from cart. Default is checked/applied.
    if ($action !== 'clear') {
        $_SESSION['apply_gst'] = isset($_POST['apply_gst']) && $_POST['apply_gst'] === '1';
    }

    if ($action === 'update' || $action === 'proceed_checkout') {
        cart_update($_POST['qty'] ?? []);
        if ($action === 'proceed_checkout') {
            redirect(frontend_url('checkout'));
        }
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
$applyGst = isset($_SESSION['apply_gst']) ? (bool)$_SESSION['apply_gst'] : true;
$displayGst = $applyGst ? (float)$totals['gst'] : 0.00;
$displayGrand = round((float)$totals['subtotal'] + $displayGst, 2);
include __DIR__ . '/_header.php';
?>
<header class="page-header"><div class="container"><h1 class="display-6 fw-bold mb-0">Quote Cart</h1><p class="mb-0 mt-2 opacity-75">Review products and choose whether to apply 18% Sales Tax/GST before checkout.</p></div></header>
<section class="py-5">
  <div class="container">
    <?php if (!$items): ?>
      <div class="soft-card p-5 text-center"><h2 class="h4 fw-bold">Your cart is empty</h2><p class="text-muted">Add products to request a quote from BMJ Pyramids LLP.</p><a href="<?= e(frontend_url('shop')) ?>" class="btn btn-primary">Browse Products</a></div>
    <?php else: ?>
      <form method="post" id="cartForm">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="">
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="card-soft p-3">
              <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>S.Tax/GST</th><th>Total</th><th></th></tr></thead><tbody>
                <?php foreach ($items as $item): $p=$item['product']; $lineShown = $applyGst ? $item['total'] : $item['subtotal']; ?>
                  <tr>
                    <td><div class="d-flex gap-3 align-items-center"><img class="cart-img" src="<?= e(get_primary_image($p)) ?>" alt="<?= e($p['name']) ?>"><div><strong><a class="text-dark" href="<?= e(product_url($p['slug'])) ?>"><?= e($p['name']) ?></a></strong><br><small class="text-muted"><?= e($p['sku']) ?> · Stock <?= (int)$p['stock_quantity'] ?></small></div></div></td>
                    <td><?= e(format_price($p['price'])) ?></td>
                    <td><input type="number" min="1" max="<?= (int)$p['stock_quantity'] ?>" name="qty[<?= (int)$p['id'] ?>]" value="<?= (int)$item['qty'] ?>" class="form-control" style="width:90px"></td>
                    <td class="cart-line-gst" data-gst="<?= e($item['gst']) ?>"><?= e(format_price($applyGst ? $item['gst'] : 0)) ?></td>
                    <td><strong class="cart-line-total" data-subtotal="<?= e($item['subtotal']) ?>" data-gst="<?= e($item['gst']) ?>"><?= e(format_price($lineShown)) ?></strong></td>
                    <td><button type="submit" name="post_action" value="remove" class="btn btn-sm btn-outline-danger" onclick="this.form.product_id.value='<?= (int)$p['id'] ?>'">Remove</button></td>
                  </tr>
                <?php endforeach; ?>
              </tbody></table></div>
              <div class="d-flex gap-2 justify-content-between flex-wrap"><a href="<?= e(frontend_url('shop')) ?>" class="btn btn-outline-primary">Continue Shopping</a><div><button type="submit" name="post_action" value="update" class="btn btn-outline-secondary">Update Cart</button><button type="submit" name="post_action" value="clear" class="btn btn-outline-danger" data-confirm="Clear cart?">Clear</button></div></div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="totals-card p-4">
              <h2 class="h5 fw-bold mb-3">Order Summary</h2>
              <div class="form-check form-switch mb-3 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                <input class="form-check-input" type="checkbox" role="switch" id="apply_gst" name="apply_gst" value="1" <?= $applyGst ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="apply_gst">Apply 18% Sales Tax/GST</label>
                <div class="small text-muted">Uncheck if quote should be submitted without S.Tax/GST.</div>
              </div>
              <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong id="cartSubtotal" data-value="<?= e($totals['subtotal']) ?>"><?= e(format_price($totals['subtotal'])) ?></strong></div>
              <div class="d-flex justify-content-between mb-2"><span>Sales Tax/GST (<?= GST_RATE ?>%)</span><strong id="cartGst" data-value="<?= e($totals['gst']) ?>"><?= e(format_price($displayGst)) ?></strong></div>
              <hr><div class="d-flex justify-content-between fs-5 mb-3"><span>Total</span><strong id="cartGrand"><?= e(format_price($displayGrand)) ?></strong></div>
              <button type="submit" name="post_action" value="proceed_checkout" class="btn btn-primary w-100 btn-lg">Proceed to Quote Checkout</button>
              <p class="small text-muted mt-3 mb-0">This Sales Tax/GST choice will be saved into the order and shown in admin as S.Tax Applied or No S.Tax.</p>
            </div>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </div>
</section>
<script>
(function(){
  const checkbox = document.getElementById('apply_gst');
  const subtotalEl = document.getElementById('cartSubtotal');
  const gstEl = document.getElementById('cartGst');
  const grandEl = document.getElementById('cartGrand');
  const lineTotalEls = document.querySelectorAll('.cart-line-total');
  const lineGstEls = document.querySelectorAll('.cart-line-gst');
  function money(value){
    return 'Rs. ' + Number(value || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }
  function recalc(){
    if (!checkbox || !subtotalEl || !gstEl || !grandEl) return;
    const subtotal = Number(subtotalEl.dataset.value || 0);
    const gst = checkbox.checked ? Number(gstEl.dataset.value || 0) : 0;
    lineTotalEls.forEach(function(el){
      const base = Number(el.dataset.subtotal || 0);
      const lineGst = checkbox.checked ? Number(el.dataset.gst || 0) : 0;
      el.textContent = money(base + lineGst);
    });
    lineGstEls.forEach(function(el){
      el.textContent = money(checkbox.checked ? Number(el.dataset.gst || 0) : 0);
    });
    gstEl.textContent = money(gst);
    grandEl.textContent = money(subtotal + gst);
  }
  if (checkbox) checkbox.addEventListener('change', recalc);
  recalc();
})();
</script>
<?php include __DIR__ . '/_footer.php'; ?>
