<?php
defined('APP') || exit;
/** Shared footer: previous / next page in the report sequence, credits line, scripts. */
$SEQ = ['overview' => ['index.php', 'Overview'], 'seo' => ['seo.php', '1 · SEO Report'], 'aeo' => ['aeo.php', '2 · AEO Report'], 'performance' => ['performance.php', '3 · Performance Report'], 'report' => ['report.php', 'Full report']];
$keys = array_keys($SEQ); $i = array_search($PAGE, $keys, true);
$prev = $i > 0 ? $SEQ[$keys[$i - 1]] : null; $next = $i < count($keys) - 1 ? $SEQ[$keys[$i + 1]] : null;
?>
<?php if ($R): ?>
  <nav class="d-flex justify-content-between gap-2 border-top pt-4 mt-6 page-seq" aria-label="Report sequence">
    <?php if ($prev): ?><a class="btn btn-outline-secondary" href="<?= h(page_url($prev[0])) ?>"><i class="ti ti-arrow-left me-1"></i><?= h($prev[1]) ?></a><?php else: ?><span></span><?php endif; ?>
    <?php if ($next): ?><a class="btn btn-primary" href="<?= h(page_url($next[0])) ?>"><?= h($next[1]) ?><i class="ti ti-arrow-right ms-1"></i></a><?php endif; ?>
  </nav>
<?php endif; ?>
  <p class="text-secondary small text-center mt-6 mb-0">SEO · AEO · Performance Report · data: VebAPI · results cached <?= CACHE_TTL / 3600 ?> h to save credits · UI: InApp Bootstrap template</p>
</div>
</main>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@5.3.6/dist/apexcharts.min.js"></script>
<script src="assets/app.js?v=2"></script>
</body>
</html>
