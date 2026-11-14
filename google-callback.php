<?php
// Step 2: Google sends the visitor back here with a one-time ?code=…
require_once __DIR__ . '/includes/google.php';

$expected = $_SESSION['google_state'] ?? '';
$next     = $_SESSION['google_next'] ?? 'account.php';
unset($_SESSION['google_state'], $_SESSION['google_next']);

$state = (string) ($_GET['state'] ?? '');
$code  = (string) ($_GET['code'] ?? '');

if (!google_enabled()) {
    redirect('login.php');
}
if (isset($_GET['error'])) {                       // user pressed "Cancel" on Google
    flash(t('Google sign-in was cancelled.'));
    redirect('login.php');
}
if ($expected === '' || !hash_equals($expected, $state) || $code === '') {
    flash(t('That sign-in link expired. Please try again.'));
    redirect('login.php');
}

try {
    $profile = google_fetch_profile($code);
    if (empty($profile['sub']) || empty($profile['email']) || empty($profile['email_verified'])) {
        flash(t('Your Google account needs a verified email address to sign in.'));
        redirect('login.php');
    }
    $userId = google_find_or_create_user($profile);
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$userId]);
    login_user($userId);
    redirect(safe_next($next));
} catch (Throwable $e) {
    error_log('Google sign-in failed: ' . $e->getMessage());
    flash(t('We couldn’t sign you in with Google. Please try again.'));
    redirect('login.php');
}
