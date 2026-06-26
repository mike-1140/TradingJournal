<div>
    <x-page-header title="Psychologie — Auswertung" icon="graph-up">
        <x-slot:actions>
            <a href="{{ route('psychology.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Neuer Eintrag
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- ── Metriken ──────────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-md col-6">
            <x-stat-card title="Erfasst" :value="$stats['total']" color="secondary" icon="journal-check" />
        </div>
        <div class="col-md col-6">
            <x-stat-card
                title="Gesamt P&L"
                :value="$stats['total_pnl'] !== null ? (($stats['total_pnl'] >= 0 ? '+' : '') . number_format($stats['total_pnl'], 2, ',', '') . ' $') : '–'"
                :color="($stats['total_pnl'] ?? 0) >= 0 ? 'success' : 'danger'"
                icon="graph-up" />
        </div>
        <div class="col-md col-6">
            <x-stat-card
                title="Win-Rate"
                :value="$stats['win_rate'] !== null ? $stats['win_rate'] . '%' : '–'"
                color="primary"
                icon="trophy" />
        </div>
        <div class="col-md col-6">
            <x-stat-card
                title="Ø Compliance"
                :value="$stats['compliance_avg'] !== null ? $stats['compliance_avg'] . '%' : '–'"
                color="success"
                icon="shield-check" />
        </div>
        <div class="col-md col-6">
            <x-stat-card
                title="Versuchungsrate"
                :value="$stats['tempt_rate'] !== null ? $stats['tempt_rate'] . '%' : '–'"
                color="warning"
                icon="lightning-charge" />
        </div>
    </div>

    @if ($stats['total'] === 0)
    <div class="text-center text-muted py-5">
        <i class="bi bi-graph-up fs-2 d-block mb-2"></i>
        Noch keine Einträge für den gewählten Zeitraum.
        <a href="{{ route('psychology.create') }}" class="d-block mt-2">Ersten Eintrag erfassen</a>
    </div>
    @else

    {{-- ── Charts ────────────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-3">

        {{-- Cost of Emotion --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-lightning-charge"></i> Cost of Emotion
                        <small class="text-muted fw-normal">Ø P&L pro Motivation</small>
                    </h6>
                </div>
                <div class="card-body p-2">
                    <div id="chart-motivation" wire:ignore style="min-height:220px"></div>
                </div>
            </div>
        </div>

        {{-- Emotional Sweet Spot --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-emoji-smile"></i> Emotional Sweet Spot
                        <small class="text-muted fw-normal">Ø P&L pro Gemütszustand (1–5)</small>
                    </h6>
                </div>
                <div class="card-body p-2">
                    <div id="chart-emotion" wire:ignore style="min-height:220px"></div>
                </div>
            </div>
        </div>

    </div>
    <div class="row g-3">

        {{-- Compliance-Performance --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-shield-check"></i> Compliance-Performance
                        <small class="text-muted fw-normal">Ø P&L pro Regelerfüllungs-Bucket</small>
                    </h6>
                </div>
                <div class="card-body p-2">
                    <div id="chart-compliance" wire:ignore style="min-height:220px"></div>
                </div>
            </div>
        </div>

        {{-- Regelversuchungen --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Regelversuchungen
                        <small class="text-muted fw-normal">Häufigkeit pro Regel</small>
                    </h6>
                </div>
                <div class="card-body p-2">
                    @if (count($charts['temptation']['labels']) === 0)
                    <div class="d-flex align-items-center justify-content-center text-muted"
                         style="min-height:220px">Keine Versuchungen erfasst</div>
                    @else
                    <div id="chart-temptation" wire:ignore style="min-height:220px"></div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    @endif

    {{-- ── ApexCharts ─────────────────────────────────────────────────────── --}}
    @once
    <script>
    (function () {
        var C = {};

        function pnlColors(data) {
            return data.map(function (v) { return v === null ? '#adb5bd' : (v >= 0 ? '#198754' : '#dc3545'); });
        }

        function barChart(elId, labels, data) {
            var el = document.getElementById(elId);
            if (!el) return;
            if (C[elId]) { C[elId].destroy(); C[elId] = null; }
            var series = data.map(function (v) { return v !== null ? v : 0; });
            C[elId] = new ApexCharts(el, {
                chart: { type: 'bar', height: 220, toolbar: { show: false }, fontFamily: 'inherit', accessibility: { enabled: false } },
                plotOptions: { bar: { borderRadius: 3, distributed: true } },
                series: [{ name: 'Ø P&L', data: series }],
                xaxis:  { categories: labels },
                yaxis:  { labels: { formatter: function (v) { return '$ ' + v.toFixed(0); } } },
                colors: pnlColors(data),
                legend: { show: false },
                dataLabels: {
                    enabled: true,
                    formatter: function (v, opt) {
                        var orig = data[opt.dataPointIndex];
                        return orig !== null ? '$ ' + orig.toFixed(0) : '–';
                    },
                    style: { fontSize: '11px' },
                },
                tooltip: { y: { formatter: function (v) { return '$ ' + v.toFixed(2); } } },
            });
            C[elId].render();
        }

        function temptBar(labels, data) {
            var el = document.getElementById('chart-temptation');
            if (!el) return;
            if (C.temptation) { C.temptation.destroy(); C.temptation = null; }
            C.temptation = new ApexCharts(el, {
                chart: { type: 'bar', height: 220, toolbar: { show: false }, fontFamily: 'inherit', accessibility: { enabled: false } },
                plotOptions: { bar: { horizontal: true, borderRadius: 3, distributed: true } },
                series:  [{ name: 'Versuchungen', data: data }],
                xaxis:   { categories: labels, labels: { formatter: function (v) { return Math.round(v); } } },
                colors:  data.map(function () { return '#ffc107'; }),
                legend:  { show: false },
                dataLabels: { enabled: true, style: { fontSize: '11px' } },
            });
            C.temptation.render();
        }

        function render(d) {
            if (typeof ApexCharts === 'undefined') { setTimeout(function () { render(d); }, 100); return; }
            barChart('chart-motivation', d.motivationLabels, d.motivationData);
            barChart('chart-emotion',   d.emotionLabels,    d.emotionData);
            barChart('chart-compliance',d.complianceLabels, d.complianceData);
            if (d.temptationLabels && d.temptationLabels.length > 0) {
                temptBar(d.temptationLabels, d.temptationData);
            }
        }

        document.addEventListener('psych-analytics-data', function (e) { render(e.detail); });
    })();
    </script>
    @endonce
</div>
