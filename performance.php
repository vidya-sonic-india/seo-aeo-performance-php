<?php
/** 3 · Performance Report — speed grade, load timeline, page weight, heaviest files, performance fixes */
define('APP', 1);
require __DIR__ . '/app/core.php';
require __DIR__ . '/app/view.php';
$PAGE = 'performance'; $PAGE_TITLE = '3 · Performance Report';
require __DIR__ . '/app/header.php';
?>
<?= $R ? report_context($T, $R) : '' ?>
<?= $R ? '' : no_check_state() ?>
<?php require __DIR__ . '/app/sections/performance.php'; ?>
<?php require __DIR__ . '/app/footer.php'; ?>
