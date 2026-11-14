<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/tools.php';
$user       = require_login();
$flash      = flash();
$flashError = flash_error();

$studyLevels = ['bachelor' => 'Bachelor’s', 'master' => 'Master’s', 'phd' => 'PhD'];

$pageTitle = t('My account') . ' — uni wise';
require __DIR__ . '/includes/header.php';
?>
  <main>
    <section class="section-sm">
      <div class="container stack">
        <?php if ($flash): ?>
          <div class="alert alert-success" role="status"><?= icon('check') ?><span><?= e($flash) ?></span></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
          <div class="alert alert-error" role="alert"><?= icon('info') ?><span><?= e($flashError) ?></span></div>
        <?php endif; ?>

        <div class="account-grid">
          <!-- PROFILE (first) -->
          <article class="card profile-card">
            <div class="profile-cover" aria-hidden="true"></div>
            <div class="profile-body">
              <div class="profile-photo">
                <?= user_avatar($user, 'xl') ?>
                <form class="photo-form" method="post" action="avatar.php" enctype="multipart/form-data">
                  <?= csrf_field() ?>
                  <input type="hidden" name="back" value="account.php" />
                  <label class="photo-btn" title="<?= te('Change photo') ?>">
                    <?= icon('camera') ?><span class="sr-only"><?= te('Change photo') ?></span>
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" data-autosubmit />
                  </label>
                  <noscript><button class="btn btn-ghost btn-sm" type="submit"><?= te('Upload') ?></button></noscript>
                </form>
              </div>

              <div class="profile-main">
                <div class="row-between">
                  <div>
                    <h1><?= e(trim($user['first_name'] . ' ' . $user['last_name'])) ?></h1>
                    <p class="muted"><?= e($user['email']) ?></p>
                  </div>
                  <a href="settings.php#profile" class="btn btn-secondary btn-sm"><?= icon('pen', 'i i-sm') ?><?= te('Edit profile') ?></a>
                </div>
                <?php if ($user['bio']): ?><p class="profile-bio"><?= e($user['bio']) ?></p><?php endif; ?>
                <div class="tag-row" style="margin-top:14px">
                  <?php if ($user['school']): ?><span class="badge badge-purple"><?= icon('building', 'i i-sm') ?><?= e($user['school']) ?></span><?php endif; ?>
                  <?php if ($user['region_name']): ?><span class="badge"><?= icon('pin', 'i i-sm') ?><?= te($user['region_name']) ?></span><?php endif; ?>
                  <?php if ($user['start_year']): ?><span class="badge"><?= icon('calendar', 'i i-sm') ?><?= te('Starting uni in') ?> <?= (int) $user['start_year'] ?></span><?php endif; ?>
                  <?php if ($user['pref_study_level']): ?><span class="badge"><?= icon('book', 'i i-sm') ?><?= te($studyLevels[$user['pref_study_level']]) ?></span><?php endif; ?>
                  <span class="badge"><?= te('Member since') ?> <?= e(date('M Y', strtotime($user['created_at']))) ?></span>
                </div>
                <?php if (!empty($user['avatar_path'])): ?>
                  <form method="post" action="avatar.php" style="margin-top:14px">
                    <?= csrf_field() ?><input type="hidden" name="back" value="account.php" /><input type="hidden" name="action" value="remove" />
                    <button class="text-link small" type="submit"><?= te('Remove photo') ?></button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </article>

          <!-- QUICK SETTINGS (right of the profile) -->
          <aside class="card quick-settings">
            <div class="card-head"><h3><?= icon('settings', 'i i-sm') ?> <?= te('Settings') ?></h3><a href="settings.php" class="link-arrow small"><?= te('All settings') ?> <?= icon('arrow') ?></a></div>

            <form method="post" action="settings.php" class="qs-row">
              <?= csrf_field() ?><input type="hidden" name="section" value="language" /><input type="hidden" name="back" value="account.php" />
              <label class="label" for="qs-lang"><?= te('Language') ?></label>
              <select id="qs-lang" class="select" name="language" onchange="this.form.submit()">
                <?php foreach (LANGUAGES as $code => $name): ?>
                  <option value="<?= e($code) ?>"<?= $code === current_lang() ? ' selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
              </select>
            </form>

            <div class="qs-row">
              <span class="label"><?= te('Appearance') ?></span>
              <div class="segmented" role="group" aria-label="<?= te('Appearance') ?>">
                <button type="button" data-set-theme="light" aria-pressed="false"><?= icon('sun', 'i i-sm') ?><?= te('Light') ?></button>
                <button type="button" data-set-theme="dark" aria-pressed="false"><svg class="i i-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/></svg><?= te('Dark') ?></button>
              </div>
            </div>

            <nav class="qs-links">
              <a href="settings.php#notifications"><?= icon('bell') ?><span><?= te('Notifications') ?><small><?= $user['notify_deadlines'] ? te('Deadline reminders on') : te('Deadline reminders off') ?></small></span></a>
              <a href="settings.php#study"><?= icon('compass') ?><span><?= te('Study preferences') ?><small><?= te('Regions, majors and budget') ?></small></span></a>
              <a href="settings.php#password"><?= icon('lock') ?><span><?= te('Password & security') ?><small><?= $user['has_google'] ? te('Google connected') : te('Email and password') ?></small></span></a>
              <a href="settings.php#data"><?= icon('download') ?><span><?= te('Your data') ?><small><?= te('Download a copy') ?></small></span></a>
            </nav>

            <form method="post" action="logout.php"><?= csrf_field() ?><button class="btn btn-ghost btn-block" type="submit"><?= te('Log out') ?></button></form>
          </aside>
        </div>

        <!-- TOOLS (below the profile) -->
        <div id="tools" class="section-head" style="margin:24px 0 0">
          <span class="eyebrow"><?= te('My tools') ?></span>
          <h2 style="font-size:clamp(1.5rem,1.2rem + 1.2vw,2rem)"><?= te('Everything for your applications') ?></h2>
        </div>
        <div class="grid tools-grid">
          <?php foreach (TOOLS as $tl): ?>
            <a href="<?= e($tl['file']) ?>" class="card card-hover feature-card tool-card accent-<?= e($tl['color']) ?>">
              <span class="icon-box <?= e($tl['color']) ?>"><?= icon($tl['icon']) ?></span>
              <h3><?= te($tl['title']) ?></h3>
              <p><?= te($tl['desc']) ?></p>
              <span class="badge badge-purple"><?= te('Coming soon') ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
