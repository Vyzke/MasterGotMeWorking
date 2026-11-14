<?php
// "Sign in with Google" (OAuth 2.0 / OpenID Connect, authorisation-code flow).
// Needs google_client_id + google_client_secret in config/config.php.
require_once __DIR__ . '/auth.php';

function google_enabled(): bool
{
    $c = app_config();
    return !empty($c['google_client_id']) && !empty($c['google_client_secret']);
}

/** The URL Google sends people back to. Must be listed in Google Cloud exactly. */
function google_redirect_uri(): string
{
    $c = app_config();
    if (!empty($c['google_redirect_uri'])) {
        return $c['google_redirect_uri'];
    }
    $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $path   = implode('/', array_map('rawurlencode', explode('/', $dir)));
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $path . '/google-callback.php';
}

function google_auth_url(string $state): string
{
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => app_config()['google_client_id'],
        'redirect_uri'  => google_redirect_uri(),
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'prompt'        => 'select_account',
    ]);
}

/** Small JSON-over-HTTPS helper. Returns decoded array or throws. */
function google_http(string $url, ?array $post = null, ?string $bearer = null): array
{
    $ch = curl_init($url);
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15];
    if ($post !== null) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($post);
    }
    if ($bearer !== null) {
        $opts[CURLOPT_HTTPHEADER] = ['Authorization: Bearer ' . $bearer];
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $data = is_string($body) ? json_decode($body, true) : null;
    if ($body === false || $status >= 400 || !is_array($data)) {
        throw new RuntimeException('Google request failed: ' . ($err ?: "HTTP $status"));
    }
    return $data;
}

/** Trade the one-time code for the user's Google profile. */
function google_fetch_profile(string $code): array
{
    $c = app_config();
    $token = google_http('https://oauth2.googleapis.com/token', [
        'code'          => $code,
        'client_id'     => $c['google_client_id'],
        'client_secret' => $c['google_client_secret'],
        'redirect_uri'  => google_redirect_uri(),
        'grant_type'    => 'authorization_code',
    ]);
    if (empty($token['access_token'])) {
        throw new RuntimeException('Google did not return an access token.');
    }
    return google_http('https://openidconnect.googleapis.com/v1/userinfo', null, $token['access_token']);
}

/** Find the matching account (by Google id, then by email) or create one. Returns user id. */
function google_find_or_create_user(array $profile): int
{
    $pdo   = db();
    $sub   = (string) ($profile['sub'] ?? '');
    $email = mb_strtolower((string) ($profile['email'] ?? ''));

    $stmt = $pdo->prepare('SELECT id FROM users WHERE google_id = ?');
    $stmt->execute([$sub]);
    if ($id = $stmt->fetchColumn()) {
        return (int) $id;
    }

    // Existing email/password account → link it (Google has verified this email)
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($id = $stmt->fetchColumn()) {
        $pdo->prepare('UPDATE users SET google_id = ? WHERE id = ?')->execute([$sub, $id]);
        return (int) $id;
    }

    $first = trim((string) ($profile['given_name'] ?? '')) ?: trim((string) ($profile['name'] ?? '')) ?: 'Student';
    $last  = trim((string) ($profile['family_name'] ?? ''));
    $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, google_id) VALUES (?, ?, ?, NULL, ?)')
        ->execute([mb_substr($first, 0, 50), mb_substr($last, 0, 50), $email, $sub]);
    return (int) $pdo->lastInsertId();
}

/** Google "G" logo for the button. */
function google_logo(): string
{
    return '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.6 12.3c0-.8-.1-1.6-.2-2.3H12v4.4h5.9a5 5 0 0 1-2.2 3.3v2.7h3.6c2.1-1.9 3.3-4.8 3.3-8.1z"/><path fill="#34A853" d="M12 23c3 0 5.5-1 7.3-2.7l-3.6-2.7c-1 .7-2.2 1.1-3.7 1.1-2.9 0-5.3-1.9-6.2-4.5H2.1v2.8A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.8 14.2a6.6 6.6 0 0 1 0-4.3V7.1H2.1a11 11 0 0 0 0 9.9z"/><path fill="#EA4335" d="M12 5.4c1.6 0 3.1.6 4.2 1.7l3.2-3.2A11 11 0 0 0 2.1 7.1l3.7 2.8C6.7 7.3 9.1 5.4 12 5.4z"/></svg>';
}

/** "Continue with Google" button + divider, for login/signup forms. */
function google_button(string $label, string $next = ''): string
{
    $href = 'google-login.php' . ($next !== '' ? '?next=' . urlencode($next) : '');
    return '<a class="btn btn-social btn-block" href="' . e($href) . '">' . google_logo() . te($label) . '</a>'
        . '<div class="or">' . te('or use your email') . '</div>';
}
