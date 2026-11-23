<?php
defined('APP') || exit;
/** Shared InApp layout: <head>, top bar and sidebar. Expects $PAGE and $PAGE_TITLE from the page. */
$NAV = [
    'seo'         => ['seo.php', 'ti-search', '1 · SEO Report', [
        's-onpage' => 'On-page', 's-authority' => 'Authority', 's-organic' => 'Organic summary', 's-backlinks' => 'Backlinks', 's-spam' => 'Spam backlinks',
        's-keywords' => 'Top keywords', 's-branded' => 'Branded search', 's-brandvs' => 'Branded vs non', 's-follow' => 'Follow vs nofollow', 's-related' => 'Related keywords']],
    'aeo'         => ['aeo.php', 'ti-robot', '2 · AEO Report', [
        'a-score' => 'AI readiness', 'a-content' => 'Content & structure', 'a-discovery' => 'Discovery & trust', 'a-crawlers' => 'AI crawlers', 'a-issues' => 'Audit issues', 'a-actions' => 'AEO action plan']],
    'performance' => ['performance.php', 'ti-gauge', '3 · Performance Report', [
        'p-summary' => 'Speed summary', 'p-timeline' => 'Load timeline', 'p-weight' => 'Page weight', 'p-files' => 'Heaviest files', 'p-audit' => 'Page-audit signals', 'p-actions' => 'Performance fixes']],
];
$fixCount = ['aeo' => 0, 'performance' => 0];
if ($R) foreach ($ACT as $a) $fixCount[$a['report'] === 'aeo' ? 'aeo' : 'performance']++;
$subAnchorCount = ['a-actions' => $fixCount['aeo'], 'p-actions' => $fixCount['performance']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($PAGE_TITLE) ?><?= $T && $R ? ' — ' . h($T['domain']) : '' ?> · SEO · AEO · Performance</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.35.0/dist/tabler-icons.min.css">
<link rel="stylesheet" href="assets/app.css?v=2">
</head>
<body data-domain="<?= $T ? h($T['domain']) : '' ?>" data-page="<?= h($PAGE) ?>">
<div id="overlay" class="overlay"></div>

<!-- TOPBAR -->
<nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
  <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm" aria-label="Collapse menu"><i class="ti ti-layout-sidebar-left-expand"></i></button>
  <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2" aria-label="Open menu"><i class="ti ti-layout-sidebar-left-expand"></i></button>
  <ul class="list-unstyled d-flex align-items-center mb-0 gap-2">
    <?php if ($T && $R): ?><li class="d-none d-md-block"><a href="<?= h(page_url('index.php')) ?>" class="badge bg-primary-subtle text-primary border border-primary fs-6 fw-normal px-3 py-2 text-decoration-none"><i class="ti ti-world me-1"></i><?= h($T['domain']) ?></a></li><?php endif; ?>
    <li><span class="small text-secondary px-2">Credits <b class="text-body"><?= $credits === 'demo' ? 'demo' : ($credits === null ? '—' : n($credits)) ?></b></span></li>
    <?php if ($R): ?><li class="d-none d-lg-block"><button type="button" id="btnPrint" class="btn btn-light btn-icon btn-sm rounded-circle" aria-label="Print this page" title="Print this page"><i class="ti ti-printer fs-5"></i></button></li><?php endif; ?>
  </ul>
</nav>

<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar">
  <div class="logo-area">
    <a href="<?= h(page_url('index.php')) ?>" class="d-inline-flex align-items-center text-reset text-decoration-none"><span class="icon-shape icon-xs bg-primary text-white rounded-2"><i class="ti ti-chart-dots"></i></span><span class="logo-text ms-2 fw-semibold">SEO · AEO · Perf</span></a>
  </div>
  <ul class="nav flex-column">
    <li class="px-4 py-2"><small class="nav-text text-secondary">Main</small></li>
    <li><a class="nav-link<?= $PAGE === 'overview' ? ' active' : '' ?>" href="<?= h(page_url('index.php')) ?>"><i class="ti ti-layout-dashboard"></i><span class="nav-text">Overview &amp; new check</span></a></li>
    <li class="px-4 pt-4 pb-2"><small class="nav-text text-secondary">Reports</small></li>
    <?php foreach ($NAV as $key => [$file, $icon, $label, $subs]): $on = $PAGE === $key; ?>
    <li class="<?= $on ? 'open' : '' ?>"><a class="nav-link<?= $on ? ' active' : '' ?>" href="<?= h(page_url($file)) ?>"><i class="ti <?= $icon ?>"></i><span class="nav-text"><?= $label ?></span></a>
      <?php if ($on && $R): ?><ul class="sub"><?php foreach ($subs as $anc => $txt): ?><li><a href="#<?= $anc ?>"><?= h($txt) ?><?php if (!empty($subAnchorCount[$anc])): ?> <span class="badge rounded-pill bg-danger ms-1"><?= $subAnchorCount[$anc] ?></span><?php endif; ?></a></li><?php endforeach; ?></ul><?php endif; ?>
    </li>
    <?php endforeach; ?>
    <li class="px-4 pt-4 pb-2"><small class="nav-text text-secondary">Export</small></li>
    <li><a class="nav-link<?= $PAGE === 'report' ? ' active' : '' ?>" href="<?= h(page_url('report.php')) ?>"><i class="ti ti-file-text"></i><span class="nav-text">Full report (1 → 3)</span></a></li>
  </ul>
</aside>

<!-- MAIN CONTENT -->
<main id="content" class="content py-10">
<div class="container-fluid">
