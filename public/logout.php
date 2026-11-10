<?php
require_once __DIR__ . '/../includes/auth.php';
customer_logout();
flash('success', 'You have been logged out.');
redirect(frontend_url('login'));
