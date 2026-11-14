<?php
require_once __DIR__ . '/includes/google.php';

if (current_user()) redirect('account.php');

$errors  = [];
$dbError = false;
$regions = [];
$old     = ['first_name' => '', 'last_name' => '', 'email' => '', 'region_id' => '', 'start_year' => ''];
$thisYear = (int) date('Y');
$years    = range($thisYear, $thisYear + 4);

try {
    $regions = db()->query('SELECT id, name FROM regions ORDER BY sort_order, name')->fetchAll();
} catch (PDOException $e) {
    $dbError = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$dbError) {
    foreach ($old as $k => $_) {
        $old[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $old['email'] = mb_strtolower($old['email']);
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_valid()) {
        $errors[] = t('Your session expired. Please submit the form again.');
    }
    if ($old['first_name'] === '' || mb_strlen($old['first_name']) > 50) {
        $errors[] = t('Enter your first name (up to 50 characters).');
    }
    if ($old['last_name'] === '' || mb_strlen($old['last_name']) > 50) {
        $errors[] = t('Enter your last name (up to 50 characters).');
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 190) {
        $errors[] = t('Enter a valid email address.');
    }
    if (strlen($password) < 8) {
        $errors[] = t('Your password needs at least 8 characters.');
    } elseif (strlen($password) > 72) {
        $errors[] = t('Your password can be at most 72 characters.');
    }
    $regionId = $old['region_id'] === '' ? null : (int) $old['region_id'];
    if ($regionId !== null && !in_array($regionId, array_map('intval', array_column($regions, 'id')), true)) {
        $errors[] = t('Choose a region from the list.');
    }
    $startYear = $old['start_year'] === '' ? null : (int) $old['start_year'];
    if ($startYear !== null && !in_array($startYear, $years, true)) {
        $errors[] = t('Choose a start year from the list.');
    }
    if (empty($_POST['terms'])) {
        $errors[] = t('Please accept the Terms and Privacy Policy.');
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO users (first_name, last_name, email, password_hash, region_id, start_year, language)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $old['first_name'], $old['last_name'], $old['email'],
                password_hash($password, PASSWORD_DEFAULT), $regionId, $startYear, current_lang(),
            ]);
            login_user((int) db()->lastInsertId());
            flash(t('Welcome to uni wise, {name}! Your account is ready.', ['name' => $old['first_name']]));
            redirect('account.php');
        } catch (PDOException $e) {
            $errors[] = $e->getCode() === '23000'
                ? t('An account with that email already exists. Try logging in instead.')
                : t('Something went wrong creating your account. Please try again.');
        }
    }
}

$pageTitle  = t('Create account') . ' — uni wise';
$authNav    = 'signup';
$hideFooter = true;
require __DIR__ . '/includes/header.php';
?>
  <main class="auth">
    <aside class="auth-side">
      <h2><?= te('Your applications, organised in one place.') ?></h2>
      <ul class="check-list">
        <li><?= icon('check') ?><?= te('Research and compare universities') ?></li>
        <li><?= icon('check') ?><?= te('Track deadlines across every portal') ?></li>
        <li><?= icon('check') ?><?= te('Plan milestones, your portfolio and recommendation letters') ?></li>
      </ul>
    </aside>

    <div class="auth-main">
      <form class="auth-form" action="signup.php" method="post" novalidate>
        <h1><?= te('Create your account') ?></h1>
        <p><?= te('Free for students.') ?></p>

        <?php if ($dbError): ?>
          <div class="alert alert-error"><?= icon('info') ?><span><?= te('Sign-up is unavailable because the database isn’t connected.') ?></span></div>
        <?php elseif ($errors): ?>
          <div class="alert alert-error" role="alert"><?= icon('info') ?><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <div class="social-auth"><?= google_button('Sign up with Google') ?></div>

        <div class="form-stack">
          <?= csrf_field() ?>
          <div class="form-grid">
            <div class="field"><label class="label" for="first"><?= te('First name') ?></label><input id="first" class="input" type="text" name="first_name" value="<?= e($old['first_name']) ?>" maxlength="50" autocomplete="given-name" required /></div>
            <div class="field"><label class="label" for="last"><?= te('Last name') ?></label><input id="last" class="input" type="text" name="last_name" value="<?= e($old['last_name']) ?>" maxlength="50" autocomplete="family-name" required /></div>
          </div>
          <div class="field"><label class="label" for="email"><?= te('Email') ?></label><input id="email" class="input" type="email" name="email" value="<?= e($old['email']) ?>" maxlength="190" autocomplete="email" placeholder="you@example.com" required /></div>
          <div class="field"><label class="label" for="password"><?= te('Password') ?></label><input id="password" class="input" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" placeholder="<?= te('At least 8 characters') ?>" required /></div>
          <div class="form-grid">
            <div class="field">
              <label class="label" for="region"><?= te('Where you live') ?></label>
              <select id="region" class="select" name="region_id">
                <option value=""><?= te('Choose region') ?></option>
                <?php foreach ($regions as $r): ?>
                  <option value="<?= (int) $r['id'] ?>"<?= $old['region_id'] === (string) $r['id'] ? ' selected' : '' ?>><?= te($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label class="label" for="year"><?= te('Starting uni in') ?></label>
              <select id="year" class="select" name="start_year">
                <option value=""><?= te('Not sure yet') ?></option>
                <?php foreach ($years as $y): ?>
                  <option value="<?= $y ?>"<?= $old['start_year'] === (string) $y ? ' selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <label class="check-row"><input type="checkbox" name="terms" value="1" required /><?= te('I agree to the Terms and Privacy Policy') ?></label>
          <button class="btn btn-primary btn-block btn-lg" type="submit"<?= $dbError ? ' disabled' : '' ?>><?= te('Create account') ?></button>
        </div>

        <p class="small muted" style="margin-top:24px;text-align:center"><?= te('Already have an account?') ?> <a href="login.php" class="text-link"><?= te('Log in') ?></a></p>
      </form>
    </div>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
