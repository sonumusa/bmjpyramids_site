<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_capability('categories_view');
$pageTitle = 'Categories';
$canWrite = admin_can('categories_write');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (!$canWrite) {
        http_response_code(403);
        die('Read-only role cannot modify categories.');
    }

    $postAction = $_POST['post_action'] ?? 'save_category';
    try {
        if ($postAction === 'delete_category') {
            $id = (int)($_POST['id'] ?? 0);
            $children = db()->prepare('SELECT COUNT(*) FROM categories WHERE parent_id = ?');
            $children->execute([$id]);
            $products = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $products->execute([$id]);
            if ((int)$children->fetchColumn() > 0 || (int)$products->fetchColumn() > 0) {
                throw new RuntimeException('Cannot delete category with child categories or assigned products.');
            }
            db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('success', 'Category deleted.');
            redirect(admin_url('categories.php'));
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            throw new RuntimeException('Category name is required.');
        }
        $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
        if ($parentId && $parentId === $id) {
            throw new RuntimeException('A category cannot be its own parent.');
        }
        if ($parentId && category_depth($parentId) > 2) {
            throw new RuntimeException('Only 3 levels are allowed: Parent > Child > Sub-child.');
        }
        $slug = unique_slug('categories', trim($_POST['slug'] ?? '') ?: $name, $id ?: null);
        $description = trim($_POST['description'] ?? '');
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

        if ($id) {
            $stmt = db()->prepare('UPDATE categories SET parent_id=?, name=?, slug=?, description=?, meta_title=?, meta_description=?, sort_order=?, status=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$parentId, $name, $slug, $description, $metaTitle, $metaDescription, $sortOrder, $status, $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO categories (parent_id, name, slug, description, meta_title, meta_description, sort_order, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
            $stmt->execute([$parentId, $name, $slug, $description, $metaTitle, $metaDescription, $sortOrder, $status]);
        }
        flash('success', 'Category saved.');
        redirect(admin_url('categories.php'));
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
        redirect(admin_url('categories.php'));
    }
}

$categories = get_categories_flat(false);
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

function admin_category_rows(array $categories, ?int $parentId, int $level, bool $canWrite): void
{
    foreach ($categories as $cat) {
        if ((int)($cat['parent_id'] ?? 0) !== (int)($parentId ?? 0)) {
            continue;
        }
        $prefix = str_repeat('— ', $level);
        echo '<tr>';
        echo '<td><strong>' . e($prefix . $cat['name']) . '</strong><br><small class="text-muted">/' . e($cat['slug']) . '</small></td>';
        echo '<td>' . e($level + 1) . '</td>';
        echo '<td><span class="badge bg-' . ($cat['status'] === 'active' ? 'success' : 'secondary') . '">' . e($cat['status']) . '</span></td>';
        echo '<td>' . (int)$cat['sort_order'] . '</td>';
        echo '<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="' . e(admin_url('categories.php?edit=' . $cat['id'])) . '">' . ($canWrite ? 'Edit' : 'View') . '</a> ';
        if ($canWrite) {
            echo '<form method="post" class="d-inline" data-confirm="Delete this category?">' . csrf_field() . '<input type="hidden" name="post_action" value="delete_category"><input type="hidden" name="id" value="' . (int)$cat['id'] . '"><button class="btn btn-sm btn-outline-danger">Delete</button></form>';
        }
        echo '</td></tr>';
        admin_category_rows($categories, (int)$cat['id'], $level + 1, $canWrite);
    }
}

include __DIR__ . '/_header.php';
?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card-soft p-3">
      <h2 class="h5 fw-bold mb-3">3-Level Category Structure</h2>
      <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Level</th><th>Status</th><th>Sort</th><th class="text-end">Actions</th></tr></thead><tbody>
        <?php admin_category_rows($categories, null, 0, $canWrite); ?>
        <?php if (!$categories): ?><tr><td colspan="5" class="text-center text-muted py-5">No categories yet.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-soft p-4">
      <h2 class="h5 fw-bold mb-3"><?= $edit ? 'Edit Category' : 'Add Category' ?></h2>
      <?php if (!$canWrite): ?><div class="readonly-overlay mb-3">Viewer mode: category editing disabled.</div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="post_action" value="save_category">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <div class="mb-3"><label class="form-label">Category Name *</label><input name="name" class="form-control" required value="<?= e($edit['name'] ?? '') ?>" <?= !$canWrite ? 'disabled' : '' ?>></div>
        <div class="mb-3"><label class="form-label">Parent Category</label><select name="parent_id" class="form-select" <?= !$canWrite ? 'disabled' : '' ?>><option value="0">Root category</option><?= render_category_options($categories, $edit['parent_id'] ?? null, null, 0, $edit['id'] ?? null) ?></select><small class="text-muted">Max: Parent → Child → Sub-child.</small></div>
        <div class="mb-3"><label class="form-label">Slug</label><input name="slug" class="form-control" value="<?= e($edit['slug'] ?? '') ?>" placeholder="auto-generated" <?= !$canWrite ? 'disabled' : '' ?>></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" <?= !$canWrite ? 'disabled' : '' ?>><?= e($edit['description'] ?? '') ?></textarea></div>
        <div class="row g-3">
          <div class="col-6"><label class="form-label">Sort Order</label><input type="number" name="sort_order" class="form-control" value="<?= (int)($edit['sort_order'] ?? 0) ?>" <?= !$canWrite ? 'disabled' : '' ?>></div>
          <div class="col-6"><label class="form-label">Status</label><select name="status" class="form-select" <?= !$canWrite ? 'disabled' : '' ?>><option value="active" <?= (($edit['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option><option value="inactive" <?= (($edit['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option></select></div>
        </div>
        <hr>
        <h3 class="h6 fw-bold">SEO</h3>
        <div class="mb-2"><label class="form-label">Meta Title</label><input name="meta_title" class="form-control" value="<?= e($edit['meta_title'] ?? '') ?>" <?= !$canWrite ? 'disabled' : '' ?>></div>
        <div class="mb-3"><label class="form-label">Meta Description</label><textarea name="meta_description" class="form-control" rows="3" <?= !$canWrite ? 'disabled' : '' ?>><?= e($edit['meta_description'] ?? '') ?></textarea></div>
        <?php if ($canWrite): ?><button class="btn btn-primary w-100">Save Category</button><?php endif; ?>
        <?php if ($edit): ?><a href="<?= e(admin_url('categories.php')) ?>" class="btn btn-link w-100 mt-2">Clear form</a><?php endif; ?>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
