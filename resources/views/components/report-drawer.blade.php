{{-- In-page report preview. Include on any page that has Chart.js loaded and a
     trigger with [data-open-report]. Everything happens without navigating, so
     "Back" simply closes the panel and the page is exactly as it was left. --}}
@push('styles')
<style>
    .report-scrim {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        z-index: 10040;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity var(--dur-slow) var(--ease-out),
            visibility 0s linear var(--dur-slow);
    }

    .report-scrim.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transition: opacity var(--dur-slow) var(--ease-out), visibility 0s linear 0s;
    }

    .report-drawer {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        width: min(880px, 92vw);
        background: var(--color-surface);
        box-shadow: -8px 0 32px rgba(15, 23, 42, 0.18);
        z-index: 10045;
        display: flex;
        flex-direction: column;
        transform: translateX(100%);
        visibility: hidden;
        transition: transform var(--dur-slow) var(--ease-out),
            visibility 0s linear var(--dur-slow);
    }

    .report-drawer.is-open {
        transform: none;
        visibility: visible;
        transition: transform var(--dur-slow) var(--ease-out), visibility 0s linear 0s;
    }

    .report-drawer__head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 18px 22px;
        border-bottom: 1px solid var(--color-border);
        flex-shrink: 0;
    }

    .report-drawer__title {
        font-weight: 700;
        font-size: 1.08rem;
        color: var(--color-text);
        flex: 1;
        min-width: 0;
    }

    .report-drawer__meta {
        display: block;
        font-weight: 500;
        font-size: 0.82rem;
        color: var(--color-text-muted);
        margin-top: 2px;
    }

    .report-drawer__body {
        flex: 1;
        min-height: 0;
        background: #f1f1f4;
        position: relative;
    }

    .report-drawer__frame {
        width: 100%;
        height: 100%;
        border: 0;
        display: block;
    }

    .report-drawer__state {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 14px;
        text-align: center;
        padding: 32px;
        color: var(--color-text-muted);
        background: var(--color-surface);
    }

    .report-drawer__spinner {
        width: 38px;
        height: 38px;
        border: 3px solid var(--color-primary-soft);
        border-top-color: var(--color-primary);
        border-radius: 50%;
        animation: spinner-inline-spin 0.7s linear infinite;
    }

    /* Offscreen: Chart.js needs laid-out canvases with real dimensions, and
       these are only ever read back via toBase64Image(). Distinct ids so they
       cannot collide with the charts already on Forecasting/Analytics. */
    .report-stage {
        position: absolute;
        left: -10000px;
        top: 0;
        width: 1000px;
        pointer-events: none;
    }

    @media (max-width: 700px) {
        .report-drawer__head { flex-wrap: wrap; }
    }
</style>
@endpush

<div class="report-scrim" id="reportScrim"></div>

<aside class="report-drawer" id="reportDrawer" role="dialog" aria-modal="true"
    aria-labelledby="reportDrawerTitle" aria-hidden="true">
    <div class="report-drawer__head">
        <button type="button" class="btn-action" id="reportBackBtn" style="background:#6c757d;">← Back</button>
        <div class="report-drawer__title" id="reportDrawerTitle">
            Report Preview
            <span class="report-drawer__meta" id="reportDrawerMeta">Forecasting &amp; Analytics</span>
        </div>
        <button type="button" class="btn-action edit" id="reportPrintBtn" disabled>Print</button>
        <button type="button" class="btn-action ghost" id="reportDownloadBtn" disabled>Download</button>
    </div>

    <div class="report-drawer__body">
        <iframe class="report-drawer__frame" id="reportFrame" title="Report preview"></iframe>

        <div class="report-drawer__state" id="reportState">
            <div class="report-drawer__spinner" id="reportSpinner"></div>
            <div id="reportStateText">Gathering data and rendering charts…</div>
        </div>
    </div>

    <div class="report-stage" aria-hidden="true">
        <canvas id="rptActualSales" width="1000" height="420"></canvas>
        <canvas id="rptForecast" width="1000" height="420"></canvas>
        <canvas id="rptRevenueTrend" width="1000" height="420"></canvas>
        <canvas id="rptCategory" width="600" height="420"></canvas>
    </div>
</aside>

@push('scripts')
<script>
    (function () {
        var drawer = document.getElementById('reportDrawer');
        var scrim = document.getElementById('reportScrim');
        var frame = document.getElementById('reportFrame');
        var state = document.getElementById('reportState');
        var stateText = document.getElementById('reportStateText');
        var spinner = document.getElementById('reportSpinner');
        var printBtn = document.getElementById('reportPrintBtn');
        var downloadBtn = document.getElementById('reportDownloadBtn');
        var backBtn = document.getElementById('reportBackBtn');

        var blobUrl = null;
        var built = {};
        var charts = {};
        var lastFocus = null;

        function setState(text, busy) {
            state.style.display = 'flex';
            stateText.textContent = text;
            spinner.style.display = busy ? 'block' : 'none';
        }

        function clearState() {
            state.style.display = 'none';
        }

        function open() {
            lastFocus = document.activeElement;
            drawer.classList.add('is-open');
            scrim.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            backBtn.focus({ preventScroll: true });
        }

        function close() {
            drawer.classList.remove('is-open');
            scrim.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            if (lastFocus) lastFocus.focus({ preventScroll: true });
        }

        function reset() {
            printBtn.disabled = true;
            downloadBtn.disabled = true;
            frame.removeAttribute('src');
            // Release the previous document before building another, otherwise
            // each regenerate leaks a multi-megabyte blob for the tab's lifetime.
            if (blobUrl) { URL.revokeObjectURL(blobUrl); blobUrl = null; }
            Object.keys(charts).forEach(function (k) { charts[k].destroy(); delete charts[k]; });
            built = {};
        }

        function peso(v) { return '₱' + new Intl.NumberFormat('en-PH').format(v); }

        function line(id, labels, values, label, color, dashed) {
            var el = document.getElementById(id);
            if (!el || !labels.length) return null;
            return new Chart(el, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label, data: values,
                        borderColor: color,
                        backgroundColor: color.replace('rgb', 'rgba').replace(')', ', 0.1)'),
                        borderWidth: 3,
                        borderDash: dashed ? [8, 4] : undefined,
                        fill: true, tension: 0.4, pointRadius: 4,
                        pointBackgroundColor: color
                    }]
                },
                // No animation: a capture taken mid-animation would be a
                // half-drawn chart. No responsive sizing: the canvas is offscreen.
                options: {
                    responsive: false, maintainAspectRatio: false, animation: false,
                    plugins: { legend: { display: true, labels: { font: { size: 13 } } } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: peso, font: { size: 12 } } },
                        x: { ticks: { font: { size: 12 } } }
                    }
                }
            });
        }

        function drawCharts(d) {
            var months = [], values = [];
            ((d.salesTrend && d.salesTrend.months) || []).forEach(function (m, i) {
                if (d.salesTrend.actual && d.salesTrend.actual[i] !== null) {
                    months.push(m); values.push(d.salesTrend.actual[i]);
                }
            });
            charts.actual_sales = line('rptActualSales', months, values, 'Actual Sales', 'rgb(99, 102, 241)', false);

            var predicted = (d.forecast && d.forecast.predicted) || {};
            var fLabels = Object.keys(predicted).map(function (m) {
                // Python SARIMAX returns "Jul 2026"; the PHP fallback "2026-07".
                if (/^[A-Za-z]{3}\s\d{4}$/.test(m)) return m;
                var dt = new Date(m + '-01');
                return isNaN(dt.getTime()) ? m : dt.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            });
            charts.forecast = line('rptForecast', fLabels, Object.values(predicted),
                'SARIMA Forecast', 'rgb(22, 163, 74)', true);

            charts.revenue_trend = line('rptRevenueTrend', d.trendMonths || [], d.trendRevenue || [],
                'Revenue', 'rgb(99, 102, 241)', false);

            var catEl = document.getElementById('rptCategory');
            if (catEl && (d.categoryLabels || []).length) {
                charts.category = new Chart(catEl, {
                    type: 'doughnut',
                    data: {
                        labels: d.categoryLabels,
                        datasets: [{
                            data: d.categoryTotals,
                            backgroundColor: ['#6366f1', '#16a34a', '#f59e0b', '#a21caf', '#2563eb']
                        }]
                    },
                    options: {
                        responsive: false, maintainAspectRatio: false, animation: false,
                        plugins: { legend: { position: 'bottom', labels: { font: { size: 13 } } } }
                    }
                });
            }

            Object.keys(charts).forEach(function (k) {
                if (charts[k]) built[k] = charts[k].toBase64Image('image/png', 1);
            });
        }

        function twoFrames() {
            // One frame for Chart.js to lay out, one for it to paint.
            return new Promise(function (resolve) {
                requestAnimationFrame(function () { requestAnimationFrame(resolve); });
            });
        }

        async function build() {
            reset();
            setState('Gathering data and rendering charts…', true);

            try {
                var res = await fetch('{{ route('reports.datasets') }}', {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!res.ok) throw new Error('Could not load report data (' + res.status + ').');
                var data = await res.json();

                drawCharts(data);
                await twoFrames();

                setState('Building PDF…', true);

                var body = new FormData();
                body.append('_token', '{{ csrf_token() }}');
                body.append('token', data.token);
                Object.keys(built).forEach(function (k) {
                    body.append('charts[' + k + ']', built[k]);
                });

                var pdfRes = await fetch('{{ route('reports.generate') }}', {
                    method: 'POST', body: body, credentials: 'same-origin'
                });

                if (!pdfRes.ok) {
                    var msg = 'Could not build the report (' + pdfRes.status + ').';
                    try { msg = (await pdfRes.json()).message || msg; } catch (e) { /* not JSON */ }
                    throw new Error(msg);
                }

                var blob = await pdfRes.blob();
                blobUrl = URL.createObjectURL(blob);
                frame.src = blobUrl + '#toolbar=0&navpanes=0';

                document.getElementById('reportDrawerMeta').textContent =
                    'Forecasting & Analytics · ' + Math.round(blob.size / 1024).toLocaleString() + ' KB';
                printBtn.disabled = false;
                downloadBtn.disabled = false;
                clearState();
            } catch (err) {
                setState(err.message || 'Something went wrong building the report.', false);
            }
        }

        document.querySelectorAll('[data-open-report]').forEach(function (trigger) {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                open();
                build();
            });
        });

        backBtn.addEventListener('click', close);
        scrim.addEventListener('click', close);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drawer.classList.contains('is-open')) close();
        });

        printBtn.addEventListener('click', function () {
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch (err) {
                // Some browsers refuse print() on an embedded PDF.
                if (blobUrl) window.open(blobUrl, '_blank', 'noopener');
            }
        });

        downloadBtn.addEventListener('click', function () {
            if (!blobUrl) return;
            var a = document.createElement('a');
            a.href = blobUrl;
            a.download = 'sarima-report-' + new Date().toISOString().slice(0, 10) + '.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
        });

        window.addEventListener('beforeunload', function () {
            if (blobUrl) URL.revokeObjectURL(blobUrl);
        });
    })();
</script>
@endpush
