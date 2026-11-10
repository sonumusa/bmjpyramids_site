<?php
require_once __DIR__ . '/../includes/auth.php';
admin_logout();
flash('success', 'You have been logged out.');
redirect(admin_url('login.php'));
