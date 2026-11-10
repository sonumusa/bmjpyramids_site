<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail.php';
$pageKey = 'contact';
$seo = get_seo('contact', 'Contact BMJ Pyramids LLP | Request Industrial Quote', 'Contact BMJ Pyramids LLP for industrial tools, bearings and B2B quote requests.');
$productSlug = trim($_GET['product'] ?? '');
$form = ['name'=>'','email'=>'','phone'=>'','company'=>'','subject'=>$productSlug ? 'Question about product: ' . $productSlug : '','message'=>''];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($form as $k=>$v) $form[$k] = trim($_POST[$k] ?? '');
    if ($form['name']==='' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL) || $form['message']==='') $errors[]='Name, valid email and message are required.';
    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO contact_messages (name, email, phone, company, subject, message, created_at) VALUES (?,?,?,?,?,?,NOW())');
            $stmt->execute([$form['name'], $form['email'], $form['phone'], $form['company'], $form['subject'], $form['message']]);
            $html = '<h2>New Contact Inquiry</h2><p><strong>Name:</strong> '.e($form['name']).'<br><strong>Email:</strong> '.e($form['email']).'<br><strong>Phone:</strong> '.e($form['phone']).'<br><strong>Company:</strong> '.e($form['company']).'</p><p><strong>Subject:</strong> '.e($form['subject']).'</p><p>'.nl2br(e($form['message'])).'</p>';
            send_site_email(ADMIN_EMAIL, 'BMJ Contact Inquiry: ' . ($form['subject'] ?: $form['name']), $html, $form['email']);
            flash('success', 'Thank you. Your inquiry has been sent.');
            redirect(frontend_url('contact'));
        } catch (Throwable $e) { $errors[]='Could not submit your inquiry. Please call or email us.'; }
    }
}
include __DIR__ . '/_header.php';
?>
<header class="page-header"><div class="container"><h1 class="display-6 fw-bold mb-0">Contact BMJ Pyramids LLP</h1><p class="mb-0 mt-2 opacity-75">Request a quote, ask about stock or discuss bulk procurement.</p></div></header>
<section class="py-5"><div class="container"><div class="row g-5"><div class="col-lg-5"><div class="section-kicker">Get in Touch</div><h2 class="section-title">Send your inquiry.</h2><p class="text-muted">Our team will respond with availability, quote details and next steps.</p><div class="soft-card p-4"><p><strong>Phone:</strong><br><?= e(get_setting('company_phone', SITE_PHONE)) ?></p><p><strong>Email:</strong><br><?= e(get_setting('company_email', SITE_EMAIL)) ?></p><p class="mb-0"><strong>Address:</strong><br><?= nl2br(e(get_setting('company_address', SITE_ADDRESS))) ?></p></div></div><div class="col-lg-7"><div class="soft-card p-4"><?php if($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="row g-3"><div class="col-md-6"><label class="form-label">Name *</label><input name="name" class="form-control" required value="<?= e($form['name']) ?>"></div><div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required value="<?= e($form['email']) ?>"></div><div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($form['phone']) ?>"></div><div class="col-md-6"><label class="form-label">Company</label><input name="company" class="form-control" value="<?= e($form['company']) ?>"></div><div class="col-12"><label class="form-label">Subject</label><input name="subject" class="form-control" value="<?= e($form['subject']) ?>"></div><div class="col-12"><label class="form-label">Message *</label><textarea name="message" class="form-control" rows="6" required><?= e($form['message']) ?></textarea></div></div><button class="btn btn-primary mt-4">Send Inquiry</button></form></div></div></div></div></section>
<?php include __DIR__ . '/_footer.php'; ?>
