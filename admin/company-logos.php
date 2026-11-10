<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_capability('company_logos_write');
$pageTitle = 'Company Logos';
$settingKey = 'company_logos';

function company_logo_items(string $settingKey): array
{
    $items = json_decode((string)get_setting($settingKey, '[]'), true);
    if (!is_array($items)) {
        return [];
    }

    return array_values(array_filter($items, static function ($item) {
        return is_array($item)
            && !empty($item['id'])
            && !empty($item['name'])
            && !empty($item['image'])
            && isset($item['active'], $item['sort_order']);
    }));
}

function remove_company_logo_file(string $path): void
{
    if (preg_match('/\Acompany-logos\/[a-zA-Z0-9._-]+\z/', $path)) {
        $file = UPLOAD_PATH . '/' . $path;
        if (is_file($file)) {
            unlink($file);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $newUpload = null;
    $saved = false;
    try {
        $items = company_logo_items($settingKey);
        $action = $_POST['post_action'] ?? '';

        if ($action === 'delete') {
            $id = (string)($_POST['id'] ?? '');
            $index = null;
            foreach ($items as $key => $item) {
                if (hash_equals((string)$item['id'], $id)) {
                    $index = $key;
                    break;
                }
            }
            if ($index === null) {
                throw new RuntimeException('Company logo not found.');
            }

            $oldImage = $items[$index]['image'];
            array_splice($items, $index, 1);
            set_setting($settingKey, json_encode($items, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $saved = true;
            remove_company_logo_file($oldImage);
            flash('success', 'Company logo removed.');
            redirect(admin_url('company-logos.php'));
        }

        if ($action !== 'save') {
            throw new RuntimeException('Invalid company logo action.');
        }

        $id = trim($_POST['id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            throw new RuntimeException('Company name is required.');
        }

        $website = trim($_POST['website'] ?? '');
        if ($website !== '' && (!filter_var($website, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($website, PHP_URL_SCHEME)), ['http', 'https'], true))) {
            throw new RuntimeException('Enter a valid website URL beginning with http:// or https://.');
        }

        $index = null;
        foreach ($items as $key => $item) {
            if ($id !== '' && hash_equals((string)$item['id'], $id)) {
                $index = $key;
                break;
            }
        }
        if ($id !== '' && $index === null) {
            throw new RuntimeException('Company logo not found.');
        }

        $oldImage = $index !== null ? $items[$index]['image'] : '';
        $newUpload = upload_company_logo($_FILES['logo'] ?? []);
        $image = $newUpload ?: $oldImage;
        if ($image === '') {
            throw new RuntimeException('Choose a logo image to upload.');
        }

        $entry = [
            'id' => $index !== null ? $items[$index]['id'] : bin2hex(random_bytes(8)),
            'name' => $name,
            'website' => $website,
            'image' => $image,
            'active' => isset($_POST['active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        if ($index !== null) {
            $items[$index] = $entry;
        } else {
            $items[] = $entry;
        }
        usort($items, static function ($a, $b) {
            return ((int)$a['sort_order'] <=> (int)$b['sort_order']) ?: strcasecmp($a['name'], $b['name']);
        });

        set_setting($settingKey, json_encode($items, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $saved = true;
        if ($newUpload && $oldImage !== '') {
            remove_company_logo_file($oldImage);
        }
        flash('success', 'Company logo saved.');
    } catch (Throwable $e) {
        if ($newUpload && !$saved) {
            remove_company_logo_file($newUpload);
        }
        flash('danger', $e->getMessage());
    }
    redirect(admin_url('company-logos.php'));
}

$items = company_logo_items($settingKey);
usort($items, static function ($a, $b) {
    return ((int)$a['sort_order'] <=> (int)$b['sort_order']) ?: strcasecmp($a['name'], $b['name']);
});
include __DIR__ . '/_header.php';
?>
<div class="card-soft p-4 mb-4">
  <h2 class="h5 fw-bold">Add company logo</h2>
  <p class="text-muted">Add customer, supplier or partner logos to the homepage carousel above Categories.</p>
  <form method="post" enctype="multipart/form-data" class="row g-3 align-items-end">
    <?= csrf_field() ?>
    <input type="hidden" name="post_action" value="save">
    <div class="col-md-4"><label class="form-label">Company name *</label><input name="name" class="form-control" maxlength="160" required></div>
    <div class="col-md-4"><label class="form-label">Logo image *</label><input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif" required><small class="text-muted">JPG, PNG, WebP or GIF; maximum 5MB.</small></div>
    <div class="col-md-3"><label class="form-label">Website (optional)</label><input type="url" name="website" class="form-control" placeholder="https://example.com"></div>
    <div class="col-md-1"><label class="form-label">Order</label><input type="number" name="sort_order" class="form-control" value="0"></div>
    <div class="col-12"><label class="form-check"><input type="checkbox" name="active" class="form-check-input" checked><span class="form-check-label">Show in carousel</span></label></div>
    <div class="col-12"><button class="btn btn-primary">Add Logo</button></div>
  </form>
</div>

<div class="card-soft p-4">
  <h2 class="h5 fw-bold mb-3">Manage company logos</h2>
  <?php if (!$items): ?>
    <p class="text-muted mb-0">No logos added yet. Add logos above to show the carousel on the homepage.</p>
  <?php else: ?>
    <div class="vstack gap-3">
      <?php foreach ($items as $item): ?>
        <div class="border rounded-3 p-3">
          <form method="post" enctype="multipart/form-data" class="row g-3 align-items-center">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="save">
            <input type="hidden" name="id" value="<?= e($item['id']) ?>">
            <div class="col-md-2 text-center"><img src="<?= e(upload_url($item['image'])) ?>" alt="<?= e($item['name']) ?>" style="max-width:150px;max-height:70px;object-fit:contain"><small class="d-block text-muted"><?= !empty($item['active']) ? 'Visible' : 'Hidden' ?></small></div>
            <div class="col-md-3"><label class="form-label">Company name</label><input name="name" class="form-control" maxlength="160" required value="<?= e($item['name']) ?>"></div>
            <div class="col-md-3"><label class="form-label">Website</label><input type="url" name="website" class="form-control" value="<?= e($item['website'] ?? '') ?>" placeholder="https://example.com"></div>
            <div class="col-md-2"><label class="form-label">Replace logo</label><input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif"></div>
            <div class="col-md-1"><label class="form-label">Order</label><input type="number" name="sort_order" class="form-control" value="<?= (int)$item['sort_order'] ?>"></div>
            <div class="col-md-1"><label class="form-check"><input type="checkbox" name="active" class="form-check-input" <?= !empty($item['active']) ? 'checked' : '' ?>><span class="form-check-label">Show</span></label></div>
            <div class="col-12"><button class="btn btn-sm btn-primary">Save</button></div>
          </form>
          <form method="post" class="mt-2" onsubmit="return confirm('Remove this company logo?')">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="delete">
            <input type="hidden" name="id" value="<?= e($item['id']) ?>">
            <button class="btn btn-sm btn-outline-danger">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
