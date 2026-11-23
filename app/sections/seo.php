<?php defined('APP') || exit; if (!$R) return; ?>
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 mb-3">
    <div><h1 class="fs-3 mb-1">1 · SEO Report</h1><p class="mb-0 text-secondary">On-page audit, authority, organic keywords and backlink health.</p></div>
    <a class="btn btn-primary btn-sm next-btn" href="<?= h(page_url('aeo.php')) ?>">Next: AEO Report <i class="ti ti-arrow-right ms-1"></i></a>
  </div>
  <div class="d-flex flex-wrap gap-2 pills"><?= pill('s-onpage', 'On-page') . pill('s-authority', 'Authority') . pill('s-organic', 'Organic') . pill('s-backlinks', 'Backlinks') . pill('s-spam', 'Spam') . pill('s-keywords', 'Top keywords') . pill('s-branded', 'Branded') . pill('s-brandvs', 'Branded vs non') . pill('s-follow', 'Follow vs nofollow') . pill('s-related', 'Related keywords') ?></div>

  <!-- 1.1 ON-PAGE -->
  <?= sec_head('s-onpage', '1.1', 'On-page analysis') ?>
  <?= section_error($R['audit']) ?>
  <?php if ($A): ?>
  <div class="row g-3 mb-3">
    <div class="col-lg-6"><?= card_open('Page audit score') ?>
      <div class="score-split"><?= gauge($seoScore, 'Grade ' . g($A, 'summary.grade', grade_of($seoScore)), score_color($seoScore)) ?>
      <div class="flex-grow-1 min-w-0"><?php foreach ((array)g($A, 'scores.buckets', []) as $k => $v) echo bar_row(ucwords(str_replace('_', ' ', (string)$k)), (float)$v, 100, n($v), (float)$v >= 80 ? 'success' : ((float)$v >= 50 ? 'primary' : 'danger')); ?></div></div>
    <?= card_close() ?></div>
    <div class="col-lg-6"><?= card_open('Page basics') ?>
      <ul class="list-group list-group-flush">
        <?= signal('<span class="text-secondary">Title</span>', '<span class="text-end">' . h(g($A, 'basic.title', '—')) . ' <span class="text-secondary small">(' . mb_strlen((string)g($A, 'basic.title', '')) . ' chars)</span></span>') ?>
        <?= signal('<span class="text-secondary">Final URL</span>', '<span class="text-end text-break">' . h(g($A, 'basic.final_url')) . ' ' . badge(g($A, 'basic.http_code') == 200 ? 'success' : 'warning', (string)g($A, 'basic.http_code', '')) . '</span>') ?>
        <?= signal('<span class="text-secondary">Canonical</span>', '<span class="text-end text-break">' . h(g($A, 'basic.canonical', '—')) . '</span>') ?>
        <?= signal('<span class="text-secondary">H1</span>', '<span class="text-end">' . (h(implode(' | ', (array)g($A, 'headings.data.h1', []))) ?: badge('danger', 'Missing')) . '</span>') ?>
        <?php $hc = ''; foreach ((array)g($A, 'headings.counts', []) as $k => $v) $hc .= strtoupper(h($k)) . ' <b>' . n($v) . '</b>&nbsp; '; ?>
        <?= signal('<span class="text-secondary">Headings</span>', '<span class="text-end">' . $hc . '</span>') ?>
      </ul>
    <?= card_close() ?></div>
  </div>
  <div class="row g-3 mb-3">
    <?= kpi('file-text', 'primary', 'Word count', n(g($A, 'content.word_count_estimate')), 'Content/HTML ' . n(g($A, 'content.content_to_html_ratio_pct'), 1) . '%') ?>
    <?= kpi('link', 'info', 'Links (int / ext)', n(g($A, 'links.counts.internal')) . ' / ' . n(g($A, 'links.counts.external')), n(g($A, 'links.counts.nofollow')) . ' nofollow') ?>
    <?= kpi('photo', 'warning', 'Images missing alt', n(g($A, 'images.alt.missing')), 'Alt coverage ' . n(g($A, 'images.alt.coverage_pct'), 1) . '%') ?>
    <?= kpi('code', 'success', 'Structured data', n(g($A, 'structured_data.jsonld_count')), h(implode(', ', (array)g($A, 'structured_data.detected_types', []))) ?: 'No types') ?>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-lg-6"><?= card_open('Security &amp; crawl signals') ?><ul class="list-group list-group-flush">
      <?= signal('HTTPS', yesno(g($A, 'security.https'))) . signal('HSTS', yesno(g($A, 'security.hsts'))) . signal('Content-Security-Policy', yesno(g($A, 'security.content_security_policy')))
        . signal('Mixed content', g($A, 'security.mixed_content_found') ? badge('danger', 'Found') : badge('success', 'None'))
        . signal('robots.txt', yesno(g($A, 'crawl_signals.robots.found'))) . signal('sitemap.xml', yesno(g($A, 'crawl_signals.sitemap.found'))) . signal('llms.txt', yesno(g($A, 'crawl_signals.llms_txt.found'))) ?>
    </ul><?= card_close() ?></div>
    <div class="col-lg-6"><?= card_open('Priority issues') ?>
      <?php $PI = (array)g($A, 'summary.priority_issues', []); if ($PI): ?><ul class="list-group list-group-flush"><?php foreach ($PI as $pi): ?><li class="list-group-item px-0 d-flex gap-2"><i class="ti ti-alert-circle text-danger fs-5"></i><?= h(is_array($pi) ? ($pi['issue'] ?? json_encode($pi)) : $pi) ?></li><?php endforeach; ?></ul><?php else: ?><p class="text-secondary mb-0">No priority issues reported.</p><?php endif; ?>
    <?= card_close() ?></div>
  </div>
  <?php $F = (array)g($A, 'findings', []); if ($F): ?>
  <div class="mb-3"><?= card_open('All findings (' . count($F) . ')', '', '') ?>
    <?= table_open('', ['Severity', 'Category', 'Issue', 'Fix']) ?>
    <?php foreach ($F as $f): ?><tr><td><?= badge(sev_cls($f['severity'] ?? ''), $f['severity'] ?? '') ?></td><td class="text-capitalize"><?= h($f['category'] ?? '') ?></td><td class="text-wrap"><?= h($f['issue'] ?? '') ?></td><td class="text-wrap"><?= h($f['fix'] ?? '') ?></td></tr><?php endforeach; ?>
    <?= table_close() ?>
  <?= card_close() ?></div>
  <?php endif; endif; ?>

  <!-- 1.2 AUTHORITY -->
  <?= sec_head('s-authority', '1.2', 'Domain authority', 'Estimated from VebAPI backlink data — not a Moz DA or Ahrefs DR equivalent.') ?>
  <?= section_error($R['refdomains']) ?>
  <div class="row g-3 mb-3">
    <div class="col-lg-8"><?= card_open('Estimated authority score') ?>
      <div class="score-split"><?= gauge($AUTH['score'], 'of 100') ?>
      <div class="flex-grow-1 min-w-0"><?php foreach ($AUTH['parts'] as $k => $v): $max = ['Referring domains' => 60, 'Link quality' => 25, 'Dofollow share' => 10, 'Domain age' => 5, 'Spam penalty' => 10][$k];
        echo bar_row($k, abs($v), $max, ($v < 0 ? '' : '+') . n($v, 1) . '/' . $max, $v < 0 ? 'danger' : 'primary'); endforeach; ?></div></div>
      <p class="text-secondary small mb-0 mt-3">Weights: referring domains 60 · link quality 25 · dofollow share 10 · domain age 5 · spam penalty up to −10. Use it to compare sites and track the trend over time.</p>
    <?= card_close() ?></div>
    <div class="col-lg-4"><div class="row g-3 h-100">
      <?= str_replace('col-sm-6 col-xl-3', 'col-12', kpi('world', 'primary', 'Referring domains', n($AUTH['rd']), n(g($D('backlinks'), 'counts.domains.doFollow')) . ' dofollow')) ?>
      <?= str_replace('col-sm-6 col-xl-3', 'col-12', kpi('calendar', 'info', 'Domain age', $AUTH['age_years'] !== null ? h($AUTH['age_years']) . ' yrs' : '—', 'Registered ' . h(g($D('domain'), 'registration.created_at', '—')))) ?>
    </div></div>
  </div>

  <!-- 1.3 ORGANIC -->
  <?= sec_head('s-organic', '1.3', 'Organic search summary') ?>
  <?= section_error($R['keywords']) ?>
  <div class="row g-3 mb-3">
    <?= kpi('key', 'primary', 'Ranking keywords', n($ORG['count']), $ORG['count'] >= KEYWORD_LIMIT ? 'Capped at limit ' . KEYWORD_LIMIT : 'From top search keywords') ?>
    <?= kpi('trending-up', 'success', 'Est. organic traffic / mo', n($ORG['traffic'])) ?>
    <?= kpi('chart-bar', 'info', 'Total search volume', n($ORG['volume'])) ?>
    <?= kpi('list-numbers', 'warning', 'Avg. position', n($ORG['avg_rank'], 1), n($ORG['buckets']['1–3'] + $ORG['buckets']['4–10']) . ' in top 10') ?>
  </div>
  <div class="mb-3"><?= card_open('Position distribution', '', '') ?>
    <?php $mx = max(1, ...array_values($ORG['buckets'])); foreach ($ORG['buckets'] as $k => $v) echo bar_row('Position ' . $k, (float)$v, (float)$mx, n($v), in_array($k, ['1–3', '4–10']) ? 'success' : 'primary'); ?>
  <?= card_close() ?></div>

  <!-- 1.4 BACKLINKS -->
  <?= sec_head('s-backlinks', '1.4', 'Total backlinks') ?>
  <?= section_error($R['backlinks']) ?>
  <div class="row g-3 mb-3">
    <?= kpi('link', 'primary', 'Total live backlinks', n($blTot), n(g($D('backlinks'), 'counts.domains.total')) . ' referring domains') ?>
    <?= kpi('link-plus', 'success', 'New', n(g($D('backlinks'), 'counts.backlinks.new'))) ?>
    <?= kpi('link-off', 'danger', 'Lost', n(g($D('backlinks'), 'counts.backlinks.lost'))) ?>
    <?= kpi('unlink', 'warning', 'Broken / image links', n(g($D('backlinks'), 'counts.backlinks.broken')) . ' / ' . n(g($D('backlinks'), 'counts.backlinks.image'))) ?>
  </div>
  <?php $BL = (array)g($D('backlinks'), 'backlinks', []); if ($BL): ?>
  <div class="mb-3"><?= card_open('Backlinks list <span class="text-secondary fs-6 fw-normal">(showing ' . count($BL) . ' of ' . n($blTot) . ')</span>', '<input class="form-control form-control-sm filter" style="max-width:240px" placeholder="Filter backlinks…" data-target="tbl-bl" aria-label="Filter backlinks">', '') ?>
    <?= table_open('tbl-bl', ['Source page', 'Anchor', 'Type', '>Domain rank', '>Spam', 'First seen']) ?>
    <?php foreach ($BL as $b): ?><tr>
      <td class="text-wrap"><a href="<?= h($b['url_from'] ?? '#') ?>" target="_blank" rel="noopener nofollow"><?= h($b['url_from'] ?? '') ?></a></td>
      <td class="text-wrap"><?= h($b['anchor'] ?? ($b['alt'] ?? '')) ?></td>
      <td><?= !empty($b['nofollow']) ? badge('secondary', 'nofollow') : badge('success', 'dofollow') ?></td>
      <td class="text-end"><?= n($b['domain_inlink_rank'] ?? null) ?></td>
      <td class="text-end"><?= badge(spam_cls($b['spam_score'] ?? null), n($b['spam_score'] ?? null)) ?></td>
      <td><?= h($b['first_seen'] ?? '') ?></td></tr>
    <?php endforeach; ?>
    <?= table_close() ?>
  <?= card_close() ?></div>
  <?php endif; ?>

  <!-- 1.5 SPAM -->
  <?= sec_head('s-spam', '1.5', 'Spam backlinks') ?>
  <?= section_error($R['spam']) ?>
  <?php $SP = (array)g($D('spam'), 'backlinks', []); $spPct = $blTot ? $spamTot / $blTot * 100 : 0; $hl = $spPct > 20 ? ['danger', 'At risk'] : ($spPct > 5 ? ['warning', 'Watch'] : ['success', 'Healthy']); ?>
  <div class="row g-3 mb-3">
    <?= str_replace('col-sm-6 col-xl-3', 'col-md-4', kpi('alert-triangle', 'danger', 'Spam backlinks (score ≥ ' . SPAM_MIN_SCORE . ')', n($spamTot), n($spPct, 1) . '% of all backlinks')) ?>
    <?= str_replace('col-sm-6 col-xl-3', 'col-md-4', kpi('world-off', 'warning', 'Spam referring domains', n(g($D('spam'), 'counts.domains.total')), 'Candidates for disavow review')) ?>
    <?= str_replace('col-sm-6 col-xl-3', 'col-md-4', kpi('shield-check', $hl[0], 'Profile health', $hl[1], 'Over 20% at risk · 5–20% watch')) ?>
  </div>
  <?php if ($SP): usort($SP, fn($a, $b) => ($b['spam_score'] ?? 0) <=> ($a['spam_score'] ?? 0)); ?>
  <div class="mb-3"><?= card_open('Spam backlinks — disavow candidates <span class="text-secondary fs-6 fw-normal">(' . count($SP) . ')</span>', '<input class="form-control form-control-sm filter" style="max-width:240px" placeholder="Filter spam backlinks…" data-target="tbl-sp" aria-label="Filter spam backlinks">', '') ?>
    <?= table_open('tbl-sp', ['Source page', 'Domain', 'Anchor', '>Spam score', 'Type']) ?>
    <?php foreach ($SP as $b): ?><tr>
      <td class="text-wrap"><a href="<?= h($b['url_from'] ?? '#') ?>" target="_blank" rel="noopener nofollow"><?= h($b['url_from'] ?? '') ?></a></td>
      <td><?= h($b['domain_from'] ?? '') ?></td><td class="text-wrap"><?= h($b['anchor'] ?? '') ?></td>
      <td class="text-end"><?= badge(spam_cls($b['spam_score'] ?? null), n($b['spam_score'] ?? null)) ?></td>
      <td><?= !empty($b['nofollow']) ? 'nofollow' : 'dofollow' ?></td></tr>
    <?php endforeach; ?>
    <?= table_close() ?>
    <h4 class="h6 mt-4 mb-1">Disavow file (domains)</h4>
    <pre class="code" id="disavow"><?php
      echo h("# Disavow list for {$T['domain']} — generated " . date('Y-m-d') . "\n# Review manually before uploading to Google Search Console\n");
      foreach (array_unique(array_filter(array_column($SP, 'domain_from'))) as $dm) echo h("domain:$dm\n"); ?></pre>
    <button type="button" class="btn btn-light btn-sm copy-btn" data-copy-target="disavow"><i class="ti ti-copy me-1"></i><span>Copy disavow list</span></button>
  <?= card_close() ?></div>
  <?php elseif ($R['spam']['ok']): ?><p class="text-secondary">No backlinks at or above spam score <?= SPAM_MIN_SCORE ?>.</p><?php endif; ?>

  <!-- 1.6 TOP KEYWORDS -->
  <?= sec_head('s-keywords', '1.6', 'Top organic keywords (' . n(count($KW)) . ')', 'Sorted by estimated traffic. ▲ = position improved (VebAPI field rankChange).') ?>
  <?= section_error($R['keywords']) ?>
  <?php if ($KW): ?>
  <div class="mb-3"><?= card_open('Keywords', '<input class="form-control form-control-sm filter" style="max-width:240px" placeholder="Filter keywords…" data-target="tbl-kw" aria-label="Filter keywords">', '') ?>
    <?= table_open('tbl-kw', ['#', 'Keyword', '>Position', '>Change', '>Volume', '>Est. traffic', '>Difficulty', 'Ranking URL']) ?>
    <?php foreach ($KW as $i => $k): $chg = $k['rankChange'] ?? null; ?><tr>
      <td class="text-secondary"><?= $i + 1 ?></td><td class="text-wrap fw-medium"><?= h($k['keyword'] ?? '') ?></td><td class="text-end fw-semibold"><?= n($k['rank'] ?? null) ?></td>
      <td class="text-end"><?= is_numeric($chg) && $chg != 0 ? badge($chg > 0 ? 'success' : 'danger', ($chg > 0 ? '▲' : '▼') . abs((int)$chg)) : '—' ?></td>
      <td class="text-end"><?= n($k['searchVolume'] ?? null) ?></td><td class="text-end"><?= n($k['seoClicks'] ?? null) ?></td><td class="text-end"><?= n($k['rankingDifficulty'] ?? null) ?></td>
      <td><a href="<?= h($k['topRankedUrl'] ?? '#') ?>" target="_blank" rel="noopener"><?= h(preg_replace('~^https?://[^/]+~', '', (string)($k['topRankedUrl'] ?? '')) ?: '/') ?></a></td></tr>
    <?php endforeach; ?>
    <?= table_close() ?>
  <?= card_close() ?></div>
  <?php elseif ($R['keywords']['ok']): ?><p class="text-secondary">No ranking keywords found for this domain.</p><?php endif; ?>

  <!-- 1.7 BRANDED -->
  <?= sec_head('s-branded', '1.7', 'Organic branded search', 'Brand terms: <b>' . h(implode(', ', $brandTerms)) . '</b> — matched ignoring spaces and case. Add variants in the form above.') ?>
  <div class="row g-3 mb-3">
    <?= kpi('rosette-discount-check', 'primary', 'Branded keywords', n($BR['count'])) ?>
    <?= kpi('trending-up', 'success', 'Branded traffic / mo', n($BR['traffic'])) ?>
    <?= kpi('chart-bar', 'info', 'Branded search volume', n($BR['volume'])) ?>
    <?= kpi('list-numbers', 'warning', 'Avg. branded position', n($BR['avg_rank'], 1)) ?>
  </div>
  <?php if ($SPLIT['branded']): ?>
  <div class="mb-3"><?= card_open('Branded keywords', '', '') ?>
    <?= table_open('', ['Keyword', '>Position', '>Volume', '>Est. traffic']) ?>
    <?php foreach ($SPLIT['branded'] as $k): ?><tr><td class="text-wrap"><?= h($k['keyword'] ?? '') ?></td><td class="text-end"><?= n($k['rank'] ?? null) ?></td><td class="text-end"><?= n($k['searchVolume'] ?? null) ?></td><td class="text-end"><?= n($k['seoClicks'] ?? null) ?></td></tr><?php endforeach; ?>
    <?= table_close() ?>
  <?= card_close() ?></div>
  <?php endif; ?>

  <!-- 1.8 BRANDED VS NON -->
  <?= sec_head('s-brandvs', '1.8', 'Branded vs non-branded') ?>
  <?php $nbShare = $ORG['traffic'] ? $NB['traffic'] / $ORG['traffic'] * 100 : 0; ?>
  <div class="row g-3 mb-3">
    <div class="col-lg-6"><?= card_open('By keyword count') . donut($BR['count'], $NB['count'], 'Branded', 'Non-branded') . card_close() ?></div>
    <div class="col-lg-6"><?= card_open('By estimated traffic') . donut($BR['traffic'], $NB['traffic'], 'Branded', 'Non-branded') . card_close() ?></div>
  </div>
  <div class="mb-3"><?= card_open('Comparison', '', '') ?>
    <?= table_open('', ['', '>Keywords', '>Traffic', '>Volume', '>Avg pos.', '>Top-10'], false) ?>
    <?php foreach (['Branded' => $BR, 'Non-branded' => $NB] as $lbl => $x): ?>
    <tr><td class="fw-semibold"><?= $lbl ?></td><td class="text-end"><?= n($x['count']) ?></td><td class="text-end"><?= n($x['traffic']) ?></td><td class="text-end"><?= n($x['volume']) ?></td><td class="text-end"><?= n($x['avg_rank'], 1) ?></td><td class="text-end"><?= n($x['buckets']['1–3'] + $x['buckets']['4–10']) ?></td></tr>
    <?php endforeach; ?>
    <?= table_close() ?>
    <div class="alert alert-info d-flex gap-2 align-items-start mt-3 mb-0" role="alert"><i class="ti ti-bulb fs-5"></i><div><b>Interpretation:</b> <?= $ORG['traffic'] == 0 ? 'no organic traffic detected yet.' : ($nbShare >= 60 ? 'healthy — most traffic (' . n($nbShare) . '%) comes from non-branded discovery searches.' : 'only ' . n($nbShare) . '% of traffic is non-branded, so traffic depends on people already searching for the brand. Non-branded content is the growth lever.') ?></div></div>
  <?= card_close() ?></div>

  <!-- 1.9 FOLLOW VS NOFOLLOW -->
  <?= sec_head('s-follow', '1.9', 'Backlinks: follow vs nofollow', 'Totals from VebAPI counts (full index, not just the ' . BACKLINK_LIMIT . ' listed rows). A natural profile mixes both; near-100% dofollow can look manipulative.') ?>
  <?= section_error($R['backlinks']) ?>
  <?php $dT = (int)g($D('backlinks'), 'counts.domains.total', 0); $dD = (int)g($D('backlinks'), 'counts.domains.doFollow', 0); ?>
  <div class="row g-3 mb-3">
    <div class="col-lg-6"><?= card_open('Backlinks') . donut($blDo, $blNo, 'Dofollow', 'Nofollow') . card_close() ?></div>
    <div class="col-lg-6"><?= card_open('Referring domains') . donut($dD, max(0, $dT - $dD), 'Dofollow', 'Nofollow only') . card_close() ?></div>
  </div>

  <!-- 1.10 RELATED KEYWORDS -->
  <?= sec_head('s-related', '1.10', 'Related keywords (opportunities)', $seedI !== '' ? 'Seed: <b>' . h($seedI) . '</b> · country <b>' . h(strtoupper($ccI)) . '</b>. Value = volume × CTR at the chosen position × CPC.' : 'Enter a seed keyword in the form above to pull related keywords.') ?>
  <?php if ($seedI !== ''): ?><?= section_error($R['related'] ?? null) ?>
    <?php if ($REL): ?>
    <div class="mb-3"><?= card_open('Traffic value estimator', '', '') ?>
      <div class="mb-3"><label for="pos" class="form-label">Target ranking position: <b class="text-primary" id="posv">3</b></label><input id="pos" type="range" class="form-range" min="1" max="10" value="3"></div>
      <?= table_open('tbl-rel', ['Keyword', '>Volume', '>CPC', 'Competition', '>Score', '>Est. clicks/mo', '>Ad-equivalent value']) ?>
      <?php foreach ($REL as $k): $vol = (float)($k['vol'] ?? $k['v'] ?? 0); $cpc = (float)($k['cpc'] ?? 0); $cmp = (string)($k['competition'] ?? ''); ?>
        <tr data-vol="<?= $vol ?>" data-cpc="<?= $cpc ?>"><td class="text-wrap fw-medium"><?= h($k['text'] ?? '') ?></td><td class="text-end"><?= n($vol) ?></td><td class="text-end">$<?= n($cpc, 2) ?></td><td><?= badge($cmp === 'High' ? 'danger' : ($cmp === 'Low' ? 'success' : 'warning'), $cmp ?: '—') ?></td><td class="text-end"><?= h($k['score'] ?? '') ?></td><td class="text-end est-c">—</td><td class="text-end est-v fw-semibold text-primary">—</td></tr>
      <?php endforeach; ?>
      <?= table_close() ?>
      <p class="text-secondary small mb-0 mt-3">Click-through rates are typical organic averages by position (1: 28%, 2: 15%, 3: 11%, falling to 2.5% at 10) — an assumption, not VebAPI data.</p>
    <?= card_close() ?></div>
    <?php elseif (($R['related']['ok'] ?? false)): ?><p class="text-secondary">No related keywords returned for this seed.</p><?php endif; ?>
  <?php endif; ?>
