<?php defined('APP') || exit; if (!$R) return; ?>
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 mb-3">
    <div><h1 class="fs-3 mb-1">3 · Performance Report</h1><p class="mb-0 text-secondary">Load speed, server response, page weight and third-party cost.</p></div>
    <a class="btn btn-outline-secondary btn-sm next-btn" href="<?= h(page_url('report.php')) ?>"><i class="ti ti-file-text me-1"></i>Open full report</a>
  </div>
  <div class="d-flex flex-wrap gap-2 pills"><?= pill('p-summary', 'Summary') . pill('p-timeline', 'Load timeline') . pill('p-weight', 'Page weight') . pill('p-files', 'Heaviest files') . pill('p-audit', 'Page-audit signals') . pill('p-actions', 'Fixes') ?></div>

  <!-- 3.1 SUMMARY -->
  <?= sec_head('p-summary', '3.1', 'Speed summary') ?>
  <?= section_error($R['speed']) ?>
  <?php if ($S): $SS = (array)g($S, 'summary', []); $pk = (float)($SS['page_size_kb'] ?? 0); ?>
  <div class="row g-3 mb-3">
    <?= kpi('gauge', 'success', 'Speed grade', h(g($SS, 'performance_grade.grade', '–')) . ' · ' . n(g($SS, 'performance_grade.score')), n($SS['load_time_ms'] ?? null) . ' ms load · ' . n($SS['requests'] ?? null) . ' requests') ?>
    <?= kpi('server', 'danger', 'Time to first byte', n($SS['ttfb_ms'] ?? null) . ' ms', $SF['server'] !== null ? n($SF['server']) . ' ms server wait' : '') ?>
    <?= kpi('weight', 'primary', 'Page weight', n($pk, 1) . ' KB', n($SS['unique_domains'] ?? null) . ' domains · HTML ' . n(($SS['main']['html_size_bytes'] ?? 0) / 1024, 1) . ' KB') ?>
    <?= kpi('external-link', 'warning', 'Third-party weight', n($SF['tpKb'], 1) . ' KB', ($pk ? n($SF['tpKb'] / $pk * 100) : '—') . '% of the page') ?>
  </div>

  <!-- 3.2 TIMELINE -->
  <?= sec_head('p-timeline', '3.2', 'Main document load timeline') ?>
  <?php if ($SF['phases']): $tot = array_sum(array_map('floatval', $SF['phases'])); ?>
  <div class="card mb-3"><div class="card-body p-4"><div class="row g-4 align-items-center">
    <div class="col-xl-3"><h3 class="h5 mb-3">Where the time goes</h3><div class="display-6 fw-bold text-primary mb-1"><?= n($tot) ?> ms</div>
      <p class="fw-medium mb-2"><?= $tot ? n(($SF['server'] ?? 0) / $tot * 100) : 0 ?>% server wait</p>
      <p class="text-secondary small mb-0"><?= g($S, 'summary.main.timings.appconnect_time') === null ? 'TLS time is not reported separately, so it is merged with request send.' : '' ?></p></div>
    <div class="col-xl-9"><?php foreach ($SF['phases'] as $l => $v) echo bar_row($l, (float)$v, (float)$tot, n($v, 1) . ' ms', $l === 'Server wait' && $tot && $v / $tot > .5 ? 'danger' : 'primary'); ?></div>
  </div></div></div>
  <?php endif; ?>

  <!-- 3.3 WEIGHT -->
  <?= sec_head('p-weight', '3.3', 'Page weight breakdown') ?>
  <?php $ct = array_values(array_filter((array)g($S, 'content_size_by_content_type', []), fn($c) => (float)($c['size_kb'] ?? 0) > 0)); $cols = ['#E66239', '#f7a085', '#00B8DB', '#F0B100', '#00C951', '#525252']; ?>
  <div class="row g-3 mb-3">
    <div class="col-lg-6"><?= card_open('Bytes by content type') ?>
      <div class="stackbar"><?php foreach ($ct as $i => $c): ?><i style="width:<?= (float)($c['percent'] ?? 0) ?>%;background:<?= $cols[$i % 6] ?>" title="<?= h($c['content_type'] ?? '') ?>"></i><?php endforeach; ?></div>
      <ul class="list-unstyled legend d-flex flex-wrap gap-3 mb-3"><?php foreach ($ct as $i => $c): ?><li><i style="background:<?= $cols[$i % 6] ?>"></i><?= h($c['content_type'] ?? '') ?> <b><?= n($c['size_kb'] ?? 0, 1) ?> KB</b></li><?php endforeach; ?></ul>
      <?= table_open('', ['Type', '>Requests', '>Share of requests'], false) ?>
      <?php foreach ((array)g($S, 'requests_by_content_type', []) as $r): ?><tr><td><?= h($r['content_type'] ?? '') ?></td><td class="text-end"><?= n($r['requests'] ?? 0) ?></td><td class="text-end"><?= n($r['percent'] ?? 0, 1) ?>%</td></tr><?php endforeach; ?>
      <?= table_close() ?>
    <?= card_close() ?></div>
    <div class="col-lg-6"><?= card_open('Bytes by domain') ?>
      <?php foreach ((array)g($S, 'content_size_by_domain', []) as $dm) { $hst = host_of((string)($dm['domain'] ?? '')); echo bar_row($hst, (float)($dm['size_kb'] ?? 0), max(1.0, $pk), n($dm['size_kb'] ?? 0, 1) . ' KB', $hst === $T['domain'] ? 'primary' : 'danger'); } ?>
      <p class="text-secondary small mb-0 mt-2"><span class="text-danger">Red</span> is third-party: tag managers, pixels, CDNs and fonts loaded from other domains.</p>
    <?= card_close() ?></div>
  </div>

  <!-- 3.4 FILES -->
  <?= sec_head('p-files', '3.4', 'Heaviest files') ?>
  <?php $FR = (array)g($S, 'file_requests', []); usort($FR, fn($a, $b) => ($b['size_bytes'] ?? 0) <=> ($a['size_bytes'] ?? 0)); ?>
  <div class="mb-3"><?= card_open('Files by size', '', '') ?>
    <?= table_open('', ['File', 'Type', '>Size', '>Time', '>Status']) ?>
    <?php foreach ($FR as $f): $u = (string)($f['url'] ?? ''); ?><tr><td class="text-wrap"><a href="<?= h($u) ?>" target="_blank" rel="noopener nofollow"><?= h(mb_strlen($u) > 80 ? mb_substr($u, 0, 80) . '…' : $u) ?></a></td><td><?= h($f['type'] ?? '') ?></td><td class="text-end fw-semibold"><?= n(($f['size_bytes'] ?? 0) / 1024, 1) ?> KB</td><td class="text-end"><?= n(((float)g($f, 'timings.total_time', 0)) * 1000) ?> ms</td><td class="text-end"><?= badge(($f['status'] ?? 200) < 400 ? 'success' : 'danger', (string)($f['status'] ?? '—')) ?></td></tr><?php endforeach; ?>
    <?= table_close() ?>
  <?= card_close() ?></div>
  <?php endif; ?>

  <!-- 3.5 PAGE-AUDIT SIGNALS -->
  <?= sec_head('p-audit', '3.5', 'Page-audit performance signals', 'From the on-page audit, measured on the page URL.') ?>
  <?php if ($A): ?>
  <div class="row g-3 mb-3">
    <?= kpi('clock', 'danger', 'TTFB (page audit)', n(g($A, 'performance.ttfb_seconds'), 2) . ' s', 'Total ' . n(g($A, 'performance.total_time_seconds'), 2) . ' s') ?>
    <?= kpi('player-pause', 'warning', 'Render-blocking scripts', n(g($A, 'performance.scripts.blocking_count', g($A, 'resources.scripts.blocking_count'))), 'of ' . n(g($A, 'performance.scripts.count')) . ' scripts') ?>
    <?= kpi('file-zip', 'success', 'Compression', h(g($A, 'performance.content_encoding', '—')), g($A, 'performance.content_encoding') ? 'Enabled' : 'Not detected') ?>
    <?= kpi('photo-down', 'info', 'Lazy-loaded images', n(g($A, 'images.counts.lazy')) . ' / ' . n(g($A, 'images.counts.total'))) ?>
  </div>
  <?php if ((array)g($A, 'performance.suggestions', [])): ?><div class="mb-3"><?= card_open('Audit suggestions', '', '') ?><ul class="list-group list-group-flush"><?php foreach ((array)g($A, 'performance.suggestions', []) as $s): ?><li class="list-group-item px-0 d-flex gap-2"><i class="ti ti-bulb text-warning fs-5"></i><?= h(is_array($s) ? json_encode($s) : $s) ?></li><?php endforeach; ?></ul><?= card_close() ?></div><?php endif; ?>
  <?php endif; ?>

  <!-- 3.6 PERF ACTIONS -->
  <?= sec_head('p-actions', '3.6', 'Performance fixes', 'Ranked by priority. Tick items as they ship.') ?>
  <?= render_actions(array_values(array_filter($ACT, fn($a) => $a['report'] === 'perf'))) ?>
