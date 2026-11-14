<?php
require_once __DIR__ . '/includes/auth.php';

// ---- Read filters from the URL ----
$q       = trim($_GET['q'] ?? '');
$region  = (int) ($_GET['region'] ?? 0);
$major   = trim($_GET['major'] ?? '');
$lang    = trim($_GET['lang'] ?? '');
$cost    = (int) ($_GET['cost'] ?? 0);
$sort    = $_GET['sort'] ?? 'rank';
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;

$languages = ['English', 'German', 'French', 'Dutch', 'Mandarin', 'Japanese', 'Spanish'];
$costTiers = [1 => [0, 4999], 2 => [5000, 14999], 3 => [15000, 34999], 4 => [35000, 10000000]];
$sorts = [
    'rank'       => 'u.qs_rank IS NULL, u.qs_rank',
    'employment' => 'u.employment_rate IS NULL, u.employment_rate DESC',
    'tuition'    => 'u.tuition_intl_usd IS NULL, u.tuition_intl_usd',
    'name'       => 'u.name',
];
if (!isset($sorts[$sort])) $sort = 'rank';

$dbError = false;
$regions = $majorNames = $unis = $topMajors = [];
$total = 0;
$pages = 1;

try {
    $pdo = db();
    $regions    = $pdo->query('SELECT id, name FROM regions ORDER BY sort_order, name')->fetchAll();
    $majorNames = $pdo->query('SELECT DISTINCT name FROM majors ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);

    // ---- Build WHERE clause (prepared statement params only) ----
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(u.name LIKE :q1 OR u.city LIKE :q2 OR u.country LIKE :q3
                    OR EXISTS (SELECT 1 FROM majors m WHERE m.university_id = u.id AND m.name LIKE :q4))';
        foreach (['q1', 'q2', 'q3', 'q4'] as $k) $params[$k] = "%$q%";
    }
    if ($region) {
        $where[] = 'u.region_id = :region';
        $params['region'] = $region;
    }
    if ($major !== '') {
        $where[] = 'EXISTS (SELECT 1 FROM majors m2 WHERE m2.university_id = u.id AND m2.name = :major)';
        $params['major'] = $major;
    }
    if ($lang !== '' && in_array($lang, $languages, true)) {
        $where[] = 'u.teaching_language LIKE :lang';
        $params['lang'] = "%$lang%";
    }
    if (isset($costTiers[$cost])) {
        $where[] = 'u.tuition_intl_usd BETWEEN :cmin AND :cmax';
        [$params['cmin'], $params['cmax']] = $costTiers[$cost];
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM universities u $whereSql");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $pages  = max(1, (int) ceil($total / $perPage));
    $page   = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("
        SELECT u.id, u.slug, u.name, u.city, u.country, u.qs_rank, u.teaching_language,
               u.tuition_intl_usd, u.employment_rate, u.crest_color,
               (SELECT COUNT(*) FROM majors m WHERE m.university_id = u.id) AS major_count
        FROM universities u
        $whereSql
        ORDER BY {$sorts[$sort]}
        LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $unis = $stmt->fetchAll();

    // Top 2 majors for the cards on this page
    if ($unis) {
        $ids = implode(',', array_map('intval', array_column($unis, 'id')));
        foreach ($pdo->query("SELECT university_id, name FROM majors
                              WHERE is_top = 1 AND university_id IN ($ids)
                              ORDER BY top_rank") as $row) {
            if (count($topMajors[$row['university_id']] ?? []) < 2) {
                $topMajors[$row['university_id']][] = $row['name'];
            }
        }
    }
} catch (PDOException $e) {
    $dbError = true;
}

$from = $total ? ($page - 1) * $perPage + 1 : 0;
$to   = min($total, $page * $perPage);
$hasFilters = $q !== '' || $region || $major !== '' || $lang !== '' || $cost;

$pageTitle = t('Universities') . ' — uni wise';
require __DIR__ . '/includes/header.php';
?>
  <main>
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>"><a href="index.php"><?= te('Home') ?></a><span class="sep">/</span><span><?= te('Universities') ?></span></nav>
        <h1><?= te('Find your university') ?></h1>
        <p class="lead"><?= te('Search the world’s top universities by major, region, language and budget.') ?></p>

        <form class="card filter-panel" id="filters" action="universities.php" method="get" role="search">
          <div class="field">
            <label class="label" for="q"><?= te('Search') ?></label>
            <span class="input-icon"><?= icon('search') ?><input id="q" class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= te('University, city or major') ?>" /></span>
          </div>
          <div class="field">
            <label class="label" for="region"><?= te('Region') ?></label>
            <select id="region" class="select" name="region">
              <option value=""><?= te('All regions') ?></option>
              <?php foreach ($regions as $r): ?>
                <option value="<?= (int) $r['id'] ?>"<?= $region === (int) $r['id'] ? ' selected' : '' ?>><?= te($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label class="label" for="major"><?= te('Major') ?></label>
            <select id="major" class="select" name="major">
              <option value=""><?= te('Any major') ?></option>
              <?php foreach ($majorNames as $m): ?>
                <option value="<?= e($m) ?>"<?= $major === $m ? ' selected' : '' ?>><?= e($m) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label class="label" for="lang"><?= te('Language') ?></label>
            <select id="lang" class="select" name="lang">
              <option value=""><?= te('Any language') ?></option>
              <?php foreach ($languages as $l): ?>
                <option value="<?= e($l) ?>"<?= $lang === $l ? ' selected' : '' ?>><?= te($l) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label class="label" for="cost"><?= te('Tuition / year') ?></label>
            <select id="cost" class="select" name="cost">
              <option value=""><?= te('Any budget') ?></option>
              <?php foreach ([1 => '$ · < $5k', 2 => '$$ · $5k–15k', 3 => '$$$ · $15k–35k', 4 => '$$$$ · $35k+'] as $k => $label): ?>
                <option value="<?= $k ?>"<?= $cost === $k ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary" type="submit"><?= te('Search') ?></button>
        </form>
      </div>
    </section>

    <section class="section-sm">
      <div class="container">
        <?php if ($dbError): ?>
          <?= db_error_notice() ?>
        <?php elseif (!$unis && !$hasFilters): ?>
          <?= coming_soon('University data is on its way', 'Universities will appear here as soon as they’re added to the database.') ?>
        <?php else: ?>
          <div class="results-bar">
            <p class="muted"><?= te('{count} universities', ['count' => $total]) ?><?= $total ? ' · ' . te('showing {from}–{to}', ['from' => $from, 'to' => $to]) : '' ?></p>
            <label class="row small"><span class="muted"><?= te('Sort by') ?></span>
              <select class="select" name="sort" form="filters" aria-label="<?= te('Sort by') ?>" onchange="this.form.submit()">
                <?php foreach (['rank' => 'QS rank', 'employment' => 'Employment rate', 'tuition' => 'Lowest tuition', 'name' => 'Name A–Z'] as $k => $label): ?>
                  <option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>><?= te($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>

          <?php if (!$unis): ?>
            <div class="card" style="text-align:center">
              <h3><?= te('No universities match those filters') ?></h3>
              <p><?= te('Try removing a filter or searching for something broader.') ?></p>
              <a href="universities.php" class="btn btn-ghost btn-sm" style="margin-top:16px"><?= te('Clear filters') ?></a>
            </div>
          <?php else: ?>
            <div class="grid grid-3">
              <?php foreach ($unis as $u): ?>
                <article class="card card-hover uni-card">
                  <div class="uni-top">
                    <span class="crest" style="--c:<?= e($u['crest_color']) ?>"><?= e(crest_letter($u['name'])) ?></span>
                    <div>
                      <h3><a href="university.php?u=<?= urlencode($u['slug']) ?>"><?= e($u['name']) ?></a></h3>
                      <p class="uni-loc"><?= icon('pin') ?><?= e(trim(($u['city'] ? $u['city'] . ', ' : '') . $u['country'])) ?></p>
                    </div>
                    <?php if ($u['qs_rank']): ?><span class="rank">#<?= (int) $u['qs_rank'] ?></span><?php endif; ?>
                  </div>
                  <div class="tag-row">
                    <?php foreach ($topMajors[$u['id']] ?? [] as $m): ?><span class="badge badge-purple"><?= e($m) ?></span><?php endforeach; ?>
                    <?php if ($u['teaching_language']): ?><span class="badge"><?= e($u['teaching_language']) ?></span><?php endif; ?>
                  </div>
                  <dl class="facts">
                    <div><dt><?= te('Tuition') ?></dt><dd><?= e(tuition_tier($u['tuition_intl_usd'])) ?></dd></div>
                    <div><dt><?= te('Employed') ?></dt><dd><?= e(pct($u['employment_rate'])) ?></dd></div>
                    <div><dt><?= te('Majors') ?></dt><dd><?= (int) $u['major_count'] ?></dd></div>
                  </dl>
                  <div class="uni-actions">
                    <a href="university.php?u=<?= urlencode($u['slug']) ?>" class="btn btn-ghost btn-sm"><?= te('View profile') ?></a>
                    <a href="compare.php?s1=<?= (int) $u['id'] ?>" class="btn-icon" aria-label="<?= te('Compare') ?>" title="<?= te('Compare') ?>"><?= icon('plus') ?></a>
                    <button class="btn-icon" type="button" aria-label="<?= te('Save') ?>" title="<?= te('Saving comes with accounts') ?>"><?= icon('bookmark') ?></button>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
              <nav class="pagination" aria-label="<?= te('Pagination') ?>">
                <?php if ($page > 1): ?><a href="<?= e(query_with(['page' => $page - 1])) ?>" aria-label="<?= te('Previous page') ?>">‹</a><?php endif; ?>
                <?php for ($p = 1; $p <= $pages; $p++): ?>
                  <?php if ($p === 1 || $p === $pages || abs($p - $page) <= 2): ?>
                    <a href="<?= e(query_with(['page' => $p])) ?>"<?= $p === $page ? ' class="is-active" aria-current="page"' : '' ?>><?= $p ?></a>
                  <?php elseif (abs($p - $page) === 3): ?>
                    <a aria-hidden="true">…</a>
                  <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $pages): ?><a href="<?= e(query_with(['page' => $page + 1])) ?>" aria-label="<?= te('Next page') ?>">›</a><?php endif; ?>
              </nav>
            <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
