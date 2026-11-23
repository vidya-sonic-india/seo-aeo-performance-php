<?php
/** 1 · SEO Report — on-page, authority, organic, backlinks, keywords, branded search */
define('APP', 1);
require __DIR__ . '/app/core.php';
require __DIR__ . '/app/view.php';
$PAGE = 'seo'; $PAGE_TITLE = '1 · SEO Report';
require __DIR__ . '/app/header.php';
?>
<?= $R ? report_context($T, $R) : '' ?>
<?= $R ? '' : no_check_state() ?>
<?php require __DIR__ . '/app/sections/seo.php'; ?>
<?php require __DIR__ . '/app/footer.php'; ?>
