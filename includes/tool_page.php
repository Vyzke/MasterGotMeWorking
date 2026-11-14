<?php
// Shared layout for the personal tools (deadlines, milestones, portfolio, letters).
// Expects $tool = ['file','title','lead','icon','color','login_title','empty_title','empty_text'] — English text, translated here.
require_once __DIR__ . '/auth.php';
$user = current_user();

$pageTitle = t($tool['title']) . ' — uni wise';
require __DIR__ . '/header.php';
?>
  <main>
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>"><a href="index.php"><?= te('Home') ?></a><span class="sep">/</span><span><?= te('My tools') ?></span><span class="sep">/</span><span><?= te($tool['title']) ?></span></nav>
        <h1><?= te($tool['title']) ?></h1>
        <p class="lead"><?= te($tool['lead']) ?></p>
      </div>
    </section>

    <section class="section-sm">
      <div class="container">
        <?php if (!$user): ?>
          <div class="empty-state">
            <span class="icon-box lg <?= e($tool['color']) ?>"><?= icon('lock') ?></span>
            <h2><?= te($tool['login_title']) ?></h2>
            <p><?= te('This tool is private to your account.') ?></p>
            <div class="page-actions">
              <a href="login.php?next=<?= urlencode($tool['file']) ?>" class="btn btn-primary"><?= te('Log in') ?></a>
              <a href="signup.php" class="btn btn-ghost"><?= te('Create free account') ?></a>
            </div>
          </div>
        <?php else: ?>
          <!-- TODO: list this user's items here once the table for this tool exists -->
          <div class="empty-state">
            <span class="icon-box lg <?= e($tool['color']) ?>"><?= icon($tool['icon']) ?></span>
            <h2><?= te($tool['empty_title']) ?></h2>
            <p><?= te($tool['empty_text']) ?></p>
            <span class="badge badge-purple"><?= te('Coming soon') ?></span>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/footer.php'; ?>
