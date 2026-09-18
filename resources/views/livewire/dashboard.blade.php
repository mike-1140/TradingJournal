<div>
    @php
    $envColors = [
        'MONEY'       => '#198754',
        'PAPER'       => '#0d6efd',
        'TEST'        => '#6c757d',
        'AUTOM_MONEY' => '#fd7e14',
        'AUTOM_PAPER' => '#6f42c1',
    ];
    $activeColor = $envColors[$selectedEnvironment] ?? '#6c757d';
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="mb-0">Trading Dashboard</h1>
            <small class="text-muted">Analyse & Statistik</small>
        </div>

        <div class="d-flex gap-2 align-items-center">
            {{-- Environment Dropdown --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span style="width:9px;height:9px;border-radius:50%;display:inline-block;background:{{ $activeColor }};flex-shrink:0"></span>
                    {{ $selectedEnvironment }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    @foreach($environments as $env)
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2 {{ $selectedEnvironment === $env ? 'fw-semibold' : '' }}"
                                    wire:click="changeEnvironment('{{ $env }}')">
                                <span style="width:9px;height:9px;border-radius:50%;display:inline-block;background:{{ $envColors[$env] ?? '#6c757d' }};flex-shrink:0"></span>
                                {{ $env }}
                                @if($selectedEnvironment === $env)
                                    <i class="bi bi-check ms-auto text-success"></i>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Kachel-Einstellungen --}}
            <button wire:click="$toggle('showWidgetSettings')"
                    class="btn btn-sm btn-outline-secondary" title="Kacheln konfigurieren">
                <i class="bi bi-gear"></i>
            </button>

            {{-- User Dropdown — nur sichtbar mit Nutzerwechsel-Berechtigung (lokal + Admin) --}}
            @can('switch-user')
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person"></i>
                    {{ auth()->user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    @foreach($users as $u)
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2 {{ auth()->id() === $u->id ? 'fw-semibold' : '' }}"
                                    wire:click="switchUser({{ $u->id }})">
                                <i class="bi bi-person{{ auth()->id() === $u->id ? '-fill' : '' }} text-muted"></i>
                                {{ $u->name }}
                                @if(auth()->id() === $u->id)
                                    <i class="bi bi-check ms-auto text-success"></i>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endcan
        </div>
    </div>

    {{-- Kachel-Registry: nur Widgets aus der aktiven Section rendern, die in $activeWidgets stehen --}}
    @php
        $section = fn(string $name) => collect($widgets)
            ->filter(fn($w, $key) => ($w['section'] ?? null) === $name && in_array($key, $activeWidgets, true));
    @endphp

    <!-- KPI-Boxen: Fixe Perioden + Zeitraum -->
    <div class="row g-3 mb-3">
        @foreach($section('kpi_primary') as $key => $w)
            <div class="{{ $w['col'] }}">
                @include($w['view'])
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        @foreach($section('kpi_secondary') as $key => $w)
            <div class="{{ $w['col'] }}">
                @include($w['view'])
            </div>
        @endforeach
    </div>

    <!-- Risiko-Kennzahlen -->
    <div class="row g-3 mb-4">
        @foreach($section('kpi_risk') as $key => $w)
            <div class="{{ $w['col'] }}">
                @include($w['view'])
            </div>
        @endforeach
    </div>

    <!-- Performance-Detail -->
    <div class="row g-3 mb-4">
        @foreach($section('kpi_performance') as $key => $w)
            <div class="{{ $w['col'] }}">
                @include($w['view'])
            </div>
        @endforeach
    </div>

    <!-- NAV KPI-Karten (nur wenn Snapshot-Daten vorhanden) -->
    @if($navCurrent !== null)
    <div class="row g-3 mb-3">
        @foreach($section('nav_primary') as $key => $w)
            <div class="{{ $w['col'] }}">
                @include($w['view'])
            </div>
        @endforeach
    </div>
    <div class="row g-3 mb-3">
        @foreach($section('nav_secondary') as $key => $w)
            <div class="{{ $w['col'] }}">
                @include($w['view'])
            </div>
        @endforeach
    </div>
    @endif

    <!-- NAV-Kurve (immer im DOM, Sichtbarkeit per CSS) -->
    @foreach($section('chart_nav_row') as $key => $w)
        <div class="row g-3 mb-3 {{ $navCurrent !== null ? '' : 'd-none' }}">
            <div class="{{ $w['col'] }}">
                @include($w['view'])
            </div>
        </div>
    @endforeach

    <!-- Chart: Kumulativer P&L -->
    <div class="row g-3 mb-3">
        @foreach($section('charts_main') as $key => $w)
            <div class="{{ $w['col'] }}">
                @include($w['view'])
            </div>
        @endforeach
    </div>

    <!-- Charts: Spreads-Donut + Option/Aktie-Donut -->
    <div class="row g-3 mb-3">
        @foreach($section('charts_secondary') as $key => $w)
            <div class="{{ $w['col'] }}">
                @include($w['view'])
            </div>
        @endforeach
    </div>

    <!-- Chart: Monatlicher P&L -->
    <div class="row g-3 mb-4">
        @foreach($section('charts_monthly') as $key => $w)
            <div class="{{ $w['col'] }}">
                @include($w['view'])
            </div>
        @endforeach
    </div>

    <script>
    (function () {
        var C = window.__dashCharts = window.__dashCharts || {};

        var d = {
            cumulative:       @json($chartCumulative),
            monthly:          @json($chartMonthly),
            strategyLabels:   @json($chartStrategyLabels),
            strategyValues:   @json($chartStrategyValues),
            strategyActual:   @json($chartStrategyActual),
            instrumentLabels: @json($chartInstrumentLabels),
            instrumentValues: @json($chartInstrumentValues),
            instrumentActual: @json($chartInstrumentActual),
            nav:              @json($chartNav)
        };

        function fmt0(v)  { return '$ ' + Math.round(v).toLocaleString('de-DE'); }
        function fmt2(v)  { return '$ ' + parseFloat(v).toFixed(2); }

        function renderStrategy(data) {
            var el = document.getElementById('chart-strategy');
            if (!el || !data.strategyValues || data.strategyValues.length === 0) return;
            if (C.strategy) { C.strategy.destroy(); C.strategy = null; }
            var colors = data.strategyActual.map(function(v) { return v >= 0 ? undefined : '#dc3545'; });
            C.strategy = new ApexCharts(el, {
                chart:       { type: 'donut', height: 260, fontFamily: 'inherit', accessibility: { enabled: false } },
                series:      data.strategyValues,
                labels:      data.strategyLabels,
                colors:      colors.some(function(c) { return c; })
                                 ? colors.map(function(c, i) { return c || ['#008FFB','#00E396','#FEB019','#FF4560','#775DD0'][i % 5]; })
                                 : undefined,
                legend:      { position: 'bottom', fontSize: '13px' },
                dataLabels:  { formatter: function (val) { return val.toFixed(1) + '%'; } },
                tooltip:     { y: { formatter: function(val, opts) {
                    var actual = data.strategyActual[opts.seriesIndex];
                    return (actual >= 0 ? '$ ' : '−$ ') + Math.abs(actual).toFixed(2);
                }}},
                plotOptions: { pie: { donut: { size: '65%' } } }
            });
            C.strategy.render();
        }

        function renderInstrument(data) {
            var el = document.getElementById('chart-instrument');
            if (!el || !data.instrumentValues || data.instrumentValues.length === 0) return;
            if (C.instrument) { C.instrument.destroy(); C.instrument = null; }
            var colors = data.instrumentActual.map(function(v) { return v >= 0 ? undefined : '#dc3545'; });
            C.instrument = new ApexCharts(el, {
                chart:       { type: 'donut', height: 260, fontFamily: 'inherit', accessibility: { enabled: false } },
                series:      data.instrumentValues,
                labels:      data.instrumentLabels,
                colors:      colors.some(function(c) { return c; })
                                 ? colors.map(function(c, i) { return c || ['#008FFB','#00E396'][i % 2]; })
                                 : undefined,
                legend:      { position: 'bottom', fontSize: '13px' },
                dataLabels:  { formatter: function (val) { return val.toFixed(1) + '%'; } },
                tooltip:     { y: { formatter: function(val, opts) {
                    var actual = data.instrumentActual[opts.seriesIndex];
                    return (actual >= 0 ? '$ ' : '−$ ') + Math.abs(actual).toFixed(2);
                }}},
                plotOptions: { pie: { donut: { size: '65%' } } }
            });
            C.instrument.render();
        }

        function renderNav(data) {
            var el = document.getElementById('chart-nav');
            if (C.nav) { C.nav.destroy(); C.nav = null; }
            if (!el || !data.nav || data.nav.length === 0) return;
            C.nav = new ApexCharts(el, {
                chart:      { type: 'area', height: 240, toolbar: { show: false }, fontFamily: 'inherit', zoom: { enabled: false }, accessibility: { enabled: false } },
                series:     [{ name: 'NAV', data: data.nav }],
                xaxis:      { type: 'category', labels: { show: false } },
                yaxis:      { labels: { formatter: function(v) { return '€ ' + v.toLocaleString('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); } }, min: function(min) { return min * 0.995; } },
                colors:     ['#0d6efd'],
                fill:       { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.03 } },
                stroke:     { curve: 'smooth', width: 2 },
                dataLabels: { enabled: false },
                grid:       { borderColor: '#f0f0f0', strokeDashArray: 3 },
                tooltip:    { x: { formatter: function(v, opts) { return data.nav[opts.dataPointIndex].x; } }, y: { formatter: function(v) { return '€ ' + v.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } } }
            });
            C.nav.render();
        }

        function init() {
            if (typeof ApexCharts === 'undefined') { setTimeout(init, 100); return; }

            renderNav(d);

            if (!C.cumulative && document.getElementById('chart-cumulative')) {
                C.cumulative = new ApexCharts(document.getElementById('chart-cumulative'), {
                    chart:       { type: 'area', height: 260, toolbar: { show: false }, fontFamily: 'inherit', zoom: { enabled: false }, accessibility: { enabled: false } },
                    series:      [{ name: 'Kum. P&L', data: d.cumulative }],
                    xaxis:       { type: 'category' },
                    yaxis:       { labels: { formatter: fmt0 } },
                    colors:      ['#198754'],
                    fill:        { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.03 } },
                    stroke:      { curve: 'smooth', width: 2 },
                    dataLabels:  { enabled: false },
                    grid:        { borderColor: '#f0f0f0', strokeDashArray: 3 },
                    tooltip:     { y: { formatter: fmt2 } }
                });
                C.cumulative.render();
            }

            if (!C.monthly && document.getElementById('chart-monthly')) {
                C.monthly = new ApexCharts(document.getElementById('chart-monthly'), {
                    chart:       { type: 'bar', height: 220, toolbar: { show: false }, fontFamily: 'inherit', zoom: { enabled: false }, accessibility: { enabled: false } },
                    series:      [{ name: 'P&L', data: d.monthly }],
                    xaxis:       { type: 'category' },
                    yaxis:       { labels: { formatter: fmt0 } },
                    plotOptions: { bar: { borderRadius: 3, colors: { ranges: [{ from: -9999999, to: -0.01, color: '#dc3545' }, { from: 0, to: 9999999, color: '#198754' }] } } },
                    dataLabels:  { enabled: false },
                    grid:        { borderColor: '#f0f0f0', strokeDashArray: 3 },
                    tooltip:     { y: { formatter: fmt2 } }
                });
                C.monthly.render();
            }

            if (!C.strategy) {
                renderStrategy(d);
            }

            if (!C.instrument) {
                renderInstrument(d);
            }
        }

        document.readyState === 'loading'
            ? document.addEventListener('DOMContentLoaded', init)
            : init();

        // Update bei Environment-/Zeitraum-Wechsel (nur einmal registrieren)
        if (!window.__dashChartsListening) {
            window.__dashChartsListening = true;
            window.addEventListener('charts-data', function (e) {
                var u = e.detail;
                renderNav(u);
                if (C.cumulative) C.cumulative.updateSeries([{ name: 'Kum. P&L', data: u.cumulative }]);
                if (C.monthly)    C.monthly.updateSeries([{ name: 'P&L', data: u.monthly }]);
                // Donut destroy+recreate: Labels, Farben und Slice-Anzahl können sich komplett ändern
                if (C.strategy) { C.strategy.destroy(); C.strategy = null; }
                renderStrategy(u);
                if (C.instrument) { C.instrument.destroy(); C.instrument = null; }
                renderInstrument(u);
            });
        }
    })();
    </script>

    <!-- Aktuelle offene Positionen -->
    @foreach($section('table') as $key => $w)
        @include($w['view'])
    @endforeach

    {{-- ── Kachel-Einstellungen Modal ─────────────────────────────────────── --}}
    @if($showWidgetSettings)
    <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);"
         wire:click.self="$set('showWidgetSettings', false)">
        <div class="modal-dialog modal-sm" style="margin-top: 60px; margin-left: auto; margin-right: 1rem;">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0"><i class="bi bi-gear"></i> Kacheln für {{ $selectedEnvironment }}</h6>
                    <button type="button" class="btn-close" wire:click="$set('showWidgetSettings', false)"></button>
                </div>
                <div class="modal-body p-3" style="max-height: 70vh; overflow-y: auto;">
                    @php $groupedWidgets = collect($widgets)->groupBy('section', true); @endphp
                    @foreach(config('dashboard_widget_sections') as $sectionKey => $sectionLabel)
                        @continue(!$groupedWidgets->has($sectionKey))
                        <div class="text-muted small text-uppercase fw-semibold mt-2 mb-1">{{ $sectionLabel }}</div>
                        @foreach($groupedWidgets[$sectionKey] as $key => $w)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       id="widget-{{ $key }}"
                                       wire:click="toggleWidget('{{ $key }}')"
                                       @checked(in_array($key, $activeWidgets, true))>
                                <label class="form-check-label small" for="widget-{{ $key }}">
                                    {{ $w['title'] }}
                                    @if(($w['requires'] ?? null) === 'nav')
                                        <span class="text-muted">(nur mit NAV-Daten)</span>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

