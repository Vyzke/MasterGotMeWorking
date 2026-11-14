<?php
require_once __DIR__ . '/includes/avatar.php';

$user     = current_user();
$errors   = [];
$section  = (string) ($_POST['section'] ?? '');
$regions  = [];
$thisYear = (int) date('Y');
$years    = range($thisYear, $thisYear + 4);
$remindDays  = [1, 3, 7, 14, 30];
$budgets     = [1 => '$ · < $5k', 2 => '$$ · $5k–15k', 3 => '$$$ · $15k–35k', 4 => '$$$$ · $35k+'];
$studyLevels = ['bachelor' => 'Bachelor’s', 'master' => 'Master’s', 'phd' => 'PhD'];

try {
    $regions = db()->query('SELECT id, name FROM regions ORDER BY sort_order, name')->fetchAll();
} catch (PDOException $e) {
    $regions = [];
}
$regionIds = array_map('intval', array_column($regions, 'id'));

/** Save, show a message, and come back to the same section. */
function done(string $message, string $anchor): void
{
    flash($message);
    redirect('settings.php#' . $anchor);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = t('Your session expired. Please submit the form again.');
    }

    // ---- Language (everyone) ----
    elseif ($section === 'language') {
        $code = (string) ($_POST['language'] ?? '');
        if (!isset(LANGUAGES[$code])) {
            $errors[] = t('Choose a language from the list.');
        } else {
            set_lang($code);
            if ($user) {
                db()->prepare('UPDATE users SET language = ? WHERE id = ?')->execute([$code, $user['id']]);
            }
            flash(t('Language updated.'));
            $back = safe_next($_POST['back'] ?? null, '');
            redirect($back !== '' ? $back : 'settings.php#language');
        }
    }

    // ---- Profile ----
    elseif ($user && $section === 'profile') {
        $first  = trim((string) ($_POST['first_name'] ?? ''));
        $last   = trim((string) ($_POST['last_name'] ?? ''));
        $bio    = trim((string) ($_POST['bio'] ?? ''));
        $school = trim((string) ($_POST['school'] ?? ''));
        $regionId  = ($_POST['region_id'] ?? '') === '' ? null : (int) $_POST['region_id'];
        $startYear = ($_POST['start_year'] ?? '') === '' ? null : (int) $_POST['start_year'];

        if ($first === '' || mb_strlen($first) > 50) $errors[] = t('Enter your first name (up to 50 characters).');
        if (mb_strlen($last) > 50) $errors[] = t('Your last name can be at most 50 characters.');
        if (mb_strlen($bio) > 200) $errors[] = t('Your bio can be at most 200 characters.');
        if (mb_strlen($school) > 120) $errors[] = t('Your school name can be at most 120 characters.');
        if ($regionId !== null && !in_array($regionId, $regionIds, true)) $errors[] = t('Choose a region from the list.');
        if ($startYear !== null && !in_array($startYear, $years, true)) $errors[] = t('Choose a start year from the list.');

        // Optional new photo in the same form
        if (!$errors && !empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($err = avatar_save((int) $user['id'], $_FILES['avatar'])) $errors[] = t($err);
        }
        if (!$errors) {
            db()->prepare('UPDATE users SET first_name = ?, last_name = ?, bio = ?, school = ?, region_id = ?, start_year = ? WHERE id = ?')
                ->execute([$first, $last, $bio ?: null, $school ?: null, $regionId, $startYear, $user['id']]);
            done(t('Profile saved.'), 'profile');
        }
    }

    // ---- Password ----
    elseif ($user && $section === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($user['has_password']) {
            $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            if (!password_verify($current, (string) $stmt->fetchColumn())) {
                $errors[] = t('Your current password is incorrect.');
            }
        }
        if (strlen($new) < 8) $errors[] = t('Your password needs at least 8 characters.');
        elseif (strlen($new) > 72) $errors[] = t('Your password can be at most 72 characters.');
        if ($new !== $confirm) $errors[] = t('The new passwords don’t match.');

        if (!$errors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            done(t('Password updated.'), 'password');
        }
    }

    // ---- Notifications ----
    elseif ($user && $section === 'notifications') {
        $days = (int) ($_POST['notify_days'] ?? 7);
        if (!in_array($days, $remindDays, true)) $days = 7;
        db()->prepare('UPDATE users SET notify_deadlines = ?, notify_days = ?, notify_weekly = ?, notify_news = ? WHERE id = ?')
            ->execute([empty($_POST['notify_deadlines']) ? 0 : 1, $days, empty($_POST['notify_weekly']) ? 0 : 1, empty($_POST['notify_news']) ? 0 : 1, $user['id']]);
        done(t('Notification settings saved.'), 'notifications');
    }

    // ---- Study preferences ----
    elseif ($user && $section === 'study') {
        $picked = array_values(array_intersect(array_map('intval', (array) ($_POST['pref_regions'] ?? [])), $regionIds));
        $majors = trim((string) ($_POST['pref_majors'] ?? ''));
        $budget = (int) ($_POST['pref_budget'] ?? 0);
        $level  = (string) ($_POST['pref_study_level'] ?? '');
        if (mb_strlen($majors) > 200) $errors[] = t('Keep your majors to 200 characters or fewer.');
        if (!$errors) {
            db()->prepare('UPDATE users SET pref_regions = ?, pref_majors = ?, pref_budget = ?, pref_study_level = ? WHERE id = ?')
                ->execute([$picked ? implode(',', $picked) : null, $majors ?: null, isset($budgets[$budget]) ? $budget : null, isset($studyLevels[$level]) ? $level : null, $user['id']]);
            done(t('Study preferences saved.'), 'study');
        }
    }

    // ---- Delete account ----
    elseif ($user && $section === 'delete') {
        if (empty($_POST['confirm_delete'])) {
            $errors[] = t('Tick the box to confirm you want to delete your account.');
        }
        if ($user['has_password']) {
            $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            if (!password_verify((string) ($_POST['password'] ?? ''), (string) $stmt->fetchColumn())) {
                $errors[] = t('Your current password is incorrect.');
            }
        }
        if (!$errors) {
            avatar_remove((int) $user['id']);
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]);
            logout_user();
            redirect('index.php');
        }
    }
}

$flash      = flash();
$flashError = flash_error();
$lang       = current_lang();
$myRegions  = $user && $user['pref_regions'] ? array_map('intval', explode(',', $user['pref_regions'])) : [];

/** Error box shown inside the section that was submitted. */
function section_errors(string $name, string $submitted, array $errors): string
{
    if ($name !== $submitted || !$errors) return '';
    $items = implode('', array_map(fn($e) => '<li>' . e($e) . '</li>', $errors));
    return '<div class="alert alert-error" role="alert">' . icon('info') . '<ul>' . $items . '</ul></div>';
}

/** On/off switch row. */
function switch_row(string $name, bool $on, string $title, string $hint): string
{
    return '<label class="switch-row"><span><b>' . te($title) . '</b><small>' . te($hint) . '</small></span>'
        . '<input type="checkbox" name="' . e($name) . '" value="1"' . ($on ? ' checked' : '') . ' /><i class="switch" aria-hidden="true"></i></label>';
}

$pageTitle = t('Settings') . ' — uni wise';
require __DIR__ . '/includes/header.php';
?>
  <main>
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>"><a href="index.php"><?= te('Home') ?></a><span class="sep">/</span><?php if ($user): ?><a href="account.php"><?= te('My account') ?></a><span class="sep">/</span><?php endif; ?><span><?= te('Settings') ?></span></nav>
        <h1><?= te('Settings') ?></h1>
        <p class="lead"><?= te('Manage your profile, notifications, language and how the site looks.') ?></p>
      </div>
    </section>

    <section class="section-sm">
      <div class="container settings-layout">
        <nav class="settings-nav" aria-label="<?= te('Settings') ?>">
          <?php if ($user): ?>
            <a href="#profile"><?= icon('user') ?><?= te('Profile') ?></a>
            <a href="#password"><?= icon('lock') ?><?= te('Password & security') ?></a>
            <a href="#notifications"><?= icon('bell') ?><?= te('Notifications') ?></a>
            <a href="#study"><?= icon('compass') ?><?= te('Study preferences') ?></a>
          <?php endif; ?>
          <a href="#language"><?= icon('globe') ?><?= te('Language') ?></a>
          <a href="#appearance"><?= icon('sun') ?><?= te('Appearance') ?></a>
          <?php if ($user): ?>
            <a href="#data"><?= icon('download') ?><?= te('Your data') ?></a>
            <a href="#delete"><?= icon('trash') ?><?= te('Delete account') ?></a>
          <?php endif; ?>
        </nav>

        <div class="stack">
          <?php if ($flash): ?>
            <div class="alert alert-success" role="status"><?= icon('check') ?><span><?= e($flash) ?></span></div>
          <?php endif; ?>
          <?php if ($flashError): ?>
            <div class="alert alert-error" role="alert"><?= icon('info') ?><span><?= e($flashError) ?></span></div>
          <?php endif; ?>
          <?php if ($errors && $section === ''): ?>
            <div class="alert alert-error" role="alert"><?= icon('info') ?><span><?= e($errors[0]) ?></span></div>
          <?php endif; ?>

          <?php if ($user): ?>
            <!-- PROFILE -->
            <form class="card settings-card" id="profile" method="post" action="settings.php" enctype="multipart/form-data">
              <div class="settings-head"><h2><?= te('Profile') ?></h2><p><?= te('How you appear on uni wise.') ?></p></div>
              <?= section_errors('profile', $section, $errors) ?>
              <?= csrf_field() ?><input type="hidden" name="section" value="profile" />
              <div class="photo-setting">
                <?= user_avatar($user, 'xl') ?>
                <div>
                  <b><?= te('Profile picture') ?></b>
                  <p class="hint"><?= te('JPG, PNG, WebP or GIF, up to 2 MB.') ?></p>
                  <label class="btn btn-secondary btn-sm file-btn"><?= icon('camera', 'i i-sm') ?><?= te('Choose photo') ?><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" data-filename /></label>
                  <span class="hint file-name"></span>
                </div>
              </div>
              <div class="form-grid" style="margin-top:20px">
                <div class="field"><label class="label" for="first"><?= te('First name') ?></label><input id="first" class="input" name="first_name" maxlength="50" value="<?= e($user['first_name']) ?>" autocomplete="given-name" /></div>
                <div class="field"><label class="label" for="last"><?= te('Last name') ?></label><input id="last" class="input" name="last_name" maxlength="50" value="<?= e($user['last_name']) ?>" autocomplete="family-name" /></div>
              </div>
              <div class="field" style="margin-top:16px"><label class="label" for="bio"><?= te('Short bio') ?></label><textarea id="bio" class="textarea" name="bio" maxlength="200" rows="3" placeholder="<?= te('e.g. Aspiring engineer who loves robotics') ?>"><?= e($user['bio'] ?? '') ?></textarea><span class="hint"><?= te('Up to 200 characters.') ?></span></div>
              <div class="field" style="margin-top:16px"><label class="label" for="school"><?= te('Current school') ?></label><input id="school" class="input" name="school" maxlength="120" value="<?= e($user['school'] ?? '') ?>" /></div>
              <div class="form-grid" style="margin-top:16px">
                <div class="field">
                  <label class="label" for="region"><?= te('Where you live') ?></label>
                  <select id="region" class="select" name="region_id">
                    <option value=""><?= te('Choose region') ?></option>
                    <?php foreach ($regions as $r): ?>
                      <option value="<?= (int) $r['id'] ?>"<?= (int) $user['region_id'] === (int) $r['id'] ? ' selected' : '' ?>><?= te($r['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label class="label" for="year"><?= te('Starting uni in') ?></label>
                  <select id="year" class="select" name="start_year">
                    <option value=""><?= te('Not sure yet') ?></option>
                    <?php foreach ($years as $y): ?>
                      <option value="<?= $y ?>"<?= (int) $user['start_year'] === $y ? ' selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="settings-foot"><button class="btn btn-primary" type="submit"><?= te('Save profile') ?></button></div>
            </form>

            <!-- PASSWORD & SECURITY -->
            <form class="card settings-card" id="password" method="post" action="settings.php">
              <div class="settings-head">
                <h2><?= te('Password & security') ?></h2>
                <p><?= $user['has_password'] ? te('Change the password you use to log in.') : te('You sign in with Google. Add a password to also log in with your email.') ?></p>
              </div>
              <?= section_errors('password', $section, $errors) ?>
              <?= csrf_field() ?><input type="hidden" name="section" value="password" />
              <div class="form-stack">
                <?php if ($user['has_password']): ?>
                  <div class="field"><label class="label" for="cur"><?= te('Current password') ?></label><input id="cur" class="input" type="password" name="current_password" autocomplete="current-password" /></div>
                <?php endif; ?>
                <div class="form-grid">
                  <div class="field"><label class="label" for="new"><?= te('New password') ?></label><input id="new" class="input" type="password" name="new_password" minlength="8" maxlength="72" autocomplete="new-password" placeholder="<?= te('At least 8 characters') ?>" /></div>
                  <div class="field"><label class="label" for="conf"><?= te('Confirm new password') ?></label><input id="conf" class="input" type="password" name="confirm_password" maxlength="72" autocomplete="new-password" /></div>
                </div>
              </div>
              <div class="connected">
                <span class="row"><?= icon('link', 'i i-sm') ?><b><?= te('Connected accounts') ?></b></span>
                <div class="connected-row">
                  <span class="row">Google</span>
                  <?= $user['has_google'] ? '<span class="badge badge-mint">' . te('Connected') . '</span>' : '<span class="badge">' . te('Not connected') . '</span>' ?>
                </div>
              </div>
              <div class="settings-foot"><button class="btn btn-primary" type="submit"><?= $user['has_password'] ? te('Update password') : te('Add password') ?></button></div>
            </form>

            <!-- NOTIFICATIONS -->
            <form class="card settings-card" id="notifications" method="post" action="settings.php">
              <div class="settings-head"><h2><?= te('Notifications') ?></h2><p><?= te('Choose which emails you want. Emails aren’t sent yet — your choices are saved for when they are.') ?></p></div>
              <?= csrf_field() ?><input type="hidden" name="section" value="notifications" />
              <div class="switch-list">
                <?= switch_row('notify_deadlines', (bool) $user['notify_deadlines'], 'Deadline reminders', 'An email before each deadline in your tracker.') ?>
                <div class="field inline-field">
                  <label class="label" for="days"><?= te('Remind me') ?></label>
                  <select id="days" class="select" name="notify_days">
                    <?php foreach ($remindDays as $d): ?>
                      <option value="<?= $d ?>"<?= (int) $user['notify_days'] === $d ? ' selected' : '' ?>><?= te('{n} days before', ['n' => $d]) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?= switch_row('notify_weekly', (bool) $user['notify_weekly'], 'Weekly summary', 'Every Monday: what’s coming up this week.') ?>
                <?= switch_row('notify_news', (bool) $user['notify_news'], 'Tips & news', 'Occasional application tips and new features.') ?>
              </div>
              <div class="settings-foot"><button class="btn btn-primary" type="submit"><?= te('Save notifications') ?></button></div>
            </form>

            <!-- STUDY PREFERENCES -->
            <form class="card settings-card" id="study" method="post" action="settings.php">
              <div class="settings-head"><h2><?= te('Study preferences') ?></h2><p><?= te('Tell us what you’re looking for. We’ll use this to suggest universities.') ?></p></div>
              <?= section_errors('study', $section, $errors) ?>
              <?= csrf_field() ?><input type="hidden" name="section" value="study" />
              <span class="label"><?= te('Regions you’re interested in') ?></span>
              <div class="chip-checks">
                <?php foreach ($regions as $r): ?>
                  <label class="chip-check"><input type="checkbox" name="pref_regions[]" value="<?= (int) $r['id'] ?>"<?= in_array((int) $r['id'], $myRegions, true) ? ' checked' : '' ?> /><span><?= te($r['name']) ?></span></label>
                <?php endforeach; ?>
              </div>
              <div class="field" style="margin-top:18px"><label class="label" for="majors"><?= te('Majors you’re considering') ?></label><input id="majors" class="input" name="pref_majors" maxlength="200" value="<?= e($user['pref_majors'] ?? '') ?>" placeholder="<?= te('e.g. Computer Science, Economics') ?>" /></div>
              <div class="form-grid" style="margin-top:16px">
                <div class="field">
                  <label class="label" for="level"><?= te('Study level') ?></label>
                  <select id="level" class="select" name="pref_study_level">
                    <option value=""><?= te('Not sure yet') ?></option>
                    <?php foreach ($studyLevels as $k => $label): ?>
                      <option value="<?= $k ?>"<?= $user['pref_study_level'] === $k ? ' selected' : '' ?>><?= te($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label class="label" for="budget"><?= te('Tuition budget / year') ?></label>
                  <select id="budget" class="select" name="pref_budget">
                    <option value=""><?= te('Any budget') ?></option>
                    <?php foreach ($budgets as $k => $label): ?>
                      <option value="<?= $k ?>"<?= (int) $user['pref_budget'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="settings-foot"><button class="btn btn-primary" type="submit"><?= te('Save preferences') ?></button></div>
            </form>
          <?php endif; ?>

          <!-- LANGUAGE -->
          <form class="card settings-card" id="language" method="post" action="settings.php">
            <div class="settings-head"><h2><?= te('Language') ?></h2><p><?= te('The language used for menus, buttons and pages.') ?></p></div>
            <?= section_errors('language', $section, $errors) ?>
            <?= csrf_field() ?><input type="hidden" name="section" value="language" />
            <div class="lang-grid" role="radiogroup" aria-label="<?= te('Language') ?>">
              <?php foreach (LANGUAGES as $code => $name): ?>
                <label class="lang-option">
                  <input type="radio" name="language" value="<?= e($code) ?>"<?= $code === $lang ? ' checked' : '' ?> />
                  <span><b><?= e($name) ?></b><small><?= e(strtoupper($code)) ?></small></span>
                </label>
              <?php endforeach; ?>
            </div>
            <div class="settings-foot"><button class="btn btn-primary" type="submit"><?= te('Save language') ?></button></div>
          </form>

          <!-- APPEARANCE -->
          <div class="card settings-card" id="appearance">
            <div class="settings-head"><h2><?= te('Appearance') ?></h2><p><?= te('Light or dark. Saved on this device.') ?></p></div>
            <div class="theme-grid">
              <button type="button" class="theme-option" data-set-theme="light" aria-pressed="false">
                <span class="theme-preview light"><i></i><i></i><i></i></span><b><?= te('Light') ?></b>
              </button>
              <button type="button" class="theme-option" data-set-theme="dark" aria-pressed="false">
                <span class="theme-preview dark"><i></i><i></i><i></i></span><b><?= te('Dark') ?></b>
              </button>
            </div>
          </div>

          <?php if (!$user): ?>
            <div class="empty-state">
              <span class="icon-box lg purple"><?= icon('user') ?></span>
              <h2><?= te('Log in to manage your account') ?></h2>
              <p><?= te('Profile, password and account settings appear here once you’re logged in.') ?></p>
              <div class="page-actions"><a href="login.php?next=settings.php" class="btn btn-primary"><?= te('Log in') ?></a><a href="signup.php" class="btn btn-ghost"><?= te('Create free account') ?></a></div>
            </div>
          <?php else: ?>
            <!-- YOUR DATA -->
            <div class="card settings-card" id="data">
              <div class="settings-head"><h2><?= te('Your data') ?></h2><p><?= te('Download a copy of everything saved in your account.') ?></p></div>
              <a href="export.php" class="btn btn-secondary"><?= icon('download', 'i i-sm') ?><?= te('Download my data (JSON)') ?></a>
            </div>

            <!-- DELETE -->
            <form class="card settings-card danger" id="delete" method="post" action="settings.php">
              <div class="settings-head"><h2><?= te('Delete account') ?></h2><p><?= te('This permanently deletes your account and everything saved in it. This can’t be undone.') ?></p></div>
              <?= section_errors('delete', $section, $errors) ?>
              <?= csrf_field() ?><input type="hidden" name="section" value="delete" />
              <div class="form-stack">
                <?php if ($user['has_password']): ?>
                  <div class="field"><label class="label" for="delpw"><?= te('Current password') ?></label><input id="delpw" class="input" type="password" name="password" autocomplete="current-password" /></div>
                <?php endif; ?>
                <label class="check-row"><input type="checkbox" name="confirm_delete" value="1" /><?= te('I understand my account will be deleted permanently.') ?></label>
              </div>
              <div class="settings-foot"><button class="btn btn-danger" type="submit"><?= te('Delete my account') ?></button></div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
