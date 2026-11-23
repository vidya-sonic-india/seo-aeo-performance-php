<?php
/**
 * SEO · AEO · Performance Report — Overview page (run a check, see the three scores, top fixes).
 *
 * Pages, in sequence:
 *   index.php        Overview & new check
 *   seo.php          1 · SEO Report
 *   aeo.php          2 · AEO Report
 *   performance.php  3 · Performance Report
 *   report.php       Full report (1 → 3), printable
 *
 * Setup: PHP 8.0+ with curl. Put your VebAPI key in config.php ('demo' previews sample data).
 * Make sure ./cache is writable. Local run:  php -S localhost:8000
 */
define('APP', 1);
require __DIR__ . '/app/core.php';
require __DIR__ . '/app/view.php';
$PAGE = 'overview'; $PAGE_TITLE = 'Overview';
require __DIR__ . '/app/header.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
  <div>
    <h1 class="fs-3 mb-1">SEO · AEO · Performance Report</h1>
    <p class="mb-0">On-page, authority, AI-readiness and speed — powered by VebAPI</p>
  </div>
  <?php if ($DEMO): ?><span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-3 py-2"><i class="ti ti-flask me-1"></i>Demo mode — sample data, no credits used</span><?php endif; ?>
</div>

<!-- CHECK FORM -->
<div class="card mb-3 check-card"><div class="card-body p-4">
  <form method="post" action="index.php" class="row g-3 align-items-end">
    <div class="col-lg-4"><label for="f-url" class="form-label small">Website URL or domain</label><input id="f-url" type="text" class="form-control" name="url" placeholder="https://example.com" value="<?= h($input) ?>" required></div>
    <div class="col-md-4 col-lg-3"><label for="f-brand" class="form-label small">Brand terms (comma-separated)</label><input id="f-brand" type="text" class="form-control" name="brand" placeholder="e.g. sonic lamb, soniclamb" value="<?= h($brandI) ?>"></div>
    <div class="col-md-5 col-lg-3"><label for="f-kw" class="form-label small">Seed keyword (related keywords)</label><input id="f-kw" type="text" class="form-control" name="kw" placeholder="e.g. wireless headphones" value="<?= h($seedI) ?>"></div>
    <div class="col-md-3 col-lg-2"><label for="f-cc" class="form-label small">Country</label><input id="f-cc" type="text" class="form-control" name="cc" maxlength="2" value="<?= h($ccI) ?>"></div>
    <div class="col-12 d-flex align-items-center flex-wrap gap-3">
      <button type="submit" class="btn btn-primary"><i class="ti ti-search me-1"></i>Run SEO · AEO · Performance check</button>
      <div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="fresh" value="1" id="f-fresh"><label class="form-check-label small" for="f-fresh">Bypass cache (uses credits)</label></div>
    </div>
  </form>
</div></div>

<?php foreach ($errors as $e): ?><div class="alert alert-danger d-flex gap-2 align-items-center" role="alert"><i class="ti ti-alert-circle fs-5"></i><?= h($e) ?></div><?php endforeach; ?>

<?php if (!$R): ?>
  <div class="card"><div class="card-body empty-hero">
    <span class="icon-shape icon-xl bg-primary bg-opacity-10 text-primary rounded-circle mb-3"><i class="ti ti-world-search fs-1"></i></span>
    <h2 class="h4 mb-2">Enter a website to generate the three reports</h2>
    <p class="text-secondary mb-0">1 · SEO Report → 2 · AEO Report → 3 · Performance Report<?php if ($credits !== null && $credits !== 'demo'): ?> · VebAPI credits remaining: <b><?= n($credits) ?></b><?php endif; ?></p>
  </div></div>
<?php else: ?>

  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mt-6 mb-3">
    <div><h2 class="fs-4 mb-1">Report for <?= h($T['domain']) ?></h2>
      <p class="mb-0 text-secondary small"><?= h(date('d M Y H:i')) ?>
      <?php $c = array_filter($R, fn($x) => $x['cached'] ?? false); if ($c): ?> · <?= count($c) ?>/<?= count($R) ?> results from cache<?php endif; ?>
      · <span class="text-primary"><?= count(array_filter($ACT, fn($a) => $a['p'] === 'High')) ?> high-priority fixes</span></p></div>
    <a class="btn btn-outline-secondary btn-sm" href="<?= h(page_url('report.php')) ?>"><i class="ti ti-file-text me-1"></i>Full report</a>
  </div>

  <!-- 3 SCORE CARDS, IN REPORT ORDER — each opens its own page -->
  <div class="row g-3 mb-4">
    <?php $cards = [
      ['seo.php', '1 · SEO Report', $seoScore, 'ti-search', 'text-primary', 'On-page grade ' . h(g($A, 'summary.grade', grade_of($seoScore))) . ' · Authority ' . $AUTH['score'] . '/100'],
      ['aeo.php', '2 · AEO Report', $aeoScore, 'ti-robot', 'text-info', 'AI readiness grade ' . h(g($X, 'ai_score.grade', '–')) . (count((array)g($C, 'ai_access', [])) ? ' · ' . count(array_filter((array)g($C, 'ai_access', []))) . '/' . count((array)g($C, 'ai_access', [])) . ' AI bots allowed' : '')],
      ['performance.php', '3 · Performance Report', $perfScore, 'ti-gauge', 'text-success', 'Speed grade ' . h(g($S, 'summary.performance_grade.grade', '–')) . ' · ' . n(g($S, 'summary.load_time_ms')) . ' ms load'],
    ]; foreach ($cards as [$file, $lbl, $sc, $ic, $tc, $foot]): ?>
    <div class="col-lg-4 col-12"><div class="card h-100 score-card position-relative"><div class="card-body p-4">
      <div class="d-flex justify-content-between border-bottom pb-4 mb-3">
        <div><h3 class="fw-bold h4 mb-1"><?= n($sc) ?><span class="fs-6 text-secondary fw-normal">/100</span></h3><span><?= $lbl ?></span></div>
        <div><i class="ti <?= $ic ?> fs-1 <?= $tc ?>"></i></div>
      </div>
      <div class="d-flex justify-content-between align-items-center small gap-2">
        <div class="text-secondary"><?= $foot ?></div>
        <a href="<?= h(page_url($file)) ?>" class="link-primary text-decoration-underline stretched-link text-nowrap">Open</a>
      </div>
    </div></div></div>
    <?php endforeach; ?>
  </div>

  <!-- KEY NUMBERS ACROSS THE THREE REPORTS -->
  <div class="row g-3 mb-4">
    <?= kpi('world', 'primary', 'Referring domains', n($AUTH['rd']), n($blTot) . ' live backlinks') ?>
    <?= kpi('key', 'success', 'Ranking keywords', n($ORG['count']), n($ORG['traffic']) . ' est. visits / mo') ?>
    <?= kpi('robot', 'info', 'AI readiness', n($aeoScore) . '/100', $AF['schemaN'] ? $AF['schemaN'] . ' schema blocks' : 'No JSON-LD schema') ?>
    <?= kpi('server', 'warning', 'Time to first byte', n(g($S, 'summary.ttfb_ms')) . ' ms', $SF['server'] !== null ? n($SF['server']) . ' ms server wait' : '') ?>
  </div>

  <!-- TOP FIXES ACROSS ALL REPORTS -->
  <div class="card mb-3">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center px-4 py-3"><h3 class="h5 mb-0">Top fixes across all reports</h3><span class="small text-secondary"><?= count($ACT) ?> in total</span></div>
    <div class="table-responsive"><table class="table mb-0 text-nowrap table-hover"><thead class="table-light border-light"><tr><th class="ps-4">Priority</th><th>Fix</th><th>Report</th><th>Area</th><th class="pe-4">Effort</th></tr></thead><tbody>
    <?php foreach (array_slice($ACT, 0, 8) as $a): $pg = $a['report'] === 'aeo' ? ['aeo.php', '2 · AEO', 'a-actions'] : ['performance.php', '3 · Performance', 'p-actions']; ?>
      <tr><td class="ps-4"><?= badge(p_cls($a['p']), $a['p']) ?></td><td class="text-wrap"><?= h($a['t']) ?></td><td><a class="link-primary" href="<?= h(page_url($pg[0], $pg[2])) ?>"><?= $pg[1] ?></a></td><td><?= h($a['area']) ?></td><td class="pe-4"><?= h($a['effort']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$ACT): ?><tr><td colspan="5" class="ps-4 text-secondary">No fixes found.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/app/footer.php'; ?>
