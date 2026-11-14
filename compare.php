<?php
require_once __DIR__ . '/includes/auth.php';

$maxSchools = 4;
$dbError = false;
$all = $schools = $topMajors = $facilities = [];

try {
    $pdo = db();
    $all = $pdo->query('SELECT id, name FROM universities ORDER BY name')->fetchAll();

    // Selected ids from ?s1=..&s2=..&s3=..&s4=..
    $ids = [];
    for ($i = 1; $i <= $maxSchools; $i++) {
        $v = (int) ($_GET["s$i"] ?? 0);
        if ($v && !in_array($v, $ids, true)) $ids[] = $v;
    }
    // Nothing picked yet → start with the top 3 ranked
    if (!$ids && !isset($_GET['s1'])) {
        $ids = array_map('intval', $pdo->query('SELECT id FROM universities ORDER BY qs_rank IS NULL, qs_rank LIMIT 3')->fetchAll(PDO::FETCH_COLUMN));
    }

    if ($ids) {
        $in = implode(',', $ids);
        $rows = $pdo->query("
            SELECT u.*, p.name AS portal_name,
                   (SELECT COUNT(*) FROM majors m WHERE m.university_id = u.id) AS major_count
            FROM universities u LEFT JOIN portals p ON p.id = u.portal_id
            WHERE u.id IN ($in)")->fetchAll();
        $byId = array_column($rows, null, 'id');
        foreach ($ids as $id) if (isset($byId[$id])) $schools[] = $byId[$id];   // keep picked order

        foreach ($pdo->query("SELECT university_id, name FROM majors WHERE is_top = 1 AND university_id IN ($in) ORDER BY top_rank") as $r) {
            $topMajors[$r['university_id']][] = $r['name'];
        }
        foreach ($pdo->query("SELECT university_id, name FROM facilities WHERE university_id IN ($in) ORDER BY name") as $r) {
            $facilities[$r['university_id']][] = $r['name'];
        }
    }
} catch (PDOException $e) {
    $dbError = true;
}

/** Mark the best value in a row: 'min' or 'max'. Returns [id => true]. */
function best_of(array $schools, string $field, string $mode): array
{
    $vals = [];
    foreach ($schools as $s) if ($s[$field] !== null) $vals[$s['id']] = (float) $s[$field];
    if (count($vals) < 2) return [];
    $target = $mode === 'min' ? min($vals) : max($vals);
    return array_fill_keys(array_keys($vals, $target), true);
}

$best = [
    'qs_rank'           => best_of($schools, 'qs_rank', 'min'),
    'major_count'       => best_of($schools, 'major_count', 'max'),
    'employment_rate'   => best_of($schools, 'employment_rate', 'max'),
    'median_salary_usd' => best_of($schools, 'median_salary_usd', 'max'),
    'tuition_intl_usd'  => best_of($schools, 'tuition_intl_usd', 'min'),
    'living_cost_usd'   => best_of($schools, 'living_cost_usd', 'min'),
];

/** Render one comparison row. $label is English (translated here). */
function compare_row(string $label, array $schools, callable $fmt, array $best = [], int $emptySlots = 0): void
{
    echo '<tr><td>' . te($label) . '</td>';
    foreach ($schools as $s) {
        $cls = isset($best[$s['id']]) ? ' class="best" data-best="' . te('Best') . '"' : '';
        echo "<td$cls>" . $fmt($s) . '</td>';
    }
    echo str_repeat('<td></td>', $emptySlots) . '</tr>';
}

$empty = $schools && count($schools) < $maxSchools ? 1 : 0;
$cols  = count($schools) + 1 + $empty;

$pageTitle = t('Compare schools') . ' — uni wise';
require __DIR__ . '/includes/header.php';
?>
  <main>
    <section class="page-hero">
      <div class="container">
        <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>"><a href="index.php"><?= te('Home') ?></a><span class="sep">/</span><span><?= te('Compare schools') ?></span></nav>
        <h1><?= te('Compare schools') ?></h1>
        <p class="lead"><?= te('Pick up to four universities and see majors, facilities, outcomes, language and cost side by side.') ?></p>

        <?php if (!$dbError && $all): ?>
          <form class="compare-pickers" method="get" action="compare.php">
            <?php for ($i = 1; $i <= 3; $i++): $sel = $schools[$i - 1]['id'] ?? 0; ?>
              <div class="field">
                <label class="label" for="s<?= $i ?>"><?= te('School {n}', ['n' => $i]) ?></label>
                <select id="s<?= $i ?>" class="select" name="s<?= $i ?>">
                  <option value=""><?= te('— Choose —') ?></option>
                  <?php foreach ($all as $a): ?>
                    <option value="<?= (int) $a['id'] ?>"<?= (int) $a['id'] === (int) $sel ? ' selected' : '' ?>><?= e($a['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endfor; ?>
            <?php if (isset($schools[3])): ?><input type="hidden" name="s4" value="<?= (int) $schools[3]['id'] ?>" /><?php endif; ?>
            <button class="btn btn-primary" type="submit"><?= te('Compare') ?></button>
          </form>
        <?php endif; ?>
      </div>
    </section>

    <section class="section-sm">
      <div class="container">
        <?php if ($dbError): ?>
          <?= db_error_notice() ?>
        <?php elseif (!$all): ?>
          <?= coming_soon('Nothing to compare yet', 'Once universities are added to the database you can compare up to four side by side.', 'columns') ?>
        <?php elseif (!$schools): ?>
          <div class="card" style="text-align:center"><h3><?= te('Pick at least one school to start comparing') ?></h3></div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="table compare-table">
              <thead>
                <tr>
                  <th scope="col"><span class="sr-only"><?= te('Criteria') ?></span></th>
                  <?php foreach ($schools as $s): ?>
                    <th scope="col"><a href="university.php?u=<?= urlencode($s['slug']) ?>" class="compare-head"><span class="crest" style="--c:<?= e($s['crest_color']) ?>"><?= e(crest_letter($s['name'])) ?></span><span><?= e($s['name']) ?><small><?= e(trim(($s['city'] ? $s['city'] . ', ' : '') . $s['country'])) ?></small></span></a></th>
                  <?php endforeach; ?>
                  <?php if ($empty): ?><th scope="col"><a href="universities.php" class="add-slot"><?= icon('plus') ?><?= te('Add school') ?></a></th><?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <tr class="group"><td colspan="<?= $cols ?>"><?= te('Overview') ?></td></tr>
                <?php
                compare_row('QS rank', $schools, fn($s) => $s['qs_rank'] ? '#' . (int) $s['qs_rank'] : '—', $best['qs_rank'], $empty);
                compare_row('Type', $schools, fn($s) => $s['type'] ? te($s['type']) : '—', [], $empty);
                compare_row('Application portal', $schools, fn($s) => $s['portal_name'] ? e($s['portal_name']) : te('Direct'), [], $empty);
                ?>
                <tr class="group"><td colspan="<?= $cols ?>"><?= te('Majors') ?></td></tr>
                <?php
                compare_row('Top majors', $schools, fn($s) => e(implode(', ', $topMajors[$s['id']] ?? ['—'])), [], $empty);
                compare_row('Majors listed', $schools, fn($s) => (int) $s['major_count'], $best['major_count'], $empty);
                ?>
                <tr class="group"><td colspan="<?= $cols ?>"><?= te('Facilities') ?></td></tr>
                <?php
                compare_row('Facilities', $schools, function ($s) use ($facilities) {
                    $list = $facilities[$s['id']] ?? [];
                    if (!$list) return '<span class="no">' . icon('minus', 'i i-sm') . te('None listed') . '</span>';
                    return implode('<br />', array_map(fn($f) => '<span class="yes">' . icon('check', 'i i-sm') . e($f) . '</span>', $list));
                }, [], $empty);
                ?>
                <tr class="group"><td colspan="<?= $cols ?>"><?= te('Graduate outcomes') ?></td></tr>
                <?php
                compare_row('Employed in 6 months', $schools, fn($s) => e(pct($s['employment_rate'])), $best['employment_rate'], $empty);
                compare_row('Median salary', $schools, fn($s) => e(money_k($s['median_salary_usd'])), $best['median_salary_usd'], $empty);
                ?>
                <tr class="group"><td colspan="<?= $cols ?>"><?= te('Language') ?></td></tr>
                <?php
                compare_row('Teaching language', $schools, fn($s) => e($s['teaching_language'] ?? '—'), [], $empty);
                compare_row('IELTS / TOEFL min.', $schools, fn($s) => e(($s['ielts_min'] ?? '—') . ' / ' . ($s['toefl_min'] ?? '—')), [], $empty);
                ?>
                <tr class="group"><td colspan="<?= $cols ?>"><?= te('Cost (international, per year)') ?></td></tr>
                <?php
                compare_row('Tuition', $schools, fn($s) => e(money($s['tuition_intl_usd'])), $best['tuition_intl_usd'], $empty);
                compare_row('Living costs', $schools, fn($s) => e(money($s['living_cost_usd'])), $best['living_cost_usd'], $empty);
                ?>
              </tbody>
            </table>
          </div>

          <div class="row-between" style="margin-top:24px">
            <p class="muted small"><?= te('Tip: scroll sideways on small screens to see every school.') ?></p>
            <div class="page-actions"><a href="compare.php?s1=" class="btn btn-ghost btn-sm"><?= te('Clear all') ?></a><button class="btn btn-secondary btn-sm" type="button" title="<?= te('Saving comes with accounts') ?>"><?= te('Save comparison') ?></button></div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
