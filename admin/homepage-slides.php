<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_capability('homepage_slides_write');
$pageTitle = 'Homepage Slides';
$settingKey = 'homepage_slides';

function homepage_slide_items(string $settingKey): array
{
    $items = json_decode((string)get_setting($settingKey, '[]'), true);
    if (!is_array($items)) {
        return [];
    }

    return array_values(array_filter($items, static function ($item) {
        return is_array($item)
            && !empty($item['id'])
            && !empty($item['image'])
            && isset($item['active'], $item['sort_order']);
    }));
}

function remove_homepage_slide_file(string $path): void
{
    if (preg_match('/\Ahomepage-slides\/[a-zA-Z0-9._-]+\z/', $path)) {
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
        $items = homepage_slide_items($settingKey);
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
                throw new RuntimeException('Homepage slide not found.');
            }

            $oldImage = $items[$index]['image'];
            array_splice($items, $index, 1);
            set_setting($settingKey, json_encode($items, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $saved = true;
            remove_homepage_slide_file($oldImage);
            flash('success', 'Homepage slide removed.');
            redirect(admin_url('homepage-slides.php'));
        }

        if ($action !== 'save') {
            throw new RuntimeException('Invalid homepage slide action.');
        }

        $id = trim($_POST['id'] ?? '');
        $index = null;
        foreach ($items as $key => $item) {
            if ($id !== '' && hash_equals((string)$item['id'], $id)) {
                $index = $key;
                break;
            }
        }
        if ($id !== '' && $index === null) {
            throw new RuntimeException('Homepage slide not found.');
        }

        $oldImage = $index !== null ? $items[$index]['image'] : '';
        $newUpload = upload_homepage_slide($_FILES['image'] ?? []);
        $image = $newUpload ?: $oldImage;
        if ($image === '') {
            throw new RuntimeException('Choose an image for the slide.');
        }

        $entry = [
            'id' => $index !== null ? $items[$index]['id'] : bin2hex(random_bytes(8)),
            'image' => $image,
            'alt' => trim($_POST['alt'] ?? ''),
            'active' => isset($_POST['active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        if ($index !== null) {
            $items[$index] = $entry;
        } else {
            $items[] = $entry;
        }
        usort($items, static function ($a, $b) {
            return ((int)$a['sort_order'] <=> (int)$b['sort_order']);
        });

        set_setting($settingKey, json_encode($items, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $saved = true;
        if ($newUpload && $oldImage !== '') {
            remove_homepage_slide_file($oldImage);
        }
        flash('success', 'Homepage slide saved.');
    } catch (Throwable $e) {
        if ($newUpload && !$saved) {
            remove_homepage_slide_file($newUpload);
        }
        flash('danger', $e->getMessage());
    }
    redirect(admin_url('homepage-slides.php'));
}

$items = homepage_slide_items($settingKey);
usort($items, static function ($a, $b) {
    return (int)$a['sort_order'] <=> (int)$b['sort_order'];
});
include __DIR__ . '/_header.php';
?>
<div class="card-soft p-4 mb-4">
  <h2 class="h5 fw-bold">Add homepage slide</h2>
  <p class="text-muted">These images replace the static hero illustration and rotate automatically in the same frame. The first active slide appears first.</p>
  <form method="post" enctype="multipart/form-data" class="row g-3 align-items-end">
    <?= csrf_field() ?>
    <input type="hidden" name="post_action" value="save">
    <div class="col-md-5"><label class="form-label">Slide image *</label><input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif" required><small class="text-muted">JPG, PNG, WebP or GIF; maximum 5MB.</small></div>
    <div class="col-md-4"><label class="form-label">Image description (optional)</label><input name="alt" class="form-control" maxlength="190" placeholder="Describe this slide"></div>
    <div class="col-md-2"><label class="form-label">Order</label><input type="number" name="sort_order" class="form-control" value="<?= count($items) ?>"></div>
    <div class="col-md-1"><label class="form-check"><input type="checkbox" name="active" class="form-check-input" checked><span class="form-check-label">Show</span></label></div>
    <div class="col-12"><button class="btn btn-primary">Add Slide</button></div>
  </form>
</div>

<div class="card-soft p-4">
  <h2 class="h5 fw-bold mb-3">Manage homepage slides</h2>
  <?php if (!$items): ?>
    <p class="text-muted mb-0">No custom slides yet. The homepage will show its default illustration until you add slides.</p>
  <?php else: ?>
    <div class="vstack gap-3">
      <?php foreach ($items as $item): ?>
        <div class="border rounded-3 p-3">
          <form method="post" enctype="multipart/form-data" class="row g-3 align-items-center">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="save">
            <input type="hidden" name="id" value="<?= e($item['id']) ?>">
            <div class="col-md-3"><img src="<?= e(upload_url($item['image'])) ?>" alt="<?= e($item['alt'] ?? '') ?>" class="img-fluid rounded" style="max-height:120px;object-fit:contain"></div>
            <div class="col-md-4"><label class="form-label">Image description</label><input name="alt" class="form-control" maxlength="190" value="<?= e($item['alt'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label">Replace image</label><input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif"></div>
            <div class="col-md-1"><label class="form-label">Order</label><input type="number" name="sort_order" class="form-control" value="<?= (int)$item['sort_order'] ?>"></div>
            <div class="col-md-2"><label class="form-check"><input type="checkbox" name="active" class="form-check-input" <?= !empty($item['active']) ? 'checked' : '' ?>><span class="form-check-label">Show slide</span></label></div>
            <div class="col-12"><button class="btn btn-sm btn-primary">Save</button></div>
          </form>
          <form method="post" class="mt-2" onsubmit="return confirm('Remove this homepage slide?')">
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
