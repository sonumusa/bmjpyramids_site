<?php
require_once __DIR__ . '/../includes/auth.php';

if (admin_current_user()) {
    redirect(admin_url('index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if (admin_login($email, $password)) {
        flash('success', 'Welcome back.');
        redirect(admin_url('index.php'));
    }
    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login | BMJ Pyramids LLP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(admin_asset_url('css/admin.css')) ?>">
</head>
<body class="d-flex align-items-center" style="min-height:100vh;background:linear-gradient(135deg,#0f172a,#005b9f);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5 col-lg-4">
        <div class="card card-soft p-4">
          <div class="text-center mb-4">
            <img src="<?= e(asset_url('images/logo.png')) ?>" alt="BMJ logo" style="width:82px;height:86px;object-fit:contain">
            <h1 class="h4 fw-bold mt-2">BMJ Admin Login</h1>
            <p class="text-muted mb-0">Manage products, stock and quote requests.</p>
          </div>
          <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="admin@bmj.com"></div>
            <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required placeholder="admin123"></div>
            <button class="btn btn-primary w-100 py-2">Login</button>
          </form>
          <p class="small text-muted mt-3 mb-0">Default: admin@bmj.com / admin123. Change immediately after first login.</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
