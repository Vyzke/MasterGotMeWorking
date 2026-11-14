<?php
require_once __DIR__ . '/includes/auth.php';

$slug = trim($_GET['u'] ?? '');
$dbError = false;
$uni = null;
$majors = $facilities = $deadlines = [];

try {
    $pdo = db();
    if ($slug !== '') {
        $stmt = $pdo->prepare('
            SELECT u.*, r.name AS region_name,
                   p.name AS portal_name, p.code AS portal_code, p.scope AS portal_scope
            FROM universities u
            JOIN regions r ON r.id = u.region_id
            LEFT JOIN portals p ON p.id = u.portal_id
            WHERE u.slug = ?');
        $stmt->execute([$slug]);
        $uni = $stmt->fetch() ?: null;
    }
    if ($uni) {
        $id = (int) $uni['id'];
        $majors     = $pdo->query("SELECT * FROM majors WHERE university_id = $id ORDER BY is_top DESC, top_rank, faculty, name")->fetchAll();
        $facilities = $pdo->query("SELECT * FROM facilities WHERE university_id = $id ORDER BY name")->fetchAll();
        $deadlines  = $pdo->query("SELECT * FROM university_deadlines WHERE university_id = $id ORDER BY due_date")->fetchAll();
    }
} catch (PDOException $e) {
    $dbError = true;
}

if (!$dbError && !$uni && $slug !== '') {
    http_response_code(404);
}

$topMajors = array_values(array_filter($majors, fn($m) => $m['is_top']));
$byFaculty = [];
foreach ($majors as $m) {
    $byFaculty[$m['faculty'] ?: 'Other'][] = $m['name'];
}
ksort($byFaculty);

$pageTitle = ($uni ? $uni['name'] : t('University not found')) . ' — uni wise';
require __DIR__ . '/includes/header.php';
?>
  <main>
<?php if ($dbError || !$uni): ?>
    <section class="section">
      <div class="container" style="max-width:720px">
        <?php if ($dbError): ?>
          <?= db_error_notice() ?>
        <?php elseif ($slug === ''): ?>
          <?= coming_soon('University profiles are on their way', 'Profiles will appear here once universities are added to the database.') ?>
        <?php else: ?>
          <div class="card" style="text-align:center">
            <h1 style="font-size:2rem"><?= te('University not found') ?></h1>
            <p><?= te('We couldn’t find that university. It may have moved or the link is wrong.') ?></p>
            <a href="universities.php" class="btn btn-primary" style="margin-top:20px"><?= te('Browse universities') ?></a>
          </div>
        <?php endif; ?>
      </div>
    </section>
<?php else:
    $tuitionIntl = $uni['tuition_intl_usd'];
    $tuitionDom  = $uni['tuition_domestic_usd'];
    $living      = $uni['living_cost_usd'];
    $totalIntl   = ($tuitionIntl !== null || $living !== null) ? (int) $tuitionIntl + (int) $living : null;
    $totalDom    = ($tuitionDom !== null || $living !== null) ? (int) $tuitionDom + (int) $living : null;
?>
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>"><a href="index.php"><?= te('Home') ?></a><span class="sep">/</span><a href="universities.php"><?= te('Universities') ?></a><span class="sep">/</span><span><?= e($uni['name']) ?></span></nav>

        <div class="page-hero-row">
          <div class="profile-head">
            <span class="crest lg" style="--c:<?= e($uni['crest_color']) ?>"><?= e(crest_letter($uni['name'])) ?></span>
            <div>
              <h1><?= e($uni['name']) ?></h1>
              <div class="meta-line">
                <span><?= icon('pin', 'i i-sm') ?><?= e(trim(($uni['city'] ? $uni['city'] . ', ' : '') . $uni['country'])) ?></span>
                <?php if ($uni['qs_rank']): ?><span class="badge badge-yellow">QS #<?= (int) $uni['qs_rank'] ?></span><?php endif; ?>
                <?php if ($uni['type']): ?><span class="badge"><?= te($uni['type']) ?></span><?php endif; ?>
                <?php if ($uni['founded_year']): ?><span class="badge"><?= te('Founded {year}', ['year' => (int) $uni['founded_year']]) ?></span><?php endif; ?>
              </div>
            </div>
          </div>
          <div class="page-actions">
            <a href="compare.php?s1=<?= (int) $uni['id'] ?>" class="btn btn-secondary"><?= icon('plus') ?><?= te('Compare') ?></a>
            <button class="btn btn-primary" type="button" title="<?= te('Saving comes with accounts') ?>"><?= icon('bookmark') ?><?= te('Save to shortlist') ?></button>
          </div>
        </div>

        <div class="card quick-facts" style="padding:0">
          <div class="stat"><span class="stat-label"><?= te('QS rank') ?></span><span class="stat-value"><?= $uni['qs_rank'] ? '#' . (int) $uni['qs_rank'] : '—' ?></span></div>
          <div class="stat"><span class="stat-label"><?= te('Employed in 6 months') ?></span><span class="stat-value"><?= e(pct($uni['employment_rate'])) ?></span></div>
          <div class="stat"><span class="stat-label"><?= te('Intl. tuition / year') ?></span><span class="stat-value"><?= e(money_k($tuitionIntl)) ?></span></div>
          <div class="stat"><span class="stat-label"><?= te('Language') ?></span><span class="stat-value"><?= e($uni['teaching_language'] ?: '—') ?></span></div>
          <div class="stat"><span class="stat-label"><?= te('Students') ?></span><span class="stat-value"><?= e(short_number($uni['students'])) ?></span></div>
          <div class="stat"><span class="stat-label"><?= te('Majors listed') ?></span><span class="stat-value"><?= count($majors) ?></span></div>
        </div>
      </div>
    </section>

    <div class="subnav">
      <div class="container">
        <nav class="tabs" aria-label="<?= te('Profile sections') ?>">
          <a href="#overview" class="is-active"><?= te('Top majors') ?></a>
          <a href="#majors"><?= te('Majors') ?></a>
          <a href="#facilities"><?= te('Facilities') ?></a>
          <a href="#outcomes"><?= te('Graduate outcomes') ?></a>
          <a href="#language"><?= te('Language') ?></a>
          <a href="#cost"><?= te('Cost') ?></a>
        </nav>
      </div>
    </div>

    <section class="section-sm">
      <div class="container layout-aside">
        <div class="stack">
          <article class="card profile-section" id="overview">
            <h2><span class="icon-box sm yellow"><?= icon('star') ?></span><?= te('Top majors') ?></h2>
            <?php if ($topMajors): ?>
              <ol class="major-rank">
                <?php foreach ($topMajors as $i => $m): ?>
                  <li><span class="n"><?= sprintf('%02d', $i + 1) ?></span><div><b><?= e($m['name']) ?></b><small class="muted"><?= e($m['faculty'] ?? '') ?></small></div><?php if ($m['rank_label']): ?><span class="badge badge-purple"><?= e($m['rank_label']) ?></span><?php endif; ?></li>
                <?php endforeach; ?>
              </ol>
            <?php else: ?>
              <p class="muted"><?= te('No top majors listed yet.') ?></p>
            <?php endif; ?>
          </article>

          <article class="card profile-section" id="majors">
            <h2><span class="icon-box sm purple"><?= icon('book') ?></span><?= te('Majors offered') ?></h2>
            <?php if ($byFaculty): ?>
              <?php foreach ($byFaculty as $faculty => $names): ?>
                <div class="faculty-group"><h4><?= e($faculty === 'Other' ? t('Other') : $faculty) ?></h4><div class="tag-row"><?php foreach ($names as $n): ?><span class="badge"><?= e($n) ?></span><?php endforeach; ?></div></div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="muted"><?= te('No majors listed yet.') ?></p>
            <?php endif; ?>
          </article>

          <article class="card profile-section" id="facilities">
            <h2><span class="icon-box sm mint"><?= icon('flask') ?></span><?= te('Facilities') ?></h2>
            <?php if ($facilities): ?>
              <div class="grid grid-2" style="gap:20px">
                <?php foreach ($facilities as $f): ?>
                  <div class="facility"><span class="icon-box sm"><?= icon('check') ?></span><span><b><?= e($f['name']) ?></b><?php if ($f['description']): ?><small><?= e($f['description']) ?></small><?php endif; ?></span></div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="muted"><?= te('No facilities listed yet.') ?></p>
            <?php endif; ?>
          </article>

          <article class="card profile-section" id="outcomes">
            <h2><span class="icon-box sm blue"><?= icon('briefcase') ?></span><?= te('Graduate employment') ?></h2>
            <div class="big-number"><?= e(pct($uni['employment_rate'])) ?></div>
            <p class="muted" style="margin-top:8px"><?= te('of graduates are employed or in further study within 6 months.') ?></p>
            <?php if ($uni['median_salary_usd']): ?>
              <div class="tag-row" style="margin-top:18px"><span class="badge badge-blue"><?= te('Median salary {amount}', ['amount' => money_k($uni['median_salary_usd'])]) ?></span></div>
            <?php endif; ?>
          </article>

          <article class="card profile-section" id="language">
            <h2><span class="icon-box sm coral"><?= icon('chat') ?></span><?= te('Language') ?></h2>
            <div class="grid grid-3" style="gap:12px">
              <div class="card" style="box-shadow:none;background:var(--surface-2);border:0"><span class="stat-label"><?= te('Teaching language') ?></span><div class="stat-value" style="font-size:1.3rem;margin-top:6px"><?= e($uni['teaching_language'] ?: '—') ?></div></div>
              <div class="card" style="box-shadow:none;background:var(--surface-2);border:0"><span class="stat-label"><?= te('IELTS (min.)') ?></span><div class="stat-value" style="font-size:1.3rem;margin-top:6px"><?= $uni['ielts_min'] !== null ? e($uni['ielts_min']) : '—' ?></div></div>
              <div class="card" style="box-shadow:none;background:var(--surface-2);border:0"><span class="stat-label"><?= te('TOEFL iBT (min.)') ?></span><div class="stat-value" style="font-size:1.3rem;margin-top:6px"><?= $uni['toefl_min'] !== null ? (int) $uni['toefl_min'] : '—' ?></div></div>
            </div>
          </article>

          <article class="card profile-section" id="cost">
            <h2><span class="icon-box sm pink"><?= icon('coin') ?></span><?= te('Cost per year') ?></h2>
            <div class="table-wrap" style="box-shadow:none">
              <table class="table cost-table">
                <thead><tr><th><?= te('Item') ?></th><th><?= te('Domestic') ?></th><th><?= te('International') ?></th></tr></thead>
                <tbody>
                  <tr><td><?= te('Tuition') ?></td><td><?= e(money($tuitionDom)) ?></td><td><?= e(money($tuitionIntl)) ?></td></tr>
                  <tr><td><?= te('Living costs') ?></td><td><?= e(money($living)) ?></td><td><?= e(money($living)) ?></td></tr>
                </tbody>
                <tfoot><tr><td><?= te('Estimated total') ?></td><td><?= e(money($totalDom)) ?></td><td><?= e(money($totalIntl)) ?></td></tr></tfoot>
              </table>
            </div>
            <p class="muted small" style="margin-top:12px"><?= te('Figures in USD. Last updated {date}.', ['date' => date('j M Y', strtotime($uni['updated_at']))]) ?></p>
          </article>
        </div>

        <aside class="stack sticky">
          <div class="card">
            <div class="card-head"><h3><?= te('Key dates') ?></h3></div>
            <?php if ($deadlines): ?>
              <ul class="key-dates">
                <?php foreach ($deadlines as $d): $ts = strtotime($d['due_date']); ?>
                  <li><div class="date-tile"><small><?= date('M', $ts) ?></small><b><?= date('d', $ts) ?></b></div><div class="meta"><b><?= e($d['label']) ?></b><small><?= e($d['note'] ?? date('Y', $ts)) ?></small></div></li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="muted small"><?= te('No dates listed yet.') ?></p>
            <?php endif; ?>
            <a href="deadlines.php" class="btn btn-primary btn-block" style="margin-top:16px"><?= icon('calendar') ?><?= te('Track these deadlines') ?></a>
          </div>
          <div class="card">
            <div class="card-head"><h3><?= te('How to apply') ?></h3></div>
            <?php if ($uni['portal_name']): ?>
              <div class="row"><span class="code-badge" style="--c:var(--purple-50)"><?= e($uni['portal_code']) ?></span><div><b style="color:var(--ink)"><?= e($uni['portal_name']) ?></b><br /><small class="muted"><?= e($uni['portal_scope']) ?></small></div></div>
            <?php else: ?>
              <div class="row"><span class="code-badge"><?= icon('building') ?></span><div><b style="color:var(--ink)"><?= te('Apply directly') ?></b><br /><small class="muted"><?= te('Through the university’s own website') ?></small></div></div>
            <?php endif; ?>
            <div class="row" style="margin-top:16px;flex-wrap:wrap">
              <a href="portals.php" class="link-arrow"><?= te('Portal guide') ?> <?= icon('arrow') ?></a>
              <?php if ($uni['website']): ?><a href="<?= e($uni['website']) ?>" class="link-arrow" target="_blank" rel="noopener"><?= te('Official site') ?> <?= icon('external') ?></a><?php endif; ?>
            </div>
          </div>
        </aside>
      </div>
    </section>
<?php endif; ?>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
