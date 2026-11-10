<footer class="footer mt-5 pt-5 pb-4">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3"><img src="<?= e(asset_url('images/logo.png')) ?>" alt="BMJ logo" style="width:54px;height:58px;object-fit:contain"><h5 class="mb-0">BMJ Pyramids LLP</h5></div>
        <p><?= e(SITE_TAGLINE) ?>. Built for industrial buyers who need reliable stock, clear quotations and professional service.</p>
      </div>
      <div class="col-lg-2 col-md-4"><h6>Company</h6><ul class="list-unstyled"><li><a href="<?= e(frontend_url('about')) ?>">About</a></li><li><a href="<?= e(frontend_url('shop')) ?>">Products</a></li><li><a href="<?= e(frontend_url('contact')) ?>">Contact</a></li><li><a href="<?= e(frontend_url('my-account')) ?>">My Account</a></li></ul></div>
      <div class="col-lg-3 col-md-4"><h6>Popular Categories</h6><ul class="list-unstyled"><?php foreach ($navCategories as $cat): ?><li><a href="<?= e(category_url($cat['slug'])) ?>"><?= e($cat['name']) ?></a></li><?php endforeach; ?></ul></div>
      <div class="col-lg-3 col-md-4"><h6>Contact</h6><p class="mb-1"><?= e(get_setting('company_phone', SITE_PHONE)) ?></p><p class="mb-1"><?= e(get_setting('company_email', SITE_EMAIL)) ?></p><p class="mb-0"><?= nl2br(e(get_setting('company_address', SITE_ADDRESS))) ?></p></div>
    </div>
    <hr class="border-secondary my-4">
    <div class="d-flex flex-wrap justify-content-between gap-2 small"><span>© <?= date('Y') ?> BMJ Pyramids LLP. All rights reserved.</span><span>18% GST applied on quote requests.</span></div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset_url('js/main.js')) ?>"></script>
</body>
</html>
