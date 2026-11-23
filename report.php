<?php
/** Full report — SEO → AEO → Performance on one printable page */
define('APP', 1);
require __DIR__ . '/app/core.php';
require __DIR__ . '/app/view.php';
$PAGE = 'report'; $PAGE_TITLE = 'Full report';
require __DIR__ . '/app/header.php';
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
  <div><h1 class="fs-3 mb-1">Full report <span class="text-secondary fs-5">1 → 3</span></h1><?= $R ? report_context($T, $R) : '' ?></div>
  <?php if ($R): ?><button type="button" class="btn btn-primary btn-sm print-btn"><i class="ti ti-printer me-1"></i>Print / save as PDF</button><?php endif; ?>
</div>
<?php if (!$R): echo no_check_state(); else: ?>
<div class="full-report">
  <?php require __DIR__ . '/app/sections/seo.php'; ?>
  <hr class="report-break">
  <?php require __DIR__ . '/app/sections/aeo.php'; ?>
  <hr class="report-break">
  <?php require __DIR__ . '/app/sections/performance.php'; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/app/footer.php'; ?>
