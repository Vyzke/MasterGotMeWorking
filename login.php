<?php
require_once __DIR__ . '/includes/google.php';

$next = safe_next($_POST['next'] ?? $_GET['next'] ?? null);
if (current_user()) redirect($next);

$error = flash();   // e.g. a message from the Google sign-in step
$email = '';
$lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($lockedUntil > time()) {
        $error = t('Too many failed attempts. Please wait {n} minute(s) and try again.', ['n' => ceil(($lockedUntil - time()) / 60)]);
    } elseif (!csrf_valid()) {
        $error = t('Your session expired. Please try again.');
    } elseif ($email === '' || $password === '') {
        $error = t('Enter your email and password.');
    } else {
        try {
            $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $row = $stmt->fetch();

            // Always run password_verify so response time doesn't reveal which emails exist
            $hash = $row['password_hash'] ?? password_hash(random_bytes(16), PASSWORD_DEFAULT);
            if (password_verify($password, $hash) && $row) {
                if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
                    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
                }
                db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$row['id']]);
                login_user((int) $row['id']);
                redirect($next);
            }

            $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
            if ($_SESSION['login_fails'] >= LOGIN_MAX_ATTEMPTS) {
                $_SESSION['login_locked_until'] = time() + LOGIN_LOCK_SECONDS;
                $_SESSION['login_fails'] = 0;
                $error = t('Too many failed attempts. Please wait {n} minute(s) and try again.', ['n' => 5]);
            } else {
                $error = t('That email and password don’t match an account.');
            }
        } catch (PDOException $e) {
            $error = t('Log-in is unavailable because the database isn’t connected.');
        }
    }
}

$pageTitle  = t('Log in') . ' — uni wise';
$authNav    = 'login';
$hideFooter = true;
require __DIR__ . '/includes/header.php';
?>
  <main class="auth">
    <aside class="auth-side">
      <h2><?= te('Welcome back. Your plan is right where you left it.') ?></h2>
      <ul class="check-list">
        <li><?= icon('check') ?><?= te('Your saved universities and comparisons') ?></li>
        <li><?= icon('check') ?><?= te('Every deadline in one timeline') ?></li>
        <li><?= icon('check') ?><?= te('Milestones, portfolio and letters') ?></li>
      </ul>
    </aside>

    <div class="auth-main">
      <form class="auth-form" action="login.php" method="post" novalidate>
        <h1><?= te('Log in') ?></h1>
        <p><?= te('Good to see you again.') ?></p>

        <?php if ($error): ?>
          <div class="alert alert-error" role="alert"><?= icon('info') ?><span><?= e($error) ?></span></div>
        <?php endif; ?>

        <div class="social-auth"><?= google_button('Continue with Google', $next) ?></div>

        <div class="form-stack">
          <?= csrf_field() ?>
          <input type="hidden" name="next" value="<?= e($next) ?>" />
          <div class="field"><label class="label" for="email"><?= te('Email') ?></label><input id="email" class="input" type="email" name="email" value="<?= e($email) ?>" autocomplete="email" placeholder="you@example.com" required /></div>
          <div class="field"><label class="label" for="password"><?= te('Password') ?></label><input id="password" class="input" type="password" name="password" autocomplete="current-password" required /></div>
          <button class="btn btn-primary btn-block btn-lg" type="submit"><?= te('Log in') ?></button>
        </div>

        <p class="small muted" style="margin-top:24px;text-align:center"><?= te('New to uni wise?') ?> <a href="signup.php" class="text-link"><?= te('Create a free account') ?></a></p>
      </form>
    </div>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
