<?php
// Shared <head> + site header.
// Set before including:  $pageTitle (string, already translated)
// Optional:              $authNav = 'login' | 'signup'  → minimal header for the account pages
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/tools.php';

$pageTitle = $pageTitle ?? 'uni wise';
$authNav   = $authNav ?? null;
$current   = basename($_SERVER['SCRIPT_NAME']);
$user      = current_user();

function nav_attr(string $file): string
{
    global $current;
    return $current === $file ? ' aria-current="page"' : '';
}

// Light/dark switching lives on the Settings page (and the account page's quick settings).
$settingsLink = '<a class="icon-btn" href="settings.php" aria-label="' . te('Settings') . '" title="' . te('Settings') . '">' . icon('settings') . '</a>';
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= te('Research universities, compare schools, track deadlines and manage your applications — for students in every region.') ?>" />
  <link rel="icon" href="assets/img/logo.svg" type="image/svg+xml" />
  <link rel="apple-touch-icon" href="assets/img/logo.svg" />
  <!-- apply saved theme before paint (light is the default) -->
  <script>try { if (localStorage.getItem('uniwise-theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark'); } catch (e) {}</script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <script src="assets/js/main.js" defer></script>
</head>
<body>

  <header class="site-header">
    <div class="container nav">
      <a href="index.php" class="logo" aria-label="<?= te('uni wise home') ?>">
        <img class="logo-mark" src="assets/img/logo.svg" alt="" width="38" height="38" />
        <span>uni<span class="logo-wise">wise</span></span>
      </a>

<?php if ($authNav): ?>
      <div class="nav-actions">
        <?php if ($authNav === 'login'): ?>
          <a href="signup.php" class="btn btn-ghost btn-sm" style="display:inline-flex"><?= te('Create account') ?></a>
        <?php else: ?>
          <a href="login.php" class="btn btn-ghost btn-sm" style="display:inline-flex"><?= te('Log in') ?></a>
        <?php endif; ?>
      </div>
<?php else: ?>
      <nav class="nav-links" id="nav-menu" aria-label="<?= te('Main') ?>">
        <a href="universities.php"<?= nav_attr('universities.php') ?>><?= te('Universities') ?></a>
        <a href="compare.php"<?= nav_attr('compare.php') ?>><?= te('Compare') ?></a>
        <a href="portals.php"<?= nav_attr('portals.php') ?>><?= te('Portals') ?></a>
        <div class="nav-dropdown">
          <button class="nav-dropdown-toggle" type="button" aria-expanded="false"><?= te('My tools') ?> <svg class="i" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></button>
          <div class="nav-dropdown-menu wide">
            <?php foreach (TOOLS as $tl): ?>
              <a href="<?= e($tl['file']) ?>" class="menu-item"<?= nav_attr($tl['file']) ?>><span class="icon-box sm <?= e($tl['color']) ?>"><?= icon($tl['icon']) ?></span><span><b><?= te($tl['title']) ?></b><small><?= te($tl['desc']) ?></small></span></a>
            <?php endforeach; ?>
          </div>
        </div>
        <a href="settings.php" class="nav-mobile-only"<?= nav_attr('settings.php') ?>><?= te('Settings') ?></a>
        <div class="nav-mobile-actions">
          <?php if ($user): ?>
            <a href="account.php" class="btn btn-ghost"><?= te('My account') ?></a>
            <form method="post" action="logout.php"><?= csrf_field() ?><button class="btn btn-primary" type="submit"><?= te('Log out') ?></button></form>
          <?php else: ?>
            <a href="login.php" class="btn btn-ghost"><?= te('Log in') ?></a>
            <a href="signup.php" class="btn btn-primary"><?= te('Get started') ?></a>
          <?php endif; ?>
        </div>
      </nav>
      <div class="nav-actions">
        <?php if ($user): ?>
          <a href="account.php" class="nav-user" title="<?= te('My account') ?>"><?= user_avatar($user) ?><span class="nav-user-name"><?= e($user['first_name']) ?></span></a>
          <?= $settingsLink ?>
          <form method="post" action="logout.php"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit"><?= te('Log out') ?></button></form>
        <?php else: ?>
          <a href="login.php" class="nav-login"><?= te('Log in') ?></a>
          <a href="signup.php" class="btn btn-primary btn-sm"><?= te('Get started') ?></a>
          <?= $settingsLink ?>
        <?php endif; ?>
        <button class="nav-toggle" type="button" aria-controls="nav-menu" aria-expanded="false" aria-label="<?= te('Open menu') ?>"><span></span></button>
      </div>
<?php endif; ?>
    </div>
  </header>
