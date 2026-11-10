<?php
require_once __DIR__ . '/functions.php';

function verify_password_with_upgrade(string $table, int $id, string $password, string $storedHash): bool
{
    if (password_verify($password, $storedHash)) {
        if (password_needs_rehash($storedHash, PASSWORD_BCRYPT)) {
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = db()->prepare("UPDATE {$table} SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newHash, $id]);
        }
        return true;
    }

    // Backward-compatible first-login migration for any imported SHA-256 bootstrap hash.
    if (preg_match('/^[a-f0-9]{64}$/', $storedHash) && hash_equals($storedHash, hash('sha256', $password))) {
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = db()->prepare("UPDATE {$table} SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newHash, $id]);
        return true;
    }

    return false;
}

function admin_current_user(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([(int)$_SESSION['admin_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function admin_login(string $email, string $password): bool
{
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();
    if (!$user || !verify_password_with_upgrade('users', (int)$user['id'], $password, $user['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$user['id'];
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
    return true;
}

function admin_logout(): void
{
    unset($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_current_user()) {
        redirect(admin_url('login.php'));
    }
}

function admin_can(string $capability): bool
{
    $user = admin_current_user();
    if (!$user) {
        return false;
    }
    $role = $user['role'];
    if ($role === 'super_admin') {
        return true;
    }

    $matrix = [
        'manager' => ['dashboard_view', 'products_view', 'products_write', 'categories_view', 'categories_write', 'orders_view', 'company_logos_write', 'homepage_slides_write'],
        'viewer' => ['dashboard_view', 'products_view', 'categories_view', 'orders_view'],
    ];

    return in_array($capability, $matrix[$role] ?? [], true);
}

function require_admin_capability(string $capability): void
{
    require_admin();
    if (!admin_can($capability)) {
        http_response_code(403);
        die('You do not have permission to access this section.');
    }
}

function customer_current(): ?array
{
    if (empty($_SESSION['customer_id'])) {
        return null;
    }
    static $customer = null;
    if ($customer === null) {
        $stmt = db()->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$_SESSION['customer_id']]);
        $customer = $stmt->fetch() ?: null;
    }
    return $customer;
}

function customer_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM customers WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $customer = $stmt->fetch();
    if (!$customer || !verify_password_with_upgrade('customers', (int)$customer['id'], $password, $customer['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['customer_id'] = (int)$customer['id'];
    return true;
}

function customer_logout(): void
{
    unset($_SESSION['customer_id']);
}

function require_customer(): void
{
    if (!customer_current()) {
        flash('warning', 'Please login to continue.');
        redirect(frontend_url('login'));
    }
}
