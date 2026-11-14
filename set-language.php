<?php
// Switch site language (used by the footer picker), then go back to the same page.
require_once __DIR__ . '/includes/auth.php';

$code = (string) ($_GET['lang'] ?? '');
if (isset(LANGUAGES[$code])) {
    set_lang($code);
    if ($user = current_user()) {
        db()->prepare('UPDATE users SET language = ? WHERE id = ?')->execute([$code, $user['id']]);
    }
}
redirect(safe_next($_GET['back'] ?? null, 'index.php'));
