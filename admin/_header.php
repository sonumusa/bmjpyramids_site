<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$user = admin_current_user();
$pageTitle = $pageTitle ?? 'Admin';
$current = basename($_SERVER['SCRIPT_NAME']);
$nav = [
    'index.php' => ['Dashboard', '▦', 'dashboard_view'],
    'products.php' => ['Products', '◫', 'products_view'],
    'categories.php' => ['Categories', '▱', 'categories_view'],
    'orders.php' => ['Orders', '☑', 'orders_view'],
    'inventory-report.php' => ['Inventory Report', '▤', 'inventory_view'],
    'team.php' => ['Team Roles', '♙', 'team_write'],
    'seo-settings.php' => ['SEO Tools', '◎', 'seo_write'],
    'company-logos.php' => ['Company Logos', '▧', 'company_logos_write'],
    'homepage-slides.php' => ['Homepage Slides', '▣', 'homepage_slides_write'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | BMJ Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(admin_asset_url('css/admin.css')) ?>">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="brand">
      <img src="<?= e(asset_url('images/logo.png')) ?>" alt="BMJ Pyramids logo">
      <div><strong>BMJ Admin</strong><small><?= e($user['role'] ?? '') ?></small></div>
    </div>
    <nav class="sidebar-nav">
      <?php foreach ($nav as $file => $item): ?>
        <?php if (admin_can($item[2])): ?>
          <a class="<?= $current === $file ? 'active' : '' ?>" href="<?= e(admin_url($file)) ?>"><span><?= e($item[1]) ?></span><?= e($item[0]) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
      <hr class="border-secondary">
      <a href="<?= e(frontend_url('index')) ?>" target="_blank"><span>↗</span>View Website</a>
      <a href="<?= e(admin_url('logout.php')) ?>"><span>⎋</span>Logout</a>
    </nav>
  </aside>
  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <h1 class="h4 mb-0 fw-bold"><?= e($pageTitle) ?></h1>
        <small class="text-muted">Professional B2B catalogue and quote system</small>
      </div>
      <div class="text-end">
        <strong><?= e($user['name'] ?? 'Admin') ?></strong><br>
        <small class="text-muted"><?= e($user['email'] ?? '') ?></small>
      </div>
    </div>
    <div class="content">
      <?php foreach (get_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
          <?= e($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endforeach; ?>
