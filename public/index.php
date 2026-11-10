<?php
require_once __DIR__ . '/../includes/functions.php';
$pageKey = 'home';
$seo = get_seo('home', 'BMJ Pyramids LLP | Industrial Tools, Bearings & B2B Supplier', 'Professional B2B industrial tools, bearings and supply solutions with live stock and GST-ready quote requests.');
$categories = [];
$featuredProducts = [];
$companyLogos = json_decode((string)get_setting('company_logos', '[]'), true);
$companyLogos = is_array($companyLogos) ? array_values(array_filter($companyLogos, static function ($item) {
    return is_array($item) && !empty($item['active']) && !empty($item['name']) && !empty($item['image']);
})) : [];
usort($companyLogos, static function ($a, $b) {
    return ((int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0)) ?: strcasecmp($a['name'], $b['name']);
});
$homepageSlides = json_decode((string)get_setting('homepage_slides', '[]'), true);
$homepageSlides = is_array($homepageSlides) ? array_values(array_filter($homepageSlides, static function ($item) {
    return is_array($item) && !empty($item['active']) && !empty($item['image']);
})) : [];
usort($homepageSlides, static function ($a, $b) {
    return (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0);
});
if (!$homepageSlides) {
    $homepageSlides = [['image' => 'asset:images/hero-tools.svg', 'alt' => 'Industrial tools and bearings illustration']];
}
try {
    $categories = db()->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.status='active') AS product_count FROM categories c WHERE c.parent_id IS NULL AND c.status='active' ORDER BY c.sort_order, c.name LIMIT 6")->fetchAll();
    $featuredProducts = db()->query("SELECT p.*, c.name AS category_name, (SELECT image_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS primary_image FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.status='active' AND p.featured=1 ORDER BY p.updated_at DESC LIMIT 8")->fetchAll();
} catch (Throwable $e) {}
include __DIR__ . '/_header.php';
?>
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="section-kicker mb-3">Trusted by Industry Leaders</div>
        <h2>Importer and Wholesaler of Industrial Tools, Hardware, Electrical & Electronic Components.</h2>
        <p class="lead mt-3">BMJ Pyramids LLP helps factories, workshops and procurement teams request accurate quotes with stock visibility, GST calculation and organized product categories.</p>
        <div class="d-flex gap-3 flex-wrap mt-4"><a href="<?= e(frontend_url('shop')) ?>" class="btn btn-primary btn-lg">Browse Products</a><a href="<?= e(frontend_url('contact')) ?>" class="btn btn-outline-primary btn-lg">Request Bulk Quote</a></div>
      </div>
      <div class="col-lg-6">
        <div class="hero-card">
          <div id="homepageHeroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="4500" data-bs-pause="hover" data-bs-touch="true" aria-label="Homepage image slideshow">
            <div class="carousel-inner">
              <?php foreach ($homepageSlides as $index => $slide): ?>
                <?php
                  $slideImage = (string)$slide['image'];
                  $slideSrc = strpos($slideImage, 'asset:') === 0
                      ? asset_url(substr($slideImage, 6))
                      : upload_url($slideImage);
                ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                  <img src="<?= e($slideSrc) ?>" class="d-block w-100" alt="<?= e($slide['alt'] ?? '') ?>" <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if (count($homepageSlides) > 1): ?>
              <button class="carousel-control-prev" type="button" data-bs-target="#homepageHeroCarousel" data-bs-slide="prev" aria-label="Previous slide">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#homepageHeroCarousel" data-bs-slide="next" aria-label="Next slide">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<div class="container">
  <div class="trust-strip p-4">
    <div class="row g-3 text-center">
      <div class="col-md-3"><strong class="fs-4 d-block"><?= e(get_setting('years_in_business', '10+')) ?></strong><span class="text-muted">Years Experience</span></div>
      <div class="col-md-3"><strong class="fs-4 d-block">Live</strong><span class="text-muted">Stock Tracking</span></div>
      <div class="col-md-3"><strong class="fs-4 d-block">18%</strong><span class="text-muted">GST Calculation</span></div>
      <div class="col-md-3"><strong class="fs-4 d-block">B2B</strong><span class="text-muted">Quote Workflow</span></div>
    </div>
  </div>
</div>
<?php if ($companyLogos): ?>
<section class="company-logo-section" aria-label="Our clients and partners">
  <div class="container">
    <div class="text-center mb-3"><div class="section-kicker">Trusted Partnerships</div><h2 class="h4 section-title mb-0">Companies we work with</h2></div>
    <div class="company-logo-carousel">
      <?php if (count($companyLogos) > 1): ?><button class="company-logo-control" type="button" data-logo-scroll="-1" aria-label="Previous company logos">&lsaquo;</button><?php endif; ?>
      <div class="company-logo-viewport">
        <div class="company-logo-track">
          <?php foreach ($companyLogos as $logo): ?>
            <?php $logoName = (string)$logo['name']; ?>
            <?php if (!empty($logo['website'])): ?>
              <a class="company-logo-item" href="<?= e($logo['website']) ?>" target="_blank" rel="noopener noreferrer"><span class="company-logo-circle"><img src="<?= e(upload_url($logo['image'])) ?>" alt="" loading="lazy"></span><span class="company-logo-name"><?= e($logoName) ?></span></a>
            <?php else: ?>
              <div class="company-logo-item"><span class="company-logo-circle"><img src="<?= e(upload_url($logo['image'])) ?>" alt="" loading="lazy"></span><span class="company-logo-name"><?= e($logoName) ?></span></div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
      <?php if (count($companyLogos) > 1): ?><button class="company-logo-control" type="button" data-logo-scroll="1" aria-label="Next company logos">&rsaquo;</button><?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4"><div><div class="section-kicker">Categories</div><h2 class="section-title">Shop by industrial category</h2></div><a href="<?= e(frontend_url('shop')) ?>" class="btn btn-outline-primary">View all</a></div>
    <div class="row g-4">
      <?php foreach ($categories as $cat): ?>
        <div class="col-md-6 col-lg-4"><a href="<?= e(category_url($cat['slug'])) ?>" class="soft-card category-card p-4 d-block h-100"><div class="icon mb-3">⚙</div><h3 class="h5 fw-bold text-dark"><?= e($cat['name']) ?></h3><p class="text-muted mb-2"><?= e($cat['description'] ?: 'Browse professional-grade products in this category.') ?></p><small><?= (int)$cat['product_count'] ?> products →</small></a></div>
      <?php endforeach; ?>
      <?php if (!$categories): ?><div class="col-12"><div class="alert alert-info">Import database.sql and add categories from admin to populate this area.</div></div><?php endif; ?>
    </div>
  </div>
</section>
<?php if ($featuredProducts): ?>
<section class="py-5 bg-light">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4"><div><div class="section-kicker">Featured Stock</div><h2 class="section-title">Popular products</h2></div><a href="<?= e(frontend_url('shop')) ?>" class="btn btn-outline-primary">Open shop</a></div>
    <div class="row g-4">
      <?php foreach ($featuredProducts as $p): ?>
        <?php [$stockText, $stockClass] = stock_label((int)$p['stock_quantity']); ?>
        <div class="col-md-6 col-lg-3"><div class="soft-card product-card"><a href="<?= e(product_url($p['slug'])) ?>"><img src="<?= e(get_primary_image($p)) ?>" alt="<?= e($p['name']) ?>"></a><div class="p-3"><div class="sku"><?= e($p['sku']) ?></div><h3 class="h6 fw-bold"><a class="text-dark" href="<?= e(product_url($p['slug'])) ?>"><?= e($p['name']) ?></a></h3><div class="d-flex justify-content-between align-items-center"><span class="price"><?= e(format_price($p['price'])) ?></span><span class="badge bg-<?= e($stockClass) ?> badge-stock"><?= e($stockText) ?></span></div></div></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="py-5 quote-process-section">
  <div class="container">
    <div class="text-center mb-4">
      <div class="section-kicker">A straightforward quote process</div>
      <h2 class="section-title">From product selection to quote in three steps.</h2>
      <p class="text-muted mx-auto quote-process-intro">Find what you need, tell us the quantities and send your request. Our team will review stock, pricing and delivery details with you.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4"><div class="soft-card quote-process-card p-4 h-100"><span class="quote-step-number">01</span><h3 class="h5 fw-bold mt-3">Choose your products</h3><p class="text-muted mb-0">Browse industrial categories and check product details and listed stock.</p></div></div>
      <div class="col-md-4"><div class="soft-card quote-process-card p-4 h-100"><span class="quote-step-number">02</span><h3 class="h5 fw-bold mt-3">Build your quote cart</h3><p class="text-muted mb-0">Add the products and quantities you need to your request.</p></div></div>
      <div class="col-md-4"><div class="soft-card quote-process-card p-4 h-100"><span class="quote-step-number">03</span><h3 class="h5 fw-bold mt-3">Send your request</h3><p class="text-muted mb-0">Submit your quote request and our team will follow up with pricing and delivery details.</p></div></div>
    </div>
    <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
      <a href="<?= e(frontend_url('shop')) ?>" class="btn btn-primary">Browse Products</a>
      <a href="<?= e(frontend_url('cart')) ?>" class="btn btn-outline-primary">View Quote Cart</a>
    </div>
  </div>
</section>
<section class="py-5 bg-light">
  <div class="container text-center"><div class="section-kicker">Testimonials</div><h2 class="section-title">Client feedback area</h2><p class="text-muted mx-auto" style="max-width:700px">Add real customer testimonials from the admin/content handoff later. This section is ready for credibility-building social proof.</p><a href="<?= e(frontend_url('contact')) ?>" class="btn btn-primary">Start an Inquiry</a></div>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
