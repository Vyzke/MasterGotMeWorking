<?php
// Log out (POST only, with CSRF token, so links/images can't log people out).
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    logout_user();
}
redirect('index.php');
