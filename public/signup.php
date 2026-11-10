<?php
require_once __DIR__ . '/../includes/auth.php';
$pageKey = 'signup';
$seo = ['title'=>'Create Account | BMJ Pyramids LLP','description'=>'Create a BMJ Pyramids customer account for quote history and reorder.','keywords'=>'','og_image'=>''];
if (customer_current()) redirect(frontend_url('my-account'));
$errors = [];
$form = ['name'=>'','email'=>'','phone'=>'','company_name'=>'','address'=>'','city'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($form as $k=>$v) $form[$k] = trim($_POST[$k] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if ($form['name']==='' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL) || $password==='') $errors[]='Name, valid email and password are required.';
    if (strlen($password) < 6) $errors[]='Password must be at least 6 characters.';
    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO customers (name, email, phone, company_name, address, city, password_hash, created_at, updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())');
            $stmt->execute([$form['name'], strtolower($form['email']), $form['phone'], $form['company_name'], $form['address'], $form['city'], password_hash($password, PASSWORD_BCRYPT)]);
            customer_login($form['email'], $password);
            flash('success', 'Account created successfully.');
            redirect(frontend_url('my-account'));
        } catch (Throwable $e) { $errors[] = 'Email may already be registered.'; }
    }
}
include __DIR__ . '/_header.php';
?>
<section class="py-5"><div class="container"><div class="row justify-content-center"><div class="col-lg-7"><div class="soft-card p-4"><h1 class="h3 fw-bold mb-3">Create Customer Account</h1><?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="row g-3"><div class="col-md-6"><label class="form-label">Name *</label><input name="name" class="form-control" required value="<?= e($form['name']) ?>"></div><div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required value="<?= e($form['email']) ?>"></div><div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($form['phone']) ?>"></div><div class="col-md-6"><label class="form-label">Company</label><input name="company_name" class="form-control" value="<?= e($form['company_name']) ?>"></div><div class="col-md-6"><label class="form-label">City</label><input name="city" class="form-control" value="<?= e($form['city']) ?>"></div><div class="col-md-6"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required></div><div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="3"><?= e($form['address']) ?></textarea></div></div><button class="btn btn-primary mt-4">Create Account</button></form><p class="mt-3 mb-0 text-muted">Already registered? <a href="<?= e(frontend_url('login')) ?>">Login</a>.</p></div></div></div></div></section>
<?php include __DIR__ . '/_footer.php'; ?>
