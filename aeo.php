<?php
/** 2 · AEO Report — AI readiness, discovery files, trust pages, AI crawler access, AEO fixes */
define('APP', 1);
require __DIR__ . '/app/core.php';
require __DIR__ . '/app/view.php';
$PAGE = 'aeo'; $PAGE_TITLE = '2 · AEO Report';
require __DIR__ . '/app/header.php';
?>
<?= $R ? report_context($T, $R) : '' ?>
<?= $R ? '' : no_check_state() ?>
<?php require __DIR__ . '/app/sections/aeo.php'; ?>
<?php require __DIR__ . '/app/footer.php'; ?>
