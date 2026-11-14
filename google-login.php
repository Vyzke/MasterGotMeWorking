<?php
// Step 1: send the visitor to Google's sign-in page.
require_once __DIR__ . '/includes/google.php';

if (current_user()) redirect('account.php');

if (!google_enabled()) {
    flash(t('Google sign-in isn’t set up yet. Add your Google client ID and secret to config/config.php.'));
    redirect('login.php');
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_state'] = $state;
$_SESSION['google_next']  = safe_next($_GET['next'] ?? null);

redirect(google_auth_url($state));
