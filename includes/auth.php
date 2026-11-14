<?php
// Sessions, CSRF protection and the logged-in user.
// Include this BEFORE any HTML output (header.php does it for you).
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('uniwise_session');
    session_set_cookie_params([
        'lifetime' => 0,                      // until the browser closes
        'path'     => '/',
        'httponly' => true,                   // JS can't read the cookie
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 300;

/** Per-session CSRF token for every POST form. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '" />';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf'] ?? null;
    return is_string($sent) && hash_equals(csrf_token(), $sent);
}

/** The logged-in user's row, or null for guests. */
function current_user(): ?array
{
    static $loaded = false, $user = null;
    if ($loaded) return $user;
    $loaded = true;

    if (empty($_SESSION['user_id'])) return null;
    try {
        $stmt = db()->prepare('
            SELECT u.id, u.first_name, u.last_name, u.email, u.region_id, u.start_year, u.language,
                   u.avatar_path, u.bio, u.school,
                   u.notify_deadlines, u.notify_days, u.notify_weekly, u.notify_news,
                   u.pref_regions, u.pref_majors, u.pref_budget, u.pref_study_level,
                   u.created_at, u.last_login_at, r.name AS region_name,
                   (u.password_hash IS NOT NULL) AS has_password, (u.google_id IS NOT NULL) AS has_google
            FROM users u LEFT JOIN regions r ON r.id = u.region_id
            WHERE u.id = ?');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        if (!$user) unset($_SESSION['user_id']);   // account was deleted
    } catch (PDOException $e) {
        $user = null;
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);   // prevent session fixation
    $_SESSION['user_id'] = $userId;
    unset($_SESSION['login_fails'], $_SESSION['login_locked_until']);

    // Bring the user's saved language with them to this browser
    try {
        $stmt = db()->prepare('SELECT language FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $lang = $stmt->fetchColumn();
        if ($lang) set_lang($lang);
    } catch (PDOException $e) {
        // not critical
    }
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Send guests to the login page, then back here afterwards. */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('login.php?next=' . urlencode(basename($_SERVER['SCRIPT_NAME'])));
    }
    return $user;
}

/** Only allow redirects to our own pages (stops open-redirect tricks). */
function safe_next($next, string $fallback = 'account.php'): string
{
    $next = is_string($next) ? $next : '';
    return preg_match('/^[a-z0-9-]+\.php(\?[A-Za-z0-9=&%._+-]*)?$/', $next) ? $next : $fallback;
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/** "Brian Y." → "BY" */
function user_initials(array $user): string
{
    return mb_strtoupper(mb_substr($user['first_name'], 0, 1) . mb_substr($user['last_name'], 0, 1));
}

/** Round profile picture, or initials when there's no photo. $size: '' | 'lg' | 'xl' */
function user_avatar(array $user, string $size = ''): string
{
    $cls = trim('avatar ' . $size);
    if (!empty($user['avatar_path']) && is_file(__DIR__ . '/../' . $user['avatar_path'])) {
        return '<span class="' . $cls . ' has-photo"><img src="' . e($user['avatar_path']) . '" alt="" /></span>';
    }
    return '<span class="' . $cls . '">' . e(user_initials($user)) . '</span>';
}

/** One-time error message shown on the next page load. */
function flash_error(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash_error'] = $message;
        return null;
    }
    $m = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_error']);
    return $m;
}

/** One-time message shown on the next page load. */
function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}
