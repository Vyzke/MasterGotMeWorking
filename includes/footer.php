<?php
// Shared site footer. Set $hideFooter = true before including to skip it (account pages).
if (empty($hideFooter)):
    $back = basename($_SERVER['SCRIPT_NAME']) . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']);
?>
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="index.php" class="logo"><img class="logo-mark" src="assets/img/logo.svg" alt="" width="38" height="38" /><span>uni<span class="logo-wise">wise</span></span></a>
          <p><?= te('Friendly, honest guidance for students choosing and applying to university — in every region.') ?></p>
        </div>
        <div class="footer-col"><h4><?= te('Explore') ?></h4><a href="universities.php"><?= te('Universities') ?></a><a href="compare.php"><?= te('Compare schools') ?></a><a href="portals.php"><?= te('Application portals') ?></a><a href="index.php#regions"><?= te('Regions') ?></a></div>
        <div class="footer-col"><h4><?= te('My tools') ?></h4><?php foreach (array_slice(TOOLS, 0, 5) as $tl): ?><a href="<?= e($tl['file']) ?>"><?= te($tl['title']) ?></a><?php endforeach; ?><a href="account.php#tools"><?= te('All tools') ?> →</a></div>
        <div class="footer-col"><h4><?= te('Account') ?></h4><?php if (current_user()): ?><a href="account.php"><?= te('My account') ?></a><?php else: ?><a href="signup.php"><?= te('Create account') ?></a><a href="login.php"><?= te('Log in') ?></a><?php endif; ?><a href="settings.php"><?= te('Settings') ?></a></div>
      </div>
      <div class="footer-bottom">
        <span>© <?= date('Y') ?> uni wise. <?= te('All rights reserved.') ?></span>
        <form class="lang-picker" action="set-language.php" method="get">
          <?= icon('globe', 'i i-sm') ?>
          <input type="hidden" name="back" value="<?= e($back) ?>" />
          <select class="select" name="lang" aria-label="<?= te('Language') ?>" onchange="this.form.submit()">
            <?php foreach (LANGUAGES as $code => $name): ?>
              <option value="<?= e($code) ?>"<?= $code === current_lang() ? ' selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
          </select>
          <noscript><button class="btn btn-ghost btn-sm" type="submit"><?= te('Go') ?></button></noscript>
        </form>
      </div>
    </div>
  </footer>
<?php endif; ?>
</body>
</html>
