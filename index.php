<?php
require_once __DIR__ . '/includes/auth.php';

// Regions + their portals for the "For all regions" section
$regions = [];
try {
    $rows = db()->query('
        SELECT r.id, r.name, r.slug, p.name AS portal
        FROM regions r LEFT JOIN portals p ON p.region_id = r.id
        ORDER BY r.sort_order, p.sort_order')->fetchAll();
    foreach ($rows as $row) {
        $regions[$row['id']]['name'] = $row['name'];
        $regions[$row['id']]['slug'] = $row['slug'];
        $regions[$row['id']]['portals'] ??= [];
        if ($row['portal']) $regions[$row['id']]['portals'][] = $row['portal'];
    }
} catch (PDOException $e) {
    $regions = [];
}

// English text here; translated when printed.
$features = [
    ['deadlines.php', 'calendar', 'coral', 'Deadline tracker', 'Every application, scholarship and test date in one timeline, with a clear view of what’s due next.'],
    ['portals.php', 'globe', 'blue', 'Application portals', 'What each central application system is for, which countries use it and when it usually opens.'],
    ['compare.php', 'columns', 'purple', 'School compare', 'Put universities side by side on majors, cost, language, facilities and graduate outcomes.'],
    ['milestones.php', 'flag', 'yellow', 'Skill milestones', 'Turn big goals like a test score or a finished personal statement into steps you can tick off.'],
    ['portfolio.php', 'folder', 'mint', 'Portfolio', 'Keep projects, awards, creative work and volunteering organised and ready to attach.'],
    ['recommendations.php', 'mail', 'pink', 'Recommendation letters', 'Request, track and thank your recommenders in one place.'],
];
$criteria = [
    ['star', 'yellow', 'Top majors', 'Where the school is strongest'],
    ['book', 'purple', 'Majors offered', 'The full list, by faculty'],
    ['flask', 'mint', 'Facilities', 'Labs, housing, sport & more'],
    ['briefcase', 'blue', 'Graduate employment', 'Outcomes after graduation'],
    ['chat', 'coral', 'Language', 'Teaching language & test scores'],
    ['coin', 'pink', 'Cost', 'Tuition and living costs'],
];

$pageTitle = 'uni wise — ' . t('Big dreams. Smart choices.');
require __DIR__ . '/includes/header.php';
?>
  <main>
    <section class="hero">
      <div class="container hero-grid">
        <div>
          <span class="pill"><span class="pill-tag"><?= te('For students') ?></span> <?= te('Every region, one place to plan') ?></span>
          <h1><?= te('Big dreams.') ?><br /><span class="accent"><?= te('Smart choices.') ?></span></h1>
          <p class="hero-lead"><?= te('Find the right university, keep every deadline in view, and feel confident about what comes next.') ?></p>
          <form class="hero-search" action="universities.php" role="search">
            <label class="input-icon">
              <span class="sr-only"><?= te('Search courses or universities') ?></span>
              <?= icon('search') ?>
              <input class="input" type="search" name="q" placeholder="<?= te('Search a university, city or major') ?>" />
            </label>
            <button class="btn btn-primary" type="submit"><?= te('Find universities') ?></button>
          </form>
        </div>

        <div class="card hero-panel">
          <div class="panel-head">
            <h3><?= te('Every university, the same six questions') ?></h3>
            <p><?= te('So comparing schools in different countries finally makes sense.') ?></p>
          </div>
          <div class="criteria-list">
            <?php foreach ($criteria as [$ic, $color, $title, $text]): ?>
              <div class="criteria-item"><span class="icon-box sm <?= $color ?>"><?= icon($ic) ?></span><span><b><?= te($title) ?></b><small><?= te($text) ?></small></span></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="section section-alt" id="features">
      <div class="container">
        <div class="section-head center">
          <span class="eyebrow"><?= te('Everything in one place') ?></span>
          <h2><?= te('Your whole application, organised') ?></h2>
          <p><?= te('From the first shortlist to the final submit button.') ?></p>
        </div>
        <div class="grid grid-3">
          <?php foreach ($features as [$href, $ic, $color, $title, $text]): ?>
            <a href="<?= $href ?>" class="card card-hover feature-card accent-<?= $color ?>">
              <span class="icon-box <?= $color ?>"><?= icon($ic) ?></span>
              <h3><?= te($title) ?></h3>
              <p><?= te($text) ?></p>
              <span class="link-arrow"><?= te('Open') ?> <?= icon('arrow') ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section" id="how-it-works">
      <div class="container">
        <div class="section-head center">
          <span class="eyebrow"><?= te('How it works') ?></span>
          <h2><?= te('Three steps from “no idea” to “submitted”') ?></h2>
        </div>
        <div class="grid grid-3">
          <div class="card step-card accent-purple"><span class="step-num">1</span><h3><?= te('Build your shortlist') ?></h3><p><?= te('Search universities by major, region, language and budget.') ?></p></div>
          <div class="card step-card accent-blue"><span class="step-num">2</span><h3><?= te('Plan every step') ?></h3><p><?= te('Line up each school’s portal, deadlines and requirements with clear milestones.') ?></p></div>
          <div class="card step-card accent-pink"><span class="step-num">3</span><h3><?= te('Apply with confidence') ?></h3><p><?= te('Track submissions, recommendation letters and your portfolio until every application is in.') ?></p></div>
        </div>
      </div>
    </section>

    <?php if ($regions): ?>
    <section class="section section-alt" id="regions">
      <div class="container">
        <div class="section-head">
          <span class="eyebrow"><?= te('For all regions') ?></span>
          <h2><?= te('Wherever you want to study') ?></h2>
          <p><?= te('Different countries, different rules. See which portal you’ll use in each region.') ?></p>
        </div>
        <div class="grid grid-4">
          <?php $accents = ['purple', 'blue', 'pink', 'mint', 'coral', 'yellow']; $ri = 0; ?>
          <?php foreach ($regions as $r): ?>
            <a href="portals.php#<?= e($r['portals'] ? $r['slug'] : 'direct') ?>" class="card card-hover region-card accent-<?= $accents[$ri++ % count($accents)] ?>">
              <h3><?= te($r['name']) ?></h3>
              <div class="portals">
                <?php if ($r['portals']): foreach ($r['portals'] as $p): ?><span class="badge"><?= e($p) ?></span><?php endforeach; else: ?><span class="badge"><?= te('Direct applications') ?></span><?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!$user): ?>
    <section class="section">
      <div class="container">
        <div class="cta-band">
          <div>
            <h2><?= te('Ready to start your shortlist?') ?></h2>
            <p><?= te('Create a free account to keep your universities, deadlines and documents in one place.') ?></p>
          </div>
          <div class="page-actions">
            <a href="signup.php" class="btn btn-light btn-lg"><?= te('Create free account') ?></a>
            <a href="universities.php" class="btn btn-outline-light btn-lg"><?= te('Browse first') ?></a>
          </div>
        </div>
      </div>
    </section>
    <?php endif; ?>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
