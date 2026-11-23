/* SEO · AEO · Performance Report — shared page script (InApp layout behaviour + report widgets) */
(function () {
  var body = document.body, sidebar = document.getElementById('sidebar'), content = document.getElementById('content'),
      topbar = document.getElementById('topbar'), overlay = document.getElementById('overlay');

  // InApp sidebar: desktop collapse (remembered), mobile slide-in with overlay
  function setCollapsed(on) { sidebar.classList.toggle('collapsed', on); content.classList.toggle('full', on); topbar.classList.toggle('full', on); }
  try { if (localStorage.getItem('sap-sidebar') === '1' && window.innerWidth > 992) setCollapsed(true); } catch (e) {}
  document.getElementById('toggleBtn').addEventListener('click', function () {
    var on = !sidebar.classList.contains('collapsed'); setCollapsed(on);
    try { localStorage.setItem('sap-sidebar', on ? '1' : '0'); } catch (e) {}
  });
  document.getElementById('mobileBtn').addEventListener('click', function () { sidebar.classList.add('mobile-show'); overlay.classList.add('show'); });
  function closeMobile() { sidebar.classList.remove('mobile-show'); overlay.classList.remove('show'); }
  overlay.addEventListener('click', closeMobile);
  sidebar.querySelectorAll('.sub a').forEach(function (a) { a.addEventListener('click', closeMobile); });

  // Print
  document.querySelectorAll('#btnPrint, .print-btn').forEach(function (b) { b.addEventListener('click', function () { window.print(); }); });

  // Table filters
  document.querySelectorAll('input.filter').forEach(function (inp) {
    inp.addEventListener('input', function () {
      var q = inp.value.toLowerCase(), rows = document.getElementById(inp.dataset.target).tBodies[0].rows;
      for (var i = 0; i < rows.length; i++) rows[i].style.display = rows[i].textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
    });
  });

  // Copy buttons
  document.querySelectorAll('.copy-btn').forEach(function (b) {
    b.addEventListener('click', function () {
      var src = b.dataset.copyTarget ? document.getElementById(b.dataset.copyTarget) : b.previousElementSibling, lab = b.querySelector('span'), old = lab.textContent;
      if (navigator.clipboard && src) navigator.clipboard.writeText(src.textContent).then(function () { lab.textContent = 'Copied'; setTimeout(function () { lab.textContent = old; }, 1800); }, function () {});
    });
  });

  // Action checklist, remembered per domain (shared by AEO, Performance and Full report pages)
  var key = 'seo-aeo-perf-done:' + (body.dataset.domain || ''), done = {};
  try { done = JSON.parse(localStorage.getItem(key) || '{}'); } catch (e) {}
  document.querySelectorAll('.act input[data-id]').forEach(function (cb) {
    var card = cb.closest('.act');
    cb.checked = !!done[cb.dataset.id]; card.classList.toggle('done', cb.checked);
    cb.addEventListener('change', function () {
      done[cb.dataset.id] = cb.checked; card.classList.toggle('done', cb.checked);
      try { localStorage.setItem(key, JSON.stringify(done)); } catch (e) {}
    });
  });

  // Related-keyword value estimator
  var CTR = [28, 15, 11, 8, 7, 5, 4, 3, 3, 2.5], pos = document.getElementById('pos');
  function est() {
    var p = +pos.value; document.getElementById('posv').textContent = p;
    document.querySelectorAll('#tbl-rel tbody tr').forEach(function (tr) {
      var c = Math.round(+tr.dataset.vol * CTR[p - 1] / 100);
      tr.querySelector('.est-c').textContent = c.toLocaleString();
      tr.querySelector('.est-v').textContent = '$' + Math.round(c * +tr.dataset.cpc).toLocaleString() + '/mo';
    });
  }
  if (pos) { pos.addEventListener('input', est); est(); }

  // ApexCharts gauges and donuts (same library as the InApp template); CSS fallbacks stay if it cannot load
  if (window.ApexCharts) {
    document.querySelectorAll('.apx-radial').forEach(function (el) {
      el.innerHTML = '';
      new ApexCharts(el, { chart: { type: 'radialBar', height: 210, fontFamily: 'Poppins, sans-serif', sparkline: { enabled: true } },
        series: [+el.dataset.value], colors: [el.dataset.color], labels: [el.dataset.sub], stroke: { lineCap: 'round' },
        plotOptions: { radialBar: { hollow: { size: '62%' }, track: { background: '#f5f5f5' },
          dataLabels: { name: { show: true, offsetY: 24, fontSize: '12px', color: '#737373' }, value: { offsetY: -10, fontSize: '30px', fontWeight: 700, color: '#171717', formatter: function (v) { return Math.round(v); } } } } } }).render();
    });
    document.querySelectorAll('.apx-donut').forEach(function (el) {
      el.innerHTML = '';
      new ApexCharts(el, { chart: { type: 'donut', height: 200, fontFamily: 'Poppins, sans-serif' }, series: JSON.parse(el.dataset.series), labels: JSON.parse(el.dataset.labels), colors: ['#E66239', '#f7a085'],
        legend: { show: false }, dataLabels: { enabled: false }, stroke: { width: 0 },
        plotOptions: { pie: { donut: { size: '70%', labels: { show: true, value: { fontSize: '20px', fontWeight: 600 }, total: { show: true, label: 'Total', fontSize: '12px', color: '#737373', formatter: function (w) { return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0).toLocaleString(); } } } } } } }).render();
    });
  }
})();
