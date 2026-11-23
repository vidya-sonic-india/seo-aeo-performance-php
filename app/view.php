<?php
defined('APP') || exit;
// View helpers — InApp (Bootstrap 5.3.8) markup
function section_error(?array $r): string {
    if (!$r || $r['ok']) return '';
    return '<div class="alert alert-danger d-flex gap-2 align-items-start" role="alert"><i class="ti ti-alert-circle fs-5"></i><div><b>API error:</b> ' . h($r['error']) . '</div></div>';
}
/** Inapp card with header title */
function card_open(string $title, string $extra = '', string $cls = 'h-100'): string {
    return '<div class="card ' . $cls . '"><div class="card-header bg-transparent d-flex justify-content-between align-items-center gap-2 flex-wrap px-4 py-3"><h3 class="h5 mb-0">' . $title . '</h3>' . $extra . '</div><div class="card-body p-4">';
}
function card_close(): string { return '</div></div>'; }

/** Radial gauge — ApexCharts radialBar, with a CSS fallback if the chart library cannot load */
function gauge($v, string $sub, string $color = '#E66239'): string {
    $p = is_numeric($v) ? max(0, min(100, (float)$v)) : 0;
    return '<div class="apx-radial" data-value="' . $p . '" data-color="' . $color . '" data-sub="' . h($sub) . '"><div class="gauge" style="--v:' . $p . ';--c:' . $color . '"><div><b>' . n($v) . '</b><small>' . h($sub) . '</small></div></div></div>';
}
/** Donut — ApexCharts donut, CSS fallback, legend always shown */
function donut(float $a, float $b, string $la, string $lb): string {
    $t = $a + $b; $pa = $t ? round($a / $t * 100, 1) : 0; $pb = $t ? round(100 - $pa, 1) : 0;
    return '<div class="donut-wrap"><div class="apx-donut" data-series="' . h(json_encode([$a, $b])) . '" data-labels="' . h(json_encode([$la, $lb])) . '"><div class="donut" style="--p:' . $pa . '"><span>' . $pa . '%</span></div></div>
      <ul class="list-unstyled legend mb-0"><li><i class="c1"></i>' . h($la) . ' <b>' . n($a) . '</b> <span class="text-secondary">(' . $pa . '%)</span></li>
      <li><i class="c2"></i>' . h($lb) . ' <b>' . n($b) . '</b> <span class="text-secondary">(' . $pb . '%)</span></li></ul></div>';
}
function score_color($s): string { return !is_numeric($s) ? '#a3a3a3' : ($s >= 80 ? '#00C951' : ($s >= 50 ? '#E66239' : '#FB2C36')); }
function sev_cls($s): string { $s = strtolower((string)$s); return in_array($s, ['high', 'critical', 'error']) ? 'danger' : (in_array($s, ['medium', 'warning']) ? 'warning' : 'info'); }
function spam_cls($s): string { return !is_numeric($s) ? 'secondary' : ($s >= 60 ? 'danger' : ($s >= 30 ? 'warning' : 'success')); }
function p_cls(string $p): string { return $p === 'High' ? 'danger' : ($p === 'Medium' ? 'warning' : 'info'); }
function grade_of($s): string { return !is_numeric($s) ? '–' : ($s >= 90 ? 'A' : ($s >= 80 ? 'B' : ($s >= 70 ? 'C' : ($s >= 60 ? 'D' : 'F')))); }
function signal(string $label, string $badgeHtml): string { return '<li class="list-group-item d-flex justify-content-between align-items-center gap-3 px-0"><span>' . $label . '</span>' . $badgeHtml . '</li>'; }
function table_open(string $id, array $heads, bool $scroll = true): string {
    $th = '';
    foreach ($heads as $hd) { $end = str_starts_with($hd, '>'); $th .= '<th' . ($end ? ' class="text-end"' : '') . '>' . h(ltrim($hd, '>')) . '</th>'; }
    return '<div class="table-responsive' . ($scroll ? ' scroll-box' : '') . '"><table class="table mb-0 text-nowrap table-hover"' . ($id ? ' id="' . $id . '"' : '') . '><thead class="table-light border-light"><tr>' . $th . '</tr></thead><tbody>';
}
function table_close(): string { return '</tbody></table></div>'; }

function render_actions(array $L): string {
    if (!$L) return '<div class="card"><div class="card-body p-4 text-secondary"><i class="ti ti-circle-check text-success me-2"></i>No fixes needed in this area.</div></div>';
    $o = '';
    foreach ($L as $a) {
        $o .= '<div class="card act mb-3" data-act="' . h($a['id']) . '"><div class="card-body p-4 d-flex gap-3">'
            . '<input class="form-check-input flex-shrink-0" type="checkbox" id="c-' . h($a['id']) . '" data-id="' . h($a['id']) . '" aria-label="Mark done">'
            . '<div class="flex-grow-1 min-w-0"><label for="c-' . h($a['id']) . '" class="d-block mb-2"><h3 class="h6 mb-0 fw-semibold">' . h($a['t']) . '</h3></label>'
            . '<div class="d-flex flex-wrap gap-2 mb-2">' . badge(p_cls($a['p']), $a['p'] . ' priority') . badge('info', $a['area']) . badge('secondary', 'Effort ' . $a['effort']) . '</div>'
            . '<p class="mb-0 text-secondary">' . h($a['why']) . '</p>'
            . ($a['code'] ? '<pre class="code">' . h($a['code']) . '</pre><button type="button" class="btn btn-light btn-sm copy-btn"><i class="ti ti-copy me-1"></i><span>Copy code</span></button>' : '')
            . '</div></div></div>';
    }
    return $o;
}
function pill(string $href, string $txt): string { return '<a class="btn btn-sm btn-outline-secondary" href="#' . $href . '">' . $txt . '</a>'; }

/** Empty state shown on report pages when no check has been run yet */
function no_check_state(): string {
    return '<div class="card"><div class="card-body empty-hero"><span class="icon-shape icon-xl bg-primary bg-opacity-10 text-primary rounded-circle mb-3"><i class="ti ti-world-search fs-1"></i></span>'
        . '<h2 class="h4 mb-2">No website checked yet</h2><p class="text-secondary mb-3">Run a check on the Overview page first. The SEO, AEO and Performance pages then read the same cached results.</p>'
        . '<a class="btn btn-primary" href="index.php"><i class="ti ti-search me-1"></i>Run a check</a></div></div>';
}
/** Small context line at the top of each report page */
function report_context(array $T, array $R): string {
    $c = count(array_filter($R, fn($x) => $x['cached'] ?? false));
    return '<p class="text-secondary small mb-0"><i class="ti ti-world me-1"></i>' . h($T['domain']) . ' · ' . h(date('d M Y H:i')) . ($c ? ' · ' . $c . '/' . count($R) . ' results from cache' : '') . ' · <a class="link-primary" href="' . h(page_url('index.php')) . '">change site</a></p>';
}
