<?php
require_once __DIR__ . '/../includes/auth.php';
$pageKey = $pageKey ?? 'home';
$seo = $seo ?? get_seo($pageKey, $pageTitle ?? SITE_NAME, $metaDescription ?? SITE_TAGLINE);
$pageTitle = $seo['title'] ?? ($pageTitle ?? SITE_NAME);
$metaDescription = $seo['description'] ?? ($metaDescription ?? SITE_TAGLINE);
$canonicalUrl = $canonicalUrl ?? current_url();
$extraSchema = $extraSchema ?? [];
$customer = null;
try { $customer = customer_current(); } catch (Throwable $e) { $customer = null; }
$navCategories = [];
try { $navCategories = db()->query("SELECT name, slug FROM categories WHERE parent_id IS NULL AND status='active' ORDER BY sort_order, name LIMIT 6")->fetchAll(); } catch (Throwable $e) {}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>">
  <?php if (!empty($seo['keywords'])): ?><meta name="keywords" content="<?= e($seo['keywords']) ?>"><?php endif; ?>
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($canonicalUrl) ?>">
  <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
  <?php if (!empty($seo['og_image'])): ?><meta property="og:image" content="<?= e($seo['og_image']) ?>"><?php endif; ?>
  <link rel="icon" href="<?= e(asset_url('images/logo.png')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset_url('css/style.css') . '?v=' . filemtime(__DIR__ . '/assets/css/style.css')) ?>">
  <?= json_ld([
      '@context' => 'https://schema.org',
      '@type' => 'Organization',
      'name' => SITE_NAME,
      'url' => site_url(),
      'logo' => asset_url('images/logo.png'),
      'email' => get_setting('company_email', SITE_EMAIL),
      'telephone' => get_setting('company_phone', SITE_PHONE),
      'address' => get_setting('company_address', SITE_ADDRESS),
  ]) ?>
  <?php foreach ($extraSchema as $schema): ?><?= $schema ?><?php endforeach; ?>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(frontend_url('index')) ?>"><img src="<?= e(asset_url('images/logo.png')) ?>" alt="BMJ Pyramids LLP logo"><span>BMJ Pyramids LLP</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?= e(frontend_url('index')) ?>">Home</a></li>
        <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="<?= e(frontend_url('shop')) ?>" role="button" data-bs-toggle="dropdown">Shop</a><ul class="dropdown-menu"><?php foreach ($navCategories as $cat): ?><li><a class="dropdown-item" href="<?= e(category_url($cat['slug'])) ?>"><?= e($cat['name']) ?></a></li><?php endforeach; ?><li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="<?= e(frontend_url('shop')) ?>">All Products</a></li></ul></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(frontend_url('about')) ?>">About</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(frontend_url('contact')) ?>">Contact</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(frontend_url('cart')) ?>">Cart <span class="badge rounded-pill bg-primary"><?= cart_count() ?></span></a></li>
        <?php if ($customer): ?><li class="nav-item"><a class="btn btn-sm btn-outline-primary ms-lg-2" href="<?= e(frontend_url('my-account')) ?>">My Account</a></li><?php else: ?><li class="nav-item"><a class="btn btn-sm btn-outline-primary ms-lg-2" href="<?= e(frontend_url('login')) ?>">Login</a></li><?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<?php foreach (get_flashes() as $flash): ?>
  <div class="container mt-3"><div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div></div>
<?php endforeach; ?>
