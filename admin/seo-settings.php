<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_capability('seo_write');
$pageTitle = 'SEO Settings';
$pages = [
    'home' => 'Home Page',
    'shop' => 'Shop Page',
    'about' => 'About Page',
    'contact' => 'Contact Page',
    'product_default' => 'Default Product SEO',
    'category_default' => 'Default Category SEO',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = $_POST['post_action'] ?? '';
    try {
        if ($postAction === 'generate_sitemap') {
            $count = generate_sitemap_file();
            flash('success', "Sitemap generated with {$count} URLs.");
            redirect(admin_url('seo-settings.php'));
        }
        if ($postAction === 'save_company_settings') {
            foreach (['company_phone', 'company_email', 'company_address', 'whatsapp', 'years_in_business'] as $key) {
                set_setting($key, trim($_POST[$key] ?? ''));
            }
            flash('success', 'Company settings saved.');
            redirect(admin_url('seo-settings.php'));
        }
        if ($postAction === 'save_seo') {
            $pageKey = $_POST['page_key'] ?? '';
            if (!array_key_exists($pageKey, $pages)) {
                throw new RuntimeException('Invalid page key.');
            }
            $stmt = db()->prepare('INSERT INTO seo_settings (page_key, title, meta_description, keywords, og_image, updated_at) VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE title=VALUES(title), meta_description=VALUES(meta_description), keywords=VALUES(keywords), og_image=VALUES(og_image), updated_at=NOW()');
            $stmt->execute([$pageKey, trim($_POST['title'] ?? ''), trim($_POST['meta_description'] ?? ''), trim($_POST['keywords'] ?? ''), trim($_POST['og_image'] ?? '')]);
            flash('success', 'SEO settings saved.');
            redirect(admin_url('seo-settings.php#' . $pageKey));
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
        redirect(admin_url('seo-settings.php'));
    }
}

$seoRows = [];
foreach (db()->query('SELECT * FROM seo_settings')->fetchAll() as $row) {
    $seoRows[$row['page_key']] = $row;
}
include __DIR__ . '/_header.php';
?>
<div class="row g-4">
  <div class="col-lg-8">
    <?php foreach ($pages as $key => $label): $row = $seoRows[$key] ?? []; ?>
      <div class="card-soft p-4 mb-4" id="<?= e($key) ?>">
        <h2 class="h5 fw-bold mb-3"><?= e($label) ?></h2>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="post_action" value="save_seo">
          <input type="hidden" name="page_key" value="<?= e($key) ?>">
          <div class="mb-3"><label class="form-label">Meta Title</label><input name="title" class="form-control" maxlength="160" value="<?= e($row['title'] ?? '') ?>"><small class="text-muted">Recommended: 50-60 characters.</small></div>
          <div class="mb-3"><label class="form-label">Meta Description</label><textarea name="meta_description" class="form-control" maxlength="255" rows="3"><?= e($row['meta_description'] ?? '') ?></textarea><small class="text-muted">Recommended: 140-160 characters.</small></div>
          <div class="mb-3"><label class="form-label">Keywords</label><input name="keywords" class="form-control" value="<?= e($row['keywords'] ?? '') ?>"></div>
          <div class="mb-3"><label class="form-label">Open Graph Image URL</label><input name="og_image" class="form-control" value="<?= e($row['og_image'] ?? '') ?>" placeholder="https://..."></div>
          <button class="btn btn-primary">Save <?= e($label) ?></button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="col-lg-4">
    <div class="card-soft p-4 mb-4">
      <h2 class="h5 fw-bold">Sitemap Generator</h2>
      <p class="text-muted">Creates /sitemap.xml with pages, categories and active products.</p>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="post_action" value="generate_sitemap"><button class="btn btn-primary w-100">Generate Sitemap</button></form>
      <hr><a href="<?= e(site_url('sitemap.xml')) ?>" target="_blank">Open sitemap.xml</a><br><a href="<?= e(site_url('robots.txt')) ?>" target="_blank">Open robots.txt</a>
    </div>
    <div class="card-soft p-4 mb-4">
      <h2 class="h5 fw-bold">Company Settings</h2>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="post_action" value="save_company_settings">
        <label class="form-label">Phone</label><input name="company_phone" class="form-control mb-2" value="<?= e(get_setting('company_phone', SITE_PHONE)) ?>">
        <label class="form-label">Email</label><input name="company_email" class="form-control mb-2" value="<?= e(get_setting('company_email', SITE_EMAIL)) ?>">
        <label class="form-label">WhatsApp</label><input name="whatsapp" class="form-control mb-2" value="<?= e(get_setting('whatsapp', SITE_PHONE)) ?>">
        <label class="form-label">Address</label><textarea name="company_address" class="form-control mb-2" rows="3"><?= e(get_setting('company_address', SITE_ADDRESS)) ?></textarea>
        <label class="form-label">Years in Business</label><input name="years_in_business" class="form-control mb-3" value="<?= e(get_setting('years_in_business', '10+')) ?>">
        <button class="btn btn-outline-primary w-100">Save Company Settings</button>
      </form>
    </div>
    <div class="card-soft p-4">
      <h2 class="h5 fw-bold">Agency SEO Checklist</h2>
      <ul class="small text-muted mb-0">
        <li>Use unique meta title and description per product.</li>
        <li>Add descriptive product image alt text.</li>
        <li>Regenerate sitemap after bulk uploads.</li>
        <li>Keep one H1 per page and use category descriptions.</li>
        <li>Submit sitemap in Google Search Console.</li>
      </ul>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
