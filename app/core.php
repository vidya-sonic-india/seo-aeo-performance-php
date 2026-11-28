<?php
/**
 * SEO · AEO · Performance Report — multi-page PHP app powered by VebAPI (https://vebapi.com)
 * Pages: index.php (overview + run check) → seo.php → aeo.php → performance.php → report.php (full, printable)
 * This file (app/core.php) holds config, API client, demo data and analysis; every page includes it.
 * UI: InApp Inventory Dashboard template (Codescandy, MIT) — Bootstrap 5.3.8, Tabler icons, ApexCharts.
 *
 * Report order: 1. SEO Report  →  2. AEO Report  →  3. Performance Report
 *
 * Setup:
 *   1. PHP 8.0+ with the curl extension.
 *   2. Put your key in config.php (same folder), or export VEBAPI_KEY=xxxx.
 *      Use the key "demo" to preview the UI with sample data (no credits used).
 *   3. Make sure ./cache is writable (results are cached to save credits).
 *   4. Run locally:  php -S localhost:8000   then open http://localhost:8000
 *
 * VebAPI endpoints used per check:
 *   SEO REPORT
 *     /seo/page-audit/v2              On-page analysis
 *     /backlinks/live                 Backlinks + follow/nofollow counts   (10 credits / 100 rows)
 *     /backlinks/spam                 Spam backlinks                       (10 credits / 100 rows)
 *     /backlinks/referraldomains      Referring domains (authority input)  (10 credits / 100 rows)
 *     /seo/topsearchkeywords          Organic keywords                     (10 credits / 100 rows)
 *     /seo/domainnamedata/v2          Domain age (authority input)
 *     /seo/keywordresearch            Related keywords for a seed keyword (optional)
 *   AEO REPORT
 *     /seo/ai-visibility-checker/v2   AI Visibility Audit (score, llms.txt, schema, sitemaps, trust pages)
 *     /seo/aiseochecker               AI SEO crawler check (robots.txt access for ~23 AI bots)
 *   PERFORMANCE REPORT
 *     /seo/loadingspeeddata/v2        Website speed check (timings, weight, third-party domains)
 */

declare(strict_types=1);
defined('APP') || exit;
const ROOT_DIR = __DIR__ . '/..';

// ───────────────────────────── CONFIG ─────────────────────────────
const VEBAPI_BASE          = 'https://vebapi.com/api';
const VEBAPI_KEY_FALLBACK  = 'PUT_YOUR_VEBAPI_KEY_HERE';
const CACHE_DIR            = ROOT_DIR . '/cache';
const CACHE_TTL            = 86400;   // 24 h — repeat checks of the same site cost 0 credits
const HTTP_TIMEOUT         = 60;      // seconds per API call
const BACKLINK_LIMIT       = 100;     // rows of backlinks to list
const SPAM_LIMIT           = 100;     // rows of spam backlinks to list
const REFDOMAIN_LIMIT      = 100;     // referring domains sampled for authority score
const KEYWORD_LIMIT        = 500;     // organic keywords pulled (biggest credit driver)
const SPAM_MIN_SCORE       = 30;      // 1–100; backlinks at/above this are "spam"
const DEFAULT_COUNTRY      = 'in';    // country for related-keyword research (ISO alpha-2)

// Key lookup order: config.php → Apache SetEnv / $_SERVER → environment → fallback constant
$API_KEY = '';
if (is_file(ROOT_DIR . '/config.php')) {
    $cfg = include ROOT_DIR . '/config.php';           // config.php: <?php return ['VEBAPI_KEY' => 'xxxx'];
    $API_KEY = is_array($cfg) ? trim((string)($cfg['VEBAPI_KEY'] ?? '')) : '';
    if (str_starts_with($API_KEY, 'PASTE_')) $API_KEY = '';
}
$API_KEY = $API_KEY ?: trim((string)($_SERVER['VEBAPI_KEY'] ?? $_ENV['VEBAPI_KEY'] ?? getenv('VEBAPI_KEY') ?: '')) ?: VEBAPI_KEY_FALLBACK;
$DEMO    = strtolower($API_KEY) === 'demo';

// ─────────────────────── OPTIONAL TEAM AUTH ───────────────────────
$AUTH_USER_EXPECTED = trim((string)($_SERVER['AUTH_USER'] ?? $_ENV['AUTH_USER'] ?? getenv('AUTH_USER') ?: ''));
$AUTH_PASS_EXPECTED = trim((string)($_SERVER['AUTH_PASSWORD'] ?? $_ENV['AUTH_PASSWORD'] ?? getenv('AUTH_PASSWORD') ?: ''));
if ($AUTH_USER_EXPECTED !== '' && $AUTH_PASS_EXPECTED !== '') {
    $inUser = (string)($_SERVER['PHP_AUTH_USER'] ?? '');
    $inPass = (string)($_SERVER['PHP_AUTH_PW'] ?? '');
    if ($inUser === '' && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        if (str_starts_with(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'basic ')) {
            $decoded = (string)base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6));
            if (str_contains($decoded, ':')) {
                [$inUser, $inPass] = explode(':', $decoded, 2);
            }
        }
    }
    if ($inUser !== $AUTH_USER_EXPECTED || $inPass !== $AUTH_PASS_EXPECTED) {
        header('WWW-Authenticate: Basic realm="Restricted Access - Internal Team Only"');
        header('HTTP/1.0 401 Unauthorized');
        echo '<!DOCTYPE html><html><head><title>401 Unauthorized</title><style>body{font-family:sans-serif;padding:50px;text-align:center;}</style></head><body><h2>Restricted Access</h2><p>Please enter valid team credentials to access this tool.</p></body></html>';
        exit;
    }
}

// ──────────────────────────── HELPERS ─────────────────────────────
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

/** Safe nested getter: g($arr, 'a.b.c', default) */
function g($arr, string $path, $default = null) {
    foreach (explode('.', $path) as $k) {
        if (!is_array($arr) || !array_key_exists($k, $arr)) return $default;
        $arr = $arr[$k];
    }
    return $arr ?? $default;
}

function n($v, int $dec = 0): string { return is_numeric($v) ? number_format((float)$v, $dec) : '—'; }

/** Inapp subtle bordered badge: bg-{c}-subtle text-{c} border border-{c} */
function badge(string $c, $text): string {
    $t = $c === 'warning' ? 'warning-emphasis' : $c;
    return '<span class="badge bg-' . $c . '-subtle text-' . $t . ' border border-' . $c . '">' . h($text) . '</span>';
}

function yesno($v, string $y = 'Yes', string $no = 'No'): string {
    if ($v === null || $v === '') return badge('secondary', 'n/a');
    return $v ? badge('success', $y) : badge('danger', $no);
}

/** Horizontal progress row: label · bar · value */
function bar_row(string $label, float $val, float $max, string $shown, string $color = 'primary'): string {
    $w = $max > 0 ? max(0, min(100, $val / $max * 100)) : 0;
    return '<div class="bar-row"><span class="bar-lab" title="' . h($label) . '">' . h($label) . '</span><div class="progress flex-grow-1" role="progressbar" aria-label="' . h($label) . '" aria-valuenow="' . round($w) . '" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-' . $color . '" style="width:' . round($w, 1) . '%"></div></div><span class="bar-val">' . $shown . '</span></div>';
}

/** Inapp tinted KPI card: icon tile + label + value + sub-line */
function kpi(string $icon, string $tone, string $label, string $value, string $sub = ''): string {
    $st = $tone === 'warning' ? 'warning-emphasis' : $tone;
    return '<div class="col-sm-6 col-xl-3"><div class="card p-4 h-100 bg-' . $tone . ' bg-opacity-10 border border-' . $tone . ' border-opacity-25 rounded-2"><div class="d-flex gap-3"><div class="icon-shape icon-md bg-' . $tone . ' text-white rounded-2 flex-shrink-0"><i class="ti ti-' . $icon . ' fs-4"></i></div><div class="min-w-0"><h2 class="mb-2 fs-6">' . h($label) . '</h2><h3 class="fw-bold mb-0 fs-4 text-nowrap">' . $value . '</h3>' . ($sub !== '' ? '<p class="text-' . $st . ' mb-0 small">' . $sub . '</p>' : '') . '</div></div></div></div>';
}

/** Numbered section heading inside a report */
function sec_head(string $id, string $num, string $title, string $sub = ''): string {
    return '<div class="sec-head d-flex align-items-center gap-3" id="' . $id . '"><span class="icon-shape icon-sm rounded-2 bg-primary text-white fw-semibold small flex-shrink-0">' . $num . '</span><div><h2 class="h5 mb-0">' . h($title) . '</h2>' . ($sub ? '<p class="text-secondary small mb-0">' . $sub . '</p>' : '') . '</div></div>';
}

/** Normalise user input into a full URL, a bare domain and a default brand term. */
function normalize_target(string $raw): ?array {
    $raw = trim($raw);
    if ($raw === '') return null;
    if (!preg_match('~^https?://~i', $raw)) $raw = 'https://' . $raw;
    if (!filter_var($raw, FILTER_VALIDATE_URL)) return null;
    $p = parse_url($raw);
    $host = strtolower($p['host'] ?? '');
    if ($host === '' || !str_contains($host, '.')) return null;
    $domain = preg_replace('~^www\.~', '', $host);
    $parts  = explode('.', $domain);
    // crude SLD pick: handles example.com, example.co.in, shop.example.com
    $sld = count($parts) >= 3 && strlen($parts[count($parts) - 2]) <= 3
        ? $parts[count($parts) - 3] : $parts[count($parts) - 2];
    return ['url' => $raw, 'domain' => $domain, 'brand' => $sld];
}

/** Lowercase and strip spaces/punctuation so "Sonic Lamb" matches "soniclamb". */
function squash(string $s): string { return preg_replace('~[^a-z0-9]~', '', strtolower($s)); }

function host_of(string $u): string {
    $h = parse_url(preg_match('~^https?://~i', $u) ? $u : 'https://' . $u, PHP_URL_HOST) ?: $u;
    return preg_replace('~^www\.~', '', strtolower((string)$h));
}

// ─────────────────────────── API CLIENT ───────────────────────────
/**
 * Fire several VebAPI GET requests in parallel (curl_multi) with file caching.
 * @param array $reqs  name => [path, params]
 * @return array       name => ['ok'=>bool, 'status'=>int, 'data'=>array|null, 'error'=>string|null, 'cached'=>bool]
 */
function veb_multi(array $reqs, string $key): array {
    if (!is_dir(CACHE_DIR)) @mkdir(CACHE_DIR, 0775, true);
    // keep cached API responses out of public reach on Apache
    if (is_dir(CACHE_DIR) && !is_file(CACHE_DIR . '/.htaccess')) @file_put_contents(CACHE_DIR . '/.htaccess', "Require all denied\nDeny from all\n");
    $out = []; $handles = []; $mh = curl_multi_init();

    foreach ($reqs as $name => [$path, $params]) {
        $url = VEBAPI_BASE . $path . '?' . http_build_query($params);
        $cacheFile = CACHE_DIR . '/' . md5($url) . '.json';
        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_TTL) {
            $data = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($data)) { $out[$name] = ['ok' => true, 'status' => 200, 'data' => $data, 'error' => null, 'cached' => true]; continue; }
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => HTTP_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER     => ['X-API-KEY: ' . $key, 'Accept: application/json', 'Content-Type: application/json'],
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$name] = [$ch, $cacheFile];
    }

    do { $st = curl_multi_exec($mh, $running); if ($running) curl_multi_select($mh, 1.0); }
    while ($running && $st === CURLM_OK);

    $transferErr = [];
    while ($msg = curl_multi_info_read($mh)) {
        if ($msg['result'] !== CURLE_OK) $transferErr[spl_object_id($msg["handle"])] = curl_strerror($msg['result']);
    }

    foreach ($handles as $name => [$ch, $cacheFile]) {
        $body   = curl_multi_getcontent($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr   = curl_error($ch) ?: ($transferErr[spl_object_id($ch)] ?? '');
        if ($status === 0 && $cerr) $cerr = 'Could not reach VebAPI: ' . $cerr;
        $data   = json_decode((string)$body, true);
        $ok     = $status >= 200 && $status < 300 && is_array($data);
        $err    = null;
        if (!$ok) {
            $err = $cerr ?: (is_array($data) ? ($data['message'] ?? $data['error'] ?? json_encode($data)) : "HTTP $status");
            $err = (is_string($err) ? $err : json_encode($err)) . ($status ? " (HTTP $status)" : '');
        } else {
            @file_put_contents($cacheFile, $body);
        }
        $out[$name] = ['ok' => $ok, 'status' => $status, 'data' => $ok ? $data : null, 'error' => $err, 'cached' => false];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}

// ─────────────────────────── DEMO DATA ────────────────────────────
// Real VebAPI sample payloads (scan of vebapi.com) re-pointed at the demo domain.
const DEMO_AEO = <<<'JSON'
{"api":{"name":"AI Visibility Audit API","provider":"vebapi.com","engine":"ai-ready-max","version":"1","mode":"scan"},"url":"https://vebapi.com","fetch":{"status_code":200,"final_url":"https://vebapi.com/","content_type":"text/html; charset=UTF-8","redirect_chain":[],"server":"cloudflare","x_robots_tag":null,"runtime_ms":1759,"dom_load_ok":true},"ai_discovery":{"robots_txt":{"exists":true,"status_code":200,"size_bytes":24,"sitemaps_found":[],"ai_specific_agents_listed":[],"parsed":{"groups_count":1,"crawl_delay_any":false},"url":"https://vebapi.com/robots.txt"},"llms_txt":{"url":"https://vebapi.com/llms.txt","exists":true,"status_code":200,"size_bytes":3480,"lines":86,"summary":"# llms.txt | # Purpose: Help LLMs understand what this site is, where the docs live, and what to ignore. | # Last updated: 2026-02-06"},"sitemap_intel":{"discovered":["https://vebapi.com/sitemap.xml","https://vebapi.com/sitemap_index.xml","https://vebapi.com/sitemap-index.xml"],"checked":[{"url":"https://vebapi.com/sitemap.xml","status_code":404,"content_type":"text/html","size_bytes":146,"ok":false},{"url":"https://vebapi.com/sitemap_index.xml","status_code":404,"content_type":"text/html","size_bytes":146,"ok":false},{"url":"https://vebapi.com/sitemap-index.xml","status_code":404,"content_type":"text/html","size_bytes":146,"ok":false}],"parsed":[],"total_url_count_estimate":0,"has_lastmod":false,"parsed_sitemaps_count":0}},"ai_visibility":{"indexable":true,"block_reasons":[],"robots_meta":null,"canonical":null,"lang":"en"},"structure":{"title":"Demo Store – Official Site","meta_description":"Complete product range in one place. Fast delivery, easy returns and support. Simple pricing.","headings":{"h1":1,"h2":4,"h3":41,"h4":0,"h5":0,"h6":0},"main_selector_used":"//body"},"content":{"main_word_count":1029,"reading_time_minutes":6,"text_density_percent":10.93,"script_count":8,"js_heavy":false},"links":{"total":68,"internal":66,"external":1,"nofollow":0,"sponsored":0,"ugc":0},"schema":{"jsonld_count":0,"jsonld_parse_errors":0,"types_found":[]},"trust_signals":{"pages_found":{"privacy":true,"terms":true,"refund":false,"contact":true,"about":false,"pricing":true},"page_urls":{"privacy":"https://vebapi.com/privacy","terms":"https://vebapi.com/terms","refund":null,"contact":"https://vebapi.com/contact","about":null,"pricing":"https://vebapi.com/#pricing"}},"ai_simulation":{"indexable":true,"block_reasons":[],"ai_bots":{"chatgpt":{"bot":"GPTBot","allowed_by_robots":true},"google_ai_overviews":{"bot":"Google-Extended","allowed_by_robots":true},"anthropic":{"bot":"anthropic-ai","allowed_by_robots":true},"perplexity":{"bot":"CCBot","allowed_by_robots":true},"bytespider":{"bot":"Bytespider","allowed_by_robots":true}}},"ai_score":{"crawlability":16,"structure":16,"content_depth":20,"semantic_markup":12,"technical_cleanliness":14,"total":78,"grade":"C"},"ai_scrapable":true,"issues":[{"id":"missing_canonical","severity":"medium","evidence":"Canonical link tag not found","impact":"Deduping + consistent indexing"},{"id":"low_text_density","severity":"medium","evidence":"Text density low: 10.93%","impact":"AI scrapability"}],"suggestions":[{"priority":"medium","category":"Discovery","message":"Add `Sitemap:` lines in robots.txt.","impact":"Faster discovery for search + AI crawlers."},{"priority":"medium","category":"Structure","message":"Add a canonical URL tag.","impact":"Avoid duplicate indexing and inconsistent AI citations."},{"priority":"medium","category":"Semantic Markup","message":"Add JSON-LD schema (Organization, WebSite, Article/Product where relevant).","impact":"Better entity understanding + rich results."},{"priority":"medium","category":"Technical","message":"Reduce boilerplate or increase meaningful text in main content.","impact":"AI extraction improves when content dominates the page."}]}
JSON;
const DEMO_SPEED = <<<'JSON'
{"api":{"name":"website speed check","provider":"vebapi.com","engine":"v3-domain-master","server":"EU-4","version":"3"},"summary":{"url":"https://vebapi.com/","performance_grade":{"score":97,"grade":"A"},"page_size_bytes":532033,"page_size_kb":519.6,"load_time_ms":534,"ttfb_ms":532,"requests":29,"unique_domains":6,"main":{"http_code":200,"content_type":"text/html; charset=UTF-8","header_size":1581,"html_size_bytes":98218,"redirect_count":0,"timings":{"namelookup_time":0.008848,"connect_time":0.013405,"appconnect_time":null,"pretransfer_time":0.037235,"starttransfer_time":0.531967,"total_time":0.534037,"redirect_time":0}}},"improve_page_performance":[{"grade":"C","suggestion":"Add Expires headers / Cache-Control","detail":"3 asset(s) missing strong caching"}],"response_codes":[{"response_code":200,"responses":29}],"content_size_by_content_type":[{"content_type":"Script","percent":52.31,"size_kb":271.8},{"content_type":"Image","percent":29.18,"size_kb":151.6},{"content_type":"HTML","percent":18.46,"size_kb":95.9},{"content_type":"CSS","percent":0.05,"size_kb":0.2},{"content_type":"Other","percent":0,"size_kb":0}],"requests_by_content_type":[{"content_type":"Image","percent":79.31,"requests":23},{"content_type":"Script","percent":10.34,"requests":3},{"content_type":"CSS","percent":3.45,"requests":1},{"content_type":"Other","percent":3.45,"requests":1},{"content_type":"HTML","percent":3.45,"requests":1}],"content_size_by_domain":[{"domain":"vebapi.com","percent":47.64,"size_kb":247.5},{"domain":"www.googletagmanager.com","percent":28.06,"size_kb":145.8},{"domain":"cdn.tailwindcss.com","percent":23.75,"size_kb":123.4},{"domain":"analytics.ahrefs.com","percent":0.51,"size_kb":2.6},{"domain":"fonts.googleapis.com","percent":0.05,"size_kb":0.2},{"domain":"www.facebook.com","percent":0,"size_kb":0}],"file_requests":[{"url":"https://www.googletagmanager.com/gtag/js?id=G-XXXXXXX","domain":"www.googletagmanager.com","status":200,"type":"Script","size_bytes":149270,"timings":{"total_time":0.321054,"redirect_time":0}},{"url":"https://cdn.tailwindcss.com/3.4.17","domain":"cdn.tailwindcss.com","status":200,"type":"Script","size_bytes":126350,"timings":{"total_time":0.321787,"redirect_time":0.301471}},{"url":"https://vebapi.com/","domain":"vebapi.com","status":200,"type":"HTML","size_bytes":98218,"timings":{"total_time":0.534037,"redirect_time":0}},{"url":"https://www.facebook.com/tr?id=0000&ev=PageView&noscript=1","domain":"www.facebook.com","status":200,"type":"Other","size_bytes":0,"timings":{"total_time":0.310867,"redirect_time":0}}]}
JSON;
const DEMO_CRAWLER = <<<'JSON'
{"url":"vebapi.com","robots_found":true,"ai_access":{"GPTBot":true,"ChatGPT-User":true,"Google-Extended":true,"AnthropicBot":true,"ClaudeBot":true,"PerplexityBot":true,"CCBot":true,"Amazonbot":true,"Bytespider":true,"facebookexternalhit":true,"cohere-ai":true,"YouBot":true,"NeevaBot":true,"ai-crawler":true,"Applebot":true,"Baiduspider":true,"Sogou":true,"YandexBot":true,"PhindBot":true,"DuckDuckBot":true,"Yeti":true,"360Spider":true,"ias-va":true},"ai_bots_allowed":true,"suggestions":["Your site is currently open for AI bots. You're AI-friendly!","You can still improve by providing structured data (schema.org) for better AI comprehension.","Add clear content usage terms if you want to allow or limit AI training use."]}
JSON;

function demo_results(array $t, string $seed): array {
    mt_srand(crc32($t['domain']));
    $d = $t['domain']; $b = $t['brand'];
    $bl = [];
    $srcs = ['techblog.io','newsportal.com','forum.example.org','reviewsite.net','dir-links.xyz','casino-seo.top','partnerco.com','wikihow.com','medium.com','reddit.com'];
    for ($i = 0; $i < 40; $i++) {
        $s = $srcs[$i % count($srcs)]; $spam = str_contains($s, 'xyz') || str_contains($s, 'top') ? mt_rand(55, 95) : mt_rand(0, 25);
        $bl[] = ['url_from' => "https://$s/post-" . ($i + 1), 'url_to' => "https://$d/", 'domain_from' => $s, 'anchor' => $i % 3 ? $b : 'click here',
                 'nofollow' => (bool)($i % 4 === 0), 'link_type' => 'anchor', 'domain_inlink_rank' => mt_rand(50, 700),
                 'spam_score' => $spam, 'first_seen' => '2026-0' . mt_rand(1, 9) . '-1' . mt_rand(0, 9)];
    }
    $spamRows = array_values(array_filter($bl, fn($r) => $r['spam_score'] >= SPAM_MIN_SCORE));
    $doms = [];
    foreach (array_unique(array_column($bl, 'domain_from')) as $s)
        $doms[] = ['domain' => $s, 'domain_inlink_rank' => mt_rand(50, 700), 'backlinks' => mt_rand(1, 40), 'doFollow_pages' => mt_rand(0, 10), 'noFollow_pages' => mt_rand(0, 5), 'spam_score' => mt_rand(0, 60)];
    $kw = [];
    $words = ['headphones','wireless earbuds','best headphones india','noise cancelling','bluetooth headset','gaming headphones','studio monitor','bass headphones','headphone review','over ear'];
    for ($i = 0; $i < 337; $i++) {
        $isBrand = $i % 7 === 0;
        $kw[] = ['countryCode' => 'in', 'keyword' => $isBrand ? "$b " . $words[$i % 10] : $words[$i % 10] . ' ' . ($i + 1),
                 'topRankedUrl' => "https://$d/" . ($i % 5 ? 'products' : ''), 'rank' => mt_rand(1, 100), 'rankChange' => mt_rand(-5, 5),
                 'searchVolume' => mt_rand(20, 9000), 'rankingDifficulty' => mt_rand(1, 80), 'seoClicks' => mt_rand(0, 300)];
    }
    $rel = [];
    if ($seed !== '') {
        foreach (['', ' price', ' review', ' best', ' online', ' vs', ' near me', ' india', ' for gaming', ' wireless'] as $i => $suf)
            $rel[] = ['text' => $seed . $suf, 'cpc' => number_format(mt_rand(20, 900) / 100, 2), 'vol' => $v = mt_rand(90, 12000), 'v' => $v, 'competition' => ['Low', 'Medium', 'High'][mt_rand(0, 2)], 'score' => number_format(mt_rand(50, 300) / 100, 2)];
    }
    $repoint = function (string $json) use ($d, $b): array {
        $a = json_decode(str_replace(['https://vebapi.com', '"vebapi.com"'], ['https://' . $d, '"' . $d . '"'], $json), true);
        if (isset($a['api']['provider'])) $a['api']['provider'] = 'vebapi.com';
        if (isset($a['structure']['title'])) $a['structure']['title'] = ucfirst($b) . ' – Official Site';
        return $a;
    };
    $ok = fn($data) => ['ok' => true, 'status' => 200, 'data' => $data, 'error' => null, 'cached' => false];
    $R = [
        'audit' => $ok([
            'summary' => ['overall_score' => 72, 'grade' => 'C', 'priority_issues' => ['Missing meta description', '3 images without alt text', 'No HSTS header']],
            'scores'  => ['overall' => 72, 'buckets' => ['performance' => 68, 'technical' => 80, 'onpage' => 70, 'security' => 55, 'ai_readiness' => 75, 'accessibility' => 82]],
            'basic'   => ['requested_url' => $t['url'], 'final_url' => $t['url'], 'http_code' => 200, 'title' => ucfirst($b) . ' — Official Store', 'canonical' => $t['url']],
            'headings'=> ['counts' => ['h1' => 1, 'h2' => 6, 'h3' => 12, 'h4' => 0, 'h5' => 0, 'h6' => 0], 'data' => ['h1' => [ucfirst($b) . ' Headphones']], 'hierarchy' => ['ok' => true, 'issues' => []]],
            'links'   => ['counts' => ['total' => 84, 'internal' => 66, 'external' => 18, 'nofollow' => 4, 'empty_text' => 2], 'health_sample' => ['checked' => 20, 'broken_estimate' => 1]],
            'images'  => ['counts' => ['total' => 24, 'lazy' => 18], 'alt' => ['missing' => 3, 'coverage_pct' => 87.5]],
            'content' => ['word_count_estimate' => 860, 'content_to_html_ratio_pct' => 9.4],
            'performance' => ['ttfb_seconds' => 0.42, 'total_time_seconds' => 1.3, 'content_encoding' => 'br', 'scripts' => ['count' => 14, 'blocking_count' => 3], 'suggestions' => ['Defer 3 render-blocking scripts']],
            'security' => ['https' => true, 'hsts' => false, 'x_frame_options' => true, 'x_content_type_options' => true, 'content_security_policy' => false, 'mixed_content_found' => false],
            'structured_data' => ['jsonld_count' => 2, 'detected_types' => ['Organization', 'Product']],
            'crawl_signals' => ['robots' => ['found' => true], 'sitemap' => ['found' => true], 'llms_txt' => ['found' => false]],
            'findings' => [
                ['category' => 'onpage', 'severity' => 'high', 'issue' => 'Meta description missing', 'fix' => 'Add a 140–160 character meta description.'],
                ['category' => 'accessibility', 'severity' => 'medium', 'issue' => '3 images missing alt text', 'fix' => 'Add descriptive alt attributes.'],
                ['category' => 'security', 'severity' => 'medium', 'issue' => 'HSTS header not set', 'fix' => 'Send Strict-Transport-Security header.'],
                ['category' => 'performance', 'severity' => 'low', 'issue' => '3 render-blocking scripts', 'fix' => 'Use defer/async.'],
            ],
        ]),
        'backlinks' => $ok(['counts' => ['backlinks' => ['total' => 1843, 'returned' => count($bl), 'doFollow' => 1380], 'domains' => ['total' => 212, 'doFollow' => 158]], 'backlinks' => $bl]),
        'spam'      => $ok(['counts' => ['backlinks' => ['total' => 146, 'returned' => count($spamRows)], 'domains' => ['total' => 19]], 'backlinks' => $spamRows]),
        'refdomains'=> $ok(['counts' => ['domains' => ['total' => 212]], 'domains' => $doms]),
        'keywords'  => $ok(['keywords' => $kw]),
        'domain'    => $ok(['registration' => ['age_days' => 1460, 'created_at' => '2022-11-20']]),
        'aeo'       => $ok($repoint(DEMO_AEO)),
        'crawler'   => $ok($repoint(DEMO_CRAWLER)),
        'speed'     => $ok($repoint(DEMO_SPEED)),
    ];
    if ($seed !== '') $R['related'] = $ok($rel);
    return $R;
}

// ───────────────────────── ANALYSIS LOGIC ─────────────────────────
/**
 * Estimated Authority Score 0–100. VebAPI exposes no DA/DR endpoint, so this is a transparent proxy:
 *   60 pts  referring-domain volume (log scale, 100k RDs = max)
 *   25 pts  quality of top-20 referring domains (avg domain_inlink_rank, auto-scaled)
 *   10 pts  share of referring domains that pass dofollow
 *    5 pts  domain age (10 y = max)
 *   − up to 10 pts penalty for share of spam backlinks
 */
function authority_score(array $bl, array $rd, array $spam, array $dom): array {
    $rdTotal  = (int)(g($rd, 'counts.domains.total') ?? g($bl, 'counts.domains.total', 0));
    $rdDofol  = (int)g($bl, 'counts.domains.doFollow', 0);
    $blTotal  = (int)g($bl, 'counts.backlinks.total', 0);
    $spamTot  = (int)g($spam, 'counts.backlinks.total', 0);
    $ageDays  = (int)g($dom, 'registration.age_days', 0);

    $ranks = array_values(array_filter(array_map(fn($d) => $d['domain_inlink_rank'] ?? null, g($rd, 'domains', [])), 'is_numeric'));
    rsort($ranks);
    $top   = array_slice($ranks, 0, 20);
    $avg   = $top ? array_sum($top) / count($top) : 0;
    $scale = ($ranks && max($ranks) > 100) ? 1000 : 100;

    $p = [
        'Referring domains' => min(1, log10($rdTotal + 1) / log10(100001)) * 60,
        'Link quality'      => min(1, $avg / $scale) * 25,
        'Dofollow share'    => ($rdTotal ? min(1, $rdDofol / $rdTotal) : 0) * 10,
        'Domain age'        => min(1, $ageDays / 3650) * 5,
        'Spam penalty'      => -($blTotal ? min(1, $spamTot / $blTotal) : 0) * 10,
    ];
    $score = (int)round(max(0, min(100, array_sum($p))));
    return ['score' => $score, 'parts' => $p, 'rd' => $rdTotal, 'age_years' => $ageDays ? round($ageDays / 365, 1) : null];
}

function organic_summary(array $kw): array {
    $s = ['count' => count($kw), 'traffic' => 0, 'volume' => 0, 'avg_rank' => 0,
          'buckets' => ['1–3' => 0, '4–10' => 0, '11–20' => 0, '21–50' => 0, '51–100' => 0]];
    $rs = 0; $rc = 0;
    foreach ($kw as $k) {
        $s['traffic'] += (float)($k['seoClicks'] ?? 0);
        $s['volume']  += (float)($k['searchVolume'] ?? 0);
        $r = $k['rank'] ?? null;
        if (!is_numeric($r)) continue;
        $rs += $r; $rc++;
        $b = $r <= 3 ? '1–3' : ($r <= 10 ? '4–10' : ($r <= 20 ? '11–20' : ($r <= 50 ? '21–50' : '51–100')));
        $s['buckets'][$b]++;
    }
    $s['avg_rank'] = $rc ? $rs / $rc : null;
    return $s;
}

function split_branded(array $kw, array $terms): array {
    $terms = array_filter(array_map('squash', $terms));
    $out = ['branded' => [], 'non' => []];
    foreach ($kw as $k) {
        $q = squash((string)($k['keyword'] ?? ''));
        $hit = false;
        foreach ($terms as $t) if ($t !== '' && str_contains($q, $t)) { $hit = true; break; }
        $out[$hit ? 'branded' : 'non'][] = $k;
    }
    return $out;
}

/** Derived AEO signals from the AI Visibility Audit payload */
function aeo_facts(array $X, string $domain): array {
    $checked  = (array)g($X, 'ai_discovery.sitemap_intel.checked', []);
    $llmSum   = (string)g($X, 'ai_discovery.llms_txt.summary', '');
    $llmDate  = preg_match('~(\d{4}-\d{2}-\d{2})~', $llmSum, $m) ? $m[1] : null;
    return [
        'sitemapOk' => (bool)array_filter($checked, fn($c) => !empty($c['ok'])) || (bool)g($X, 'ai_discovery.robots_txt.sitemaps_found', []),
        'canonical' => g($X, 'ai_visibility.canonical'),
        'schemaN'   => (int)g($X, 'schema.jsonld_count', 0),
        'llmDate'   => $llmDate,
        'llmAge'    => $llmDate ? (int)floor((time() - strtotime($llmDate)) / 86400) : null,
        'trust'     => (array)g($X, 'trust_signals.pages_found', []),
        'headings'  => (array)g($X, 'structure.headings', []),
        'density'   => g($X, 'content.text_density_percent'),
        'agents'    => (array)g($X, 'ai_discovery.robots_txt.ai_specific_agents_listed', []),
        'score'     => (array)g($X, 'ai_score', []),
    ];
}

/** Speed-check derived numbers */
function speed_facts(array $S, string $domain): array {
    $T = (array)g($S, 'summary.main.timings', []);
    $ms = fn($x) => is_numeric($x) ? $x * 1000 : null;
    $phases = [];
    if (is_numeric($T['total_time'] ?? null)) {
        $phases = [
            'DNS lookup'    => $ms($T['namelookup_time'] ?? 0),
            'TCP connect'   => $ms(($T['connect_time'] ?? 0) - ($T['namelookup_time'] ?? 0)),
            'TLS + request' => $ms(($T['pretransfer_time'] ?? 0) - ($T['connect_time'] ?? 0)),
            'Server wait'   => $ms(($T['starttransfer_time'] ?? 0) - ($T['pretransfer_time'] ?? 0)),
            'Download'      => $ms(($T['total_time'] ?? 0) - ($T['starttransfer_time'] ?? 0)),
        ];
    }
    $doms = (array)g($S, 'content_size_by_domain', []);
    $tpKb = 0.0;
    foreach ($doms as $d) if (host_of((string)($d['domain'] ?? '')) !== $domain) $tpKb += (float)($d['size_kb'] ?? 0);
    $tw = null;
    foreach ((array)g($S, 'file_requests', []) as $f) if (str_contains((string)($f['url'] ?? ''), 'cdn.tailwindcss.com')) { $tw = $f; break; }
    return ['phases' => $phases, 'server' => $phases['Server wait'] ?? null, 'network' => $ms($T['pretransfer_time'] ?? null),
            'tpKb' => $tpKb, 'tailwind' => $tw];
}

/** Ranked fix list. Each: id, report (aeo|perf), p (High|Medium|Low), t, area, effort, why, code */
function build_actions(array $X, array $C, array $S, array $AF, array $SF, array $T): array {
    $d = $T['domain']; $L = [];
    $add = function (array $a) use (&$L) { $L[] = $a + ['code' => null]; };
    if ($X) {
        if (!$AF['sitemapOk']) $add(['id' => 'sitemap', 'report' => 'aeo', 'p' => 'High', 't' => 'Publish sitemap.xml and declare it in robots.txt', 'area' => 'Crawlability', 'effort' => 'S',
            'why' => 'No working sitemap was found and robots.txt lists no Sitemap line, so crawlers discover pages only through links.',
            'code' => "# robots.txt\nUser-agent: *\nAllow: /\n\nSitemap: https://$d/sitemap.xml"]);
        if (!$AF['canonical']) $add(['id' => 'canon', 'report' => 'aeo', 'p' => 'High', 't' => 'Add a canonical tag to every page', 'area' => 'Structure', 'effort' => 'S',
            'why' => 'No canonical found. AI answers may cite duplicate URL variants (trailing slash, #anchors, query strings).',
            'code' => "<link rel=\"canonical\" href=\"https://$d/\">"]);
        if (!$AF['schemaN']) $add(['id' => 'schema', 'report' => 'aeo', 'p' => 'High', 't' => 'Add JSON-LD: Organization and WebSite (plus Product where relevant)', 'area' => 'Semantic markup', 'effort' => 'M',
            'why' => 'Zero JSON-LD blocks. Semantic markup scores ' . n($AF['score']['semantic_markup'] ?? null) . '/20 in the AI audit.',
            'code' => "<script type=\"application/ld+json\">\n{\"@context\":\"https://schema.org\",\"@graph\":[\n {\"@type\":\"Organization\",\"@id\":\"https://$d/#org\",\"name\":\"" . ucfirst($T['brand']) . "\",\"url\":\"https://$d/\"},\n {\"@type\":\"WebSite\",\"url\":\"https://$d/\",\"name\":\"" . ucfirst($T['brand']) . "\",\"publisher\":{\"@id\":\"https://$d/#org\"}}\n]}\n</script>"]);
        if (is_numeric($AF['density']) && $AF['density'] < 15) $add(['id' => 'density', 'report' => 'aeo', 'p' => 'Medium', 't' => 'Raise text density above 15% and fix heading balance', 'area' => 'Content', 'effort' => 'M',
            'why' => 'Text density is ' . n($AF['density'], 2) . '%. Headings run H1 ' . n($AF['headings']['h1'] ?? 0) . ' / H2 ' . n($AF['headings']['h2'] ?? 0) . ' / H3 ' . n($AF['headings']['h3'] ?? 0) . '. Group content under more H2 sections and open each with a short answer-style paragraph.']);
        $miss = array_keys(array_filter($AF['trust'], fn($v) => !$v));
        if ($miss) $add(['id' => 'trust', 'report' => 'aeo', 'p' => 'Medium', 't' => 'Publish missing trust pages: ' . implode(', ', $miss), 'area' => 'Trust', 'effort' => 'S',
            'why' => 'Answer engines weigh entity clarity. An About page also gives Organization schema a page to point to.']);
        if ($AF['llmAge'] !== null && $AF['llmAge'] > 90) $add(['id' => 'llms', 'report' => 'aeo', 'p' => 'Low', 't' => 'Refresh llms.txt (last updated ' . $AF['llmDate'] . ')', 'area' => 'Discovery', 'effort' => 'S',
            'why' => 'The file is ' . $AF['llmAge'] . ' days old. Keep it in step with the site.']);
        if (!g($X, 'ai_discovery.llms_txt.exists')) $add(['id' => 'llmsnew', 'report' => 'aeo', 'p' => 'Medium', 't' => 'Create an llms.txt file', 'area' => 'Discovery', 'effort' => 'S',
            'why' => 'llms.txt tells LLM crawlers what the site is and where the key pages live.', 'code' => "# llms.txt — " . ucfirst($T['brand']) . " (https://$d)\n> One-line description of what the site offers.\n\n## Key pages\n- [Home](https://$d/)\n- [Products](https://$d/products)\n- [Contact](https://$d/contact)"]);
        if (!$AF['agents']) $add(['id' => 'aibots', 'report' => 'aeo', 'p' => 'Low', 't' => 'State AI crawler rules explicitly in robots.txt', 'area' => 'Discovery', 'effort' => 'S',
            'why' => 'AI bots are allowed only by default. Named groups make the policy a recorded decision.',
            'code' => "User-agent: GPTBot\nAllow: /\n\nUser-agent: OAI-SearchBot\nAllow: /\n\nUser-agent: ClaudeBot\nAllow: /\n\nUser-agent: PerplexityBot\nAllow: /"]);
    }
    if ($C) {
        $blocked = array_keys(array_filter((array)g($C, 'ai_access', []), fn($v) => !$v));
        if ($blocked) $add(['id' => 'blocked', 'report' => 'aeo', 'p' => 'High', 't' => 'Review blocked AI crawlers: ' . implode(', ', array_slice($blocked, 0, 6)) . (count($blocked) > 6 ? '…' : ''), 'area' => 'Crawlability', 'effort' => 'S',
            'why' => 'These agents are disallowed by robots.txt, so their answer engines cannot read or cite the site. Confirm this is intended.']);
    }
    if ($S) {
        if ($SF['server'] !== null && $SF['server'] > 300) $add(['id' => 'ttfb', 'report' => 'perf', 'p' => 'High', 't' => 'Cut server wait time (' . n($SF['server']) . ' ms of ' . n(g($S, 'summary.ttfb_ms')) . ' ms TTFB)', 'area' => 'Server', 'effort' => 'M',
            'why' => 'DNS, TCP and TLS finish in about ' . n($SF['network']) . ' ms; the rest of time to first byte is the origin building the HTML. Cache the HTML at the CDN edge or add page caching at the origin.']);
        if ($SF['tailwind']) $add(['id' => 'tailwind', 'report' => 'perf', 'p' => 'Medium', 't' => 'Replace the Tailwind CDN script with compiled CSS', 'area' => 'Scripts', 'effort' => 'M',
            'why' => 'The page loads ' . n(($SF['tailwind']['size_bytes'] ?? 0) / 1024, 1) . ' KB of Tailwind\'s in-browser compiler. Tailwind documents the CDN build as a development tool; a compiled stylesheet is a few KB.',
            'code' => "npx tailwindcss -i ./src/input.css -o ./public/app.css --minify\n<link rel=\"stylesheet\" href=\"/app.css\">"]);
        $pk = (float)g($S, 'summary.page_size_kb', 0);
        if ($pk > 0 && $SF['tpKb'] / $pk > 0.4) $add(['id' => 'thirdparty', 'report' => 'perf', 'p' => 'Medium', 't' => 'Trim third-party scripts (' . n($SF['tpKb'] / $pk * 100) . '% of page weight)', 'area' => 'Scripts', 'effort' => 'M',
            'why' => n($SF['tpKb'], 1) . ' KB comes from other domains (tag managers, pixels, CDNs). Load analytics after interaction or server-side where possible.']);
        foreach ((array)g($S, 'improve_page_performance', []) as $i => $x) $add(['id' => 'perf' . $i, 'report' => 'perf', 'p' => in_array(($x['grade'] ?? ''), ['D', 'E', 'F']) ? 'High' : 'Medium', 't' => (string)($x['suggestion'] ?? ''), 'area' => 'Caching', 'effort' => 'S',
            'why' => (string)($x['detail'] ?? ''), 'code' => str_contains(strtolower((string)($x['suggestion'] ?? '')), 'cache') ? 'Cache-Control: public, max-age=31536000, immutable' : null]);
    }
    $rank = ['High' => 0, 'Medium' => 1, 'Low' => 2];
    usort($L, fn($a, $b) => $rank[$a['p']] <=> $rank[$b['p']]);
    return $L;
}

// ─────────────────────────── CONTROLLER ───────────────────────────
$input  = trim((string)($_POST['url'] ?? $_GET['url'] ?? ''));
$brandI = trim((string)($_POST['brand'] ?? $_GET['brand'] ?? ''));
$seedI  = trim((string)($_POST['kw'] ?? $_GET['kw'] ?? ''));
$ccI    = strtolower(trim((string)($_POST['cc'] ?? $_GET['cc'] ?? DEFAULT_COUNTRY)));
if (!preg_match('~^[a-z]{2}$~', $ccI)) $ccI = DEFAULT_COUNTRY;
$fresh  = !empty($_POST['fresh']);
$errors = []; $R = null; $T = null; $credits = null;

// Post/Redirect/Get: a submitted check becomes a shareable GET URL that every page reuses.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $input !== '') {
    if ($fresh && is_dir(CACHE_DIR)) array_map('unlink', glob(CACHE_DIR . '/*.json') ?: []);
    header('Location: index.php?' . http_build_query(array_filter(['url' => $input, 'brand' => $brandI, 'kw' => $seedI, 'cc' => $ccI])), true, 303);
    exit;
}

if ($input !== '') {
    $T = normalize_target($input);
    if (!$T) $errors[] = 'Please enter a valid URL or domain, e.g. https://example.com';
    elseif (!$DEMO && ($API_KEY === '' || $API_KEY === VEBAPI_KEY_FALLBACK)) $errors[] = 'VebAPI key not configured. Open config.php (same folder as index.php) and paste your key, or use "demo".';
}

if ($T && !$errors) {
    @set_time_limit(180);
    $common = ['include_subdomains' => 'true', 'exclude_internal_backlinks' => 'true', 'include_indirect_links' => 'true', 'backlinks_status_type' => 'live'];
    $reqs = [
        // SEO
        'audit'      => ['/seo/page-audit/v2',          ['url' => $T['url']]],
        'backlinks'  => ['/backlinks/live',             ['target' => $T['domain'], 'limit' => BACKLINK_LIMIT, 'offset' => 0] + $common],
        'spam'       => ['/backlinks/spam',             ['target' => $T['domain'], 'limit' => SPAM_LIMIT, 'offset' => 0, 'min_spam_score' => SPAM_MIN_SCORE] + $common],
        'refdomains' => ['/backlinks/referraldomains',  ['target' => $T['domain'], 'limit' => REFDOMAIN_LIMIT, 'offset' => 0] + $common],
        'keywords'   => ['/seo/topsearchkeywords',      ['website' => $T['domain'], 'limit' => KEYWORD_LIMIT]],
        'domain'     => ['/seo/domainnamedata/v2',      ['website' => $T['domain'], 'mode' => 'fast', 'include' => 'registration']],
        // AEO
        'aeo'        => ['/seo/ai-visibility-checker/v2', ['website' => $T['url']]],
        'crawler'    => ['/seo/aiseochecker',           ['website' => $T['domain']]],
        // PERFORMANCE
        'speed'      => ['/seo/loadingspeeddata/v2',    ['website' => $T['url']]],
    ];
    if ($seedI !== '') $reqs['related'] = ['/seo/keywordresearch', ['keyword' => $seedI, 'country' => $ccI]];
    $R = $DEMO ? demo_results($T, $seedI) : veb_multi($reqs, $API_KEY);
}
if (!$DEMO && $API_KEY !== VEBAPI_KEY_FALLBACK && $API_KEY !== '') {
    // Credit balance is cheap; cached 2 minutes so page-to-page navigation stays fast
    $cf = CACHE_DIR . '/credits.cache';
    if (is_file($cf) && time() - filemtime($cf) < 120) $credits = json_decode((string)file_get_contents($cf), true);
    else {
        $ch = curl_init(VEBAPI_BASE . '/creditbalance');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_HTTPHEADER => ['X-API-KEY: ' . $API_KEY]]);
        $credits = g(json_decode((string)curl_exec($ch), true), 'credits');
        curl_close($ch);
        if ($credits !== null && is_dir(CACHE_DIR)) @file_put_contents($cf, json_encode($credits));
    }
} elseif ($DEMO) $credits = 'demo';

// Derived data
if ($R) {
    $D = fn($k) => $R[$k]['data'] ?? [];
    $kwRaw = $D('keywords');
    $KW = isset($kwRaw['keywords']) ? $kwRaw['keywords'] : (array_is_list($kwRaw) ? $kwRaw : (g($kwRaw, 'data.keywords', []) ?: []));
    usort($KW, fn($a, $b) => ($b['seoClicks'] ?? 0) <=> ($a['seoClicks'] ?? 0) ?: ($a['rank'] ?? 999) <=> ($b['rank'] ?? 999));
    $brandTerms = $brandI !== '' ? array_map('trim', explode(',', $brandI)) : [$T['brand']];
    $ORG   = organic_summary($KW);
    $SPLIT = split_branded($KW, $brandTerms);
    $BR    = organic_summary($SPLIT['branded']);
    $NB    = organic_summary($SPLIT['non']);
    $AUTH  = authority_score($D('backlinks'), $D('refdomains'), $D('spam'), $D('domain'));
    $blTot = (int)g($D('backlinks'), 'counts.backlinks.total', 0);
    $blDo  = (int)g($D('backlinks'), 'counts.backlinks.doFollow', 0);
    $blNo  = max(0, $blTot - $blDo);
    $spamTot = (int)g($D('spam'), 'counts.backlinks.total', 0);
    $relRaw = $D('related');
    $REL   = array_is_list($relRaw) ? $relRaw : (array)g($relRaw, 'data', g($relRaw, 'keywords', []));
    $X     = $D('aeo'); $C = $D('crawler'); $S = $D('speed');
    $AF    = aeo_facts($X, $T['domain']);
    $SF    = speed_facts($S, $T['domain']);
    $ACT   = build_actions($X, $C, $S, $AF, $SF, $T);
    $A     = $D('audit');
    $seoScore  = g($A, 'summary.overall_score', g($A, 'scores.overall'));
    $aeoScore  = g($X, 'ai_score.total');
    $perfScore = g($S, 'summary.performance_grade.score');
}


// Query string that carries the current check from page to page (results come from cache, 0 credits)
$QS = ($T && !$errors) ? '?' . http_build_query(array_filter(['url' => $input, 'brand' => $brandI, 'kw' => $seedI, 'cc' => $ccI])) : '';
function page_url(string $page, string $anchor = ''): string { global $QS; return $page . $QS . ($anchor !== '' ? '#' . $anchor : ''); }

