# SEO · AEO · Performance Report (InApp theme, multi-page)

## Pages (in sequence)
| File | Page |
|---|---|
| `index.php` | Overview & new check (run form, 3 scores, top fixes) |
| `seo.php` | 1 · SEO Report |
| `aeo.php` | 2 · AEO Report |
| `performance.php` | 3 · Performance Report |
| `report.php` | Full report 1 → 3 (printable / save as PDF) |

## Structure
```
config.php            your VebAPI key (keep your existing file — not included in this zip)
index.php seo.php aeo.php performance.php report.php
app/core.php          config, VebAPI client, cache, demo data, scoring, fix list
app/view.php          InApp view helpers (cards, badges, tables, gauges)
app/header.php        <head>, top bar, sidebar (shared by every page)
app/footer.php        previous / next page buttons, scripts
app/sections/*.php    report bodies, reused by the single pages and report.php
assets/app.css        InApp (Bootstrap 5.3.8) subset + app styles
assets/app.js         sidebar, filters, copy, checklist, keyword estimator, ApexCharts
cache/                API results (24 h); must be writable by PHP
```

## How it works
- Submitting the form redirects to `index.php?url=…&brand=…&kw=…&cc=…`. Every page link carries those
  parameters, and results come from the 24 h cache, so moving between pages uses **0 credits**.
- Tick-box progress on fixes is kept per domain in the browser and shared across pages.
- `app/` and `cache/` include `.htaccess` deny rules (Apache). On Nginx, block them with
  `location ~ ^/(app|cache)/ { deny all; }`.

## Setup
PHP 8.0+ with curl. Put your key in `config.php` (`demo` shows sample data). Local run: `php -S localhost:8000`.
