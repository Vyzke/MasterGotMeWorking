<?php
require_once __DIR__ . '/includes/auth.php';

$dbError = false;
$groups = []; // region slug => ['name' => ..., 'portals' => [...]]

try {
    $rows = db()->query('
        SELECT p.*, r.name AS region_name, r.slug AS region_slug
        FROM portals p JOIN regions r ON r.id = p.region_id
        ORDER BY r.sort_order, p.sort_order, p.name')->fetchAll();
    foreach ($rows as $p) {
        $groups[$p['region_slug']]['name'] = $p['region_name'];
        $groups[$p['region_slug']]['portals'][] = $p;
    }
} catch (PDOException $e) {
    $dbError = true;
}

$pageTitle = t('Application portals') . ' — uni wise';
require __DIR__ . '/includes/header.php';
?>
  <main>
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>"><a href="index.php"><?= te('Home') ?></a><span class="sep">/</span><span><?= te('Application portals') ?></span></nav>
        <h1><?= te('Application portals') ?></h1>
        <p class="lead"><?= te('Most countries use a central portal to apply. Here’s which one you’ll need, what it covers and when it usually opens.') ?></p>
        <?php if ($groups): ?>
          <div class="chips scroll" style="margin-top:28px">
            <?php foreach ($groups as $slug => $g): ?>
              <a href="#<?= e($slug) ?>" class="chip"><?= te($g['name']) ?></a>
            <?php endforeach; ?>
            <a href="#direct" class="chip"><?= te('Direct applications') ?></a>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="section-sm">
      <div class="container">
        <?php if ($dbError): ?>
          <?= db_error_notice() ?>
        <?php else: ?>
          <div class="notice" style="margin-bottom:40px"><?= icon('info') ?><span><?= te('Dates shown are typical windows and change each year and by programme. Always confirm on the official site.') ?></span></div>

          <?php foreach ($groups as $slug => $g): ?>
            <div class="region-title" id="<?= e($slug) ?>"><h2><?= te($g['name']) ?></h2><span class="line"></span></div>
            <div class="grid grid-3">
              <?php foreach ($g['portals'] as $p): ?>
                <article class="card portal-card">
                  <div class="portal-top"><span class="code-badge" style="--c:var(--purple-50)"><?= e($p['code']) ?></span><div><h3><?= e($p['name']) ?></h3><p class="muted"><?= e($p['scope']) ?></p></div></div>
                  <?php if ($p['description']): ?><p class="card-text small"><?= e($p['description']) ?></p><?php endif; ?>
                  <dl class="portal-meta"><div><dt><?= te('Opens') ?></dt><dd><?= e($p['opens'] ?: '—') ?></dd></div><div><dt><?= te('Typical deadlines') ?></dt><dd><?= e($p['typical_deadlines'] ?: '—') ?></dd></div></dl>
                  <div class="portal-actions">
                    <?php if ($p['website']): ?><a class="btn btn-ghost btn-sm" href="<?= e($p['website']) ?>" target="_blank" rel="noopener"><?= te('Official site') ?> <?= icon('external', 'i i-sm') ?></a><?php endif; ?>
                    <a class="btn btn-secondary btn-sm" href="universities.php"><?= te('Find universities') ?></a>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>

          <div class="region-title" id="direct"><h2><?= te('Direct applications') ?></h2><span class="line"></span></div>
          <div class="card direct-card">
            <span class="icon-box purple"><?= icon('building') ?></span>
            <div>
              <h3><?= te('Middle East, Latin America, Africa & more') ?></h3>
              <p class="muted small" style="margin-top:4px"><?= te('In many countries you apply straight to each university. Their profiles list the steps and dates.') ?></p>
            </div>
            <a href="universities.php" class="btn btn-primary btn-sm"><?= te('Browse universities') ?></a>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
