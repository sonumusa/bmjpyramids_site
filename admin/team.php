<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_capability('team_write');
$pageTitle = 'Team & Roles';
$roles = ['super_admin' => 'Super Admin', 'manager' => 'Manager', 'viewer' => 'Viewer'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = $_POST['post_action'] ?? 'save_user';
    try {
        if ($postAction === 'delete_user') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)admin_current_user()['id']) {
                throw new RuntimeException('You cannot delete your own account.');
            }
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('success', 'User deleted.');
            redirect(admin_url('team.php'));
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'viewer';
        $status = $_POST['status'] ?? 'active';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Name and valid email are required.');
        }
        if (!array_key_exists($role, $roles)) {
            $role = 'viewer';
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }
        if ($id) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = db()->prepare('UPDATE users SET name=?, email=?, password_hash=?, role=?, status=?, updated_at=NOW() WHERE id=?');
                $stmt->execute([$name, $email, $hash, $role, $status, $id]);
            } else {
                $stmt = db()->prepare('UPDATE users SET name=?, email=?, role=?, status=?, updated_at=NOW() WHERE id=?');
                $stmt->execute([$name, $email, $role, $status, $id]);
            }
        } else {
            if ($password === '') {
                throw new RuntimeException('Password is required for new users.');
            }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role, status, created_at, updated_at) VALUES (?,?,?,?,?,NOW(),NOW())');
            $stmt->execute([$name, $email, $hash, $role, $status]);
        }
        flash('success', 'Team user saved.');
        redirect(admin_url('team.php'));
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
        redirect(admin_url('team.php'));
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}
$users = db()->query('SELECT * FROM users ORDER BY role, name')->fetchAll();
include __DIR__ . '/_header.php';
?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card-soft p-3">
      <h2 class="h5 fw-bold mb-3">Admin Users</h2>
      <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th></th></tr></thead><tbody>
      <?php foreach ($users as $u): ?><tr><td><strong><?= e($u['name']) ?></strong></td><td><?= e($u['email']) ?></td><td><span class="badge bg-info text-dark badge-role"><?= e($roles[$u['role']] ?? $u['role']) ?></span></td><td><span class="badge bg-<?= $u['status']==='active'?'success':'secondary' ?>"><?= e($u['status']) ?></span></td><td><?= $u['last_login_at'] ? e(date('d M Y', strtotime($u['last_login_at']))) : '—' ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(admin_url('team.php?edit=' . $u['id'])) ?>">Edit</a><?php if ((int)$u['id'] !== (int)admin_current_user()['id']): ?> <form method="post" class="d-inline" data-confirm="Delete this user?"><?= csrf_field() ?><input type="hidden" name="post_action" value="delete_user"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-soft p-4">
      <h2 class="h5 fw-bold mb-3"><?= $edit ? 'Edit User' : 'Add User' ?></h2>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="post_action" value="save_user">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required value="<?= e($edit['name'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="<?= e($edit['email'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label"><?= $edit ? 'New Password (leave blank to keep)' : 'Password' ?></label><input type="password" name="password" class="form-control" <?= $edit ? '' : 'required' ?>></div>
        <div class="row g-3">
          <div class="col-6"><label class="form-label">Role</label><select name="role" class="form-select"><?php foreach ($roles as $key=>$label): ?><option value="<?= e($key) ?>" <?= (($edit['role'] ?? 'viewer')===$key)?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
          <div class="col-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?= (($edit['status'] ?? 'active')==='active')?'selected':'' ?>>Active</option><option value="inactive" <?= (($edit['status'] ?? '')==='inactive')?'selected':'' ?>>Inactive</option></select></div>
        </div>
        <button class="btn btn-primary w-100 mt-4">Save User</button>
        <?php if ($edit): ?><a href="<?= e(admin_url('team.php')) ?>" class="btn btn-link w-100 mt-2">Clear form</a><?php endif; ?>
      </form>
      <hr>
      <h3 class="h6 fw-bold">Role Permissions</h3>
      <ul class="small text-muted mb-0">
        <li><strong>Super Admin:</strong> Full access, team, SEO and status changes.</li>
        <li><strong>Manager:</strong> Add/edit products and categories, view orders.</li>
        <li><strong>Viewer:</strong> Read-only access.</li>
      </ul>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
