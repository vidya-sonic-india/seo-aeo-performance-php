<?php defined('APP') || exit; if (!$R) return; ?>
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 mb-3">
    <div><h1 class="fs-3 mb-1">2 · AEO Report</h1><p class="mb-0 text-secondary">How ready the site is to be read, understood and cited by AI answer engines.</p></div>
    <a class="btn btn-primary btn-sm next-btn" href="<?= h(page_url('performance.php')) ?>">Next: Performance Report <i class="ti ti-arrow-right ms-1"></i></a>
  </div>
  <div class="d-flex flex-wrap gap-2 pills"><?= pill('a-score', 'AI readiness') . pill('a-content', 'Content &amp; structure') . pill('a-discovery', 'Discovery &amp; trust') . pill('a-crawlers', 'AI crawlers') . pill('a-issues', 'Audit issues') . pill('a-actions', 'Action plan') ?></div>

  <!-- 2.1 SCORE -->
  <?= sec_head('a-score', '2.1', 'AI readiness score') ?>
  <?= section_error($R['aeo']) ?>
  <?php if ($X): $sc = $AF['score']; ?>
  <div class="row g-3 mb-3">
    <div class="col-lg-7"><?= card_open('Score breakdown') ?>
      <div class="score-split"><?= gauge($sc['total'] ?? null, 'Grade ' . ($sc['grade'] ?? '–'), score_color($sc['total'] ?? null)) ?>
      <div class="flex-grow-1 min-w-0"><?php foreach (['crawlability' => 'Crawlability', 'structure' => 'Structure', 'content_depth' => 'Content depth', 'semantic_markup' => 'Semantic markup', 'technical_cleanliness' => 'Technical'] as $k => $l) if (isset($sc[$k])) echo bar_row($l, (float)$sc[$k], 20, n($sc[$k]) . '/20', (float)$sc[$k] < 15 ? 'danger' : 'primary'); ?></div></div>
      <p class="text-secondary small mb-0 mt-3">Each bucket is out of 20. Red bars are under 15 and hold most of the missing <?= n(100 - (float)($sc['total'] ?? 100)) ?> points.</p>
    <?= card_close() ?></div>
    <div class="col-lg-5"><?= card_open('Machine-readable signals') ?><ul class="list-group list-group-flush">
      <?= signal('robots.txt', yesno(g($X, 'ai_discovery.robots_txt.exists'), 'Present', 'Missing'))
        . signal('llms.txt' . (g($X, 'ai_discovery.llms_txt.lines') ? ' <span class="text-secondary small">· ' . n(g($X, 'ai_discovery.llms_txt.lines')) . ' lines</span>' : ''), yesno(g($X, 'ai_discovery.llms_txt.exists'), 'Present', 'Missing'))
        . signal('XML sitemap', $AF['sitemapOk'] ? badge('success', 'Found') : badge('danger', 'Not found'))
        . signal('Canonical tag', $AF['canonical'] ? badge('success', 'Present') : badge('danger', 'Missing'))
        . signal('JSON-LD schema', $AF['schemaN'] ? badge('success', $AF['schemaN'] . ' blocks') : badge('danger', 'None'))
        . signal('Indexable', yesno(g($X, 'ai_visibility.indexable'), 'Yes', 'Blocked'))
        . signal('AI scrapable', yesno(g($X, 'ai_scrapable'), 'Yes', 'No')) ?>
    </ul><?= card_close() ?></div>
  </div>

  <!-- 2.2 CONTENT -->
  <?= sec_head('a-content', '2.2', 'Content & structure', 'The audit reads <code>' . h(g($X, 'structure.main_selector_used', '')) . '</code>, so navigation and footer count against text density.') ?>
  <div class="row g-3 mb-3">
    <?= kpi('file-text', 'primary', 'Words on page', n(g($X, 'content.main_word_count')), n(g($X, 'content.reading_time_minutes')) . ' min read') ?>
    <?= kpi('percentage', 'danger', 'Text density', n($AF['density'], 2) . '%', is_numeric($AF['density']) && $AF['density'] < 15 ? 'Low — aim for 15%+' : 'OK') ?>
    <?= kpi('code', 'info', 'Scripts', n(g($X, 'content.script_count')), g($X, 'content.js_heavy') ? 'JS-heavy' : 'Not JS-heavy') ?>
    <?= kpi('link', 'success', 'Links', n(g($X, 'links.internal')) . ' / ' . n(g($X, 'links.external')), 'Internal / external') ?>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-lg-6"><?= card_open('Heading outline') ?>
      <?php $hh = $AF['headings']; $hm = max(1, ...array_map('intval', array_values($hh ?: [0]))); foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $k) echo bar_row(strtoupper($k), (float)($hh[$k] ?? 0), (float)$hm, n($hh[$k] ?? 0), $k === 'h3' && ($hh['h3'] ?? 0) > 4 * max(1, $hh['h2'] ?? 0) ? 'danger' : 'primary'); ?>
      <p class="text-secondary small mb-0 mt-2"><?= n($hh['h3'] ?? 0) ?> H3s under <?= n($hh['h2'] ?? 0) ?> H2s. Answer engines extract cleaner passages from shorter sections with their own H2.</p>
    <?= card_close() ?></div>
    <div class="col-lg-6"><?= card_open('Title &amp; description') ?>
      <p class="text-secondary small mb-1">Title · <?= mb_strlen((string)g($X, 'structure.title', '')) ?> characters</p><h4 class="h6 mb-4"><?= h(g($X, 'structure.title', '—')) ?></h4>
      <p class="text-secondary small mb-1">Meta description · <?= mb_strlen((string)g($X, 'structure.meta_description', '')) ?> characters</p><p class="mb-0"><?= h(g($X, 'structure.meta_description', '—')) ?></p>
    <?= card_close() ?></div>
  </div>

  <!-- 2.3 DISCOVERY & TRUST -->
  <?= sec_head('a-discovery', '2.3', 'Discovery files & trust pages') ?>
  <div class="row g-3 mb-3">
    <div class="col-lg-7"><?= card_open('Discovery files') ?>
      <?= table_open('', ['File', 'Status', '>Size'], false) ?>
        <tr><td>/robots.txt</td><td><?= g($X, 'ai_discovery.robots_txt.exists') ? badge(g($X, 'ai_discovery.robots_txt.sitemaps_found') ? 'success' : 'warning', g($X, 'ai_discovery.robots_txt.status_code') . (g($X, 'ai_discovery.robots_txt.sitemaps_found') ? ' · Sitemap declared' : ' · no Sitemap line')) : badge('danger', 'Missing') ?></td><td class="text-end"><?= n(g($X, 'ai_discovery.robots_txt.size_bytes')) ?> B</td></tr>
        <tr><td>/llms.txt</td><td><?= g($X, 'ai_discovery.llms_txt.exists') ? badge(($AF['llmAge'] ?? 0) > 90 ? 'warning' : 'success', g($X, 'ai_discovery.llms_txt.status_code') . ($AF['llmDate'] ? ' · updated ' . $AF['llmDate'] : '')) : badge('danger', 'Missing') ?></td><td class="text-end"><?= n(g($X, 'ai_discovery.llms_txt.size_bytes')) ?> B</td></tr>
        <?php foreach ((array)g($X, 'ai_discovery.sitemap_intel.checked', []) as $sm): ?><tr><td><?= h(parse_url((string)($sm['url'] ?? ''), PHP_URL_PATH) ?: ($sm['url'] ?? '')) ?></td><td><?= badge(!empty($sm['ok']) ? 'success' : 'danger', (string)($sm['status_code'] ?? '')) ?></td><td class="text-end"><?= n($sm['size_bytes'] ?? null) ?> B</td></tr><?php endforeach; ?>
      <?= table_close() ?>
    <?= card_close() ?></div>
    <div class="col-lg-5"><?= card_open('Trust pages') ?><ul class="list-group list-group-flush">
      <?php foreach ($AF['trust'] as $k => $v): $u = g($X, 'trust_signals.page_urls.' . $k); echo signal('<span class="text-capitalize">' . ($u ? '<a href="' . h($u) . '" target="_blank" rel="noopener" class="link-primary">' . h($k) . '</a>' : h($k)) . '</span>', yesno($v, 'Found', 'Missing')); endforeach; ?>
    </ul><?= card_close() ?></div>
  </div>
  <?php endif; ?>

  <!-- 2.4 AI CRAWLERS -->
  <?= sec_head('a-crawlers', '2.4', 'AI crawler access') ?>
  <?= section_error($R['crawler']) ?>
  <?php $sim = (array)g($X, 'ai_simulation.ai_bots', []); $acc = (array)g($C, 'ai_access', []); ?>
  <?php if ($C && host_of((string)g($C, 'url', $T['domain'])) !== $T['domain']): ?><div class="alert alert-warning d-flex gap-2" role="alert"><i class="ti ti-alert-triangle fs-5"></i><div><b>Data check:</b> the crawler check returned results for <b><?= h(g($C, 'url')) ?></b>, not <?= h($T['domain']) ?>.</div></div><?php endif; ?>
  <div class="row g-3 mb-3">
    <?php if ($acc): ?>
    <div class="col-lg-8"><?= card_open(count(array_filter($acc)) . ' of ' . count($acc) . ' AI agents allowed by robots.txt') ?>
      <div class="bot-grid"><?php foreach ($acc as $bot => $ok): ?><div class="bot-tile"><span><?= h($bot) ?></span><?= $ok ? badge('success', 'Allow') : badge('danger', 'Block') ?></div><?php endforeach; ?></div>
      <?php if ((array)g($C, 'suggestions', [])): ?><ul class="list-group list-group-flush mt-3"><?php foreach ((array)g($C, 'suggestions', []) as $s): ?><li class="list-group-item px-0 d-flex gap-2"><i class="ti ti-bulb text-warning fs-5"></i><?= h($s) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?= card_close() ?></div>
    <?php endif; ?>
    <?php if ($sim): $fix = ['perplexity' => 'PerplexityBot', 'anthropic' => 'ClaudeBot']; ?>
    <div class="<?= $acc ? 'col-lg-4' : 'col-12' ?>"><?= card_open('AI audit bot simulation') ?><ul class="list-group list-group-flush">
      <?php foreach ($sim as $eng => $b) echo signal('<span><span class="fw-medium text-capitalize">' . h(str_replace('_', ' ', (string)$eng)) . '</span><br><span class="text-secondary small">' . h($b['bot'] ?? '') . (isset($fix[$eng]) ? ' · also check ' . $fix[$eng] : '') . '</span></span>', yesno($b['allowed_by_robots'] ?? null, 'Allowed', 'Blocked')); ?>
    </ul><p class="text-secondary small mb-0 mt-2">CCBot is Common Crawl, not Perplexity; anthropic-ai is a legacy token. Labels come from the API.</p><?= card_close() ?></div>
    <?php endif; ?>
  </div>

  <!-- 2.5 ISSUES -->
  <?= sec_head('a-issues', '2.5', 'Issues & suggestions from the AI audit') ?>
  <?php if ($X): ?>
  <div class="mb-3"><?= card_open('Audit output', '', '') ?>
    <?= table_open('', ['Type', 'Severity', 'Area', 'Detail', 'Impact'], false) ?>
    <?php foreach ((array)g($X, 'issues', []) as $i): ?><tr><td><?= badge('danger', 'Issue') ?></td><td><?= badge(sev_cls($i['severity'] ?? ''), $i['severity'] ?? '') ?></td><td class="text-capitalize"><?= h(str_replace('_', ' ', (string)($i['id'] ?? ''))) ?></td><td class="text-wrap"><?= h($i['evidence'] ?? '') ?></td><td class="text-wrap"><?= h($i['impact'] ?? '') ?></td></tr><?php endforeach; ?>
    <?php foreach ((array)g($X, 'suggestions', []) as $s): ?><tr><td><?= badge('info', 'Suggestion') ?></td><td><?= badge(sev_cls($s['priority'] ?? ''), $s['priority'] ?? '') ?></td><td><?= h($s['category'] ?? '') ?></td><td class="text-wrap"><?= h(str_replace('`', '', (string)($s['message'] ?? ''))) ?></td><td class="text-wrap"><?= h($s['impact'] ?? '') ?></td></tr><?php endforeach; ?>
    <?= table_close() ?>
  <?= card_close() ?></div>
  <?php endif; ?>

  <!-- 2.6 AEO ACTIONS -->
  <?= sec_head('a-actions', '2.6', 'AEO action plan', 'Ranked by priority. Tick items as they ship — progress is saved in this browser per domain.') ?>
  <?= render_actions(array_values(array_filter($ACT, fn($a) => $a['report'] === 'aeo'))) ?>
