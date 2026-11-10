<?php
require_once __DIR__ . '/../includes/auth.php';
$pageKey = 'login';
$seo = ['title'=>'Customer Login | BMJ Pyramids LLP','description'=>'Login to view your BMJ Pyramids quote order history.','keywords'=>'','og_image'=>''];
if (customer_current()) redirect(frontend_url('my-account'));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (customer_login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        flash('success', 'Welcome back.');
        redirect(frontend_url('my-account'));
    }
    $error = 'Invalid email or password.';
}
include __DIR__ . '/_header.php';
?>
<section class="py-5"><div class="container"><div class="row justify-content-center"><div class="col-md-6 col-lg-5"><div class="soft-card p-4"><h1 class="h3 fw-bold mb-3">Customer Login</h1><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div><div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div><button class="btn btn-primary w-100">Login</button></form><p class="mt-3 mb-0 text-muted">No account? <a href="<?= e(frontend_url('signup')) ?>">Create one</a>.</p></div></div></div></div></section>
<?php include __DIR__ . '/_footer.php'; ?>
