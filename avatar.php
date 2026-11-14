<?php
// Upload or remove a profile picture (POST only), then go back.
require_once __DIR__ . '/includes/avatar.php';

$user = require_login();
$back = safe_next($_POST['back'] ?? null, 'account.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_error(t('Your session expired. Please try again.'));
    redirect($back);
}

if (($_POST['action'] ?? '') === 'remove') {
    avatar_remove((int) $user['id']);
    flash(t('Profile picture removed.'));
} else {
    $error = avatar_save((int) $user['id'], $_FILES['avatar'] ?? null);
    $error ? flash_error(t($error)) : flash(t('Profile picture updated.'));
}
redirect($back);
