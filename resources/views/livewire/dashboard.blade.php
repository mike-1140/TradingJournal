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

            {{-- User Dropdown --}}
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
        </div>
    </div>

    <!-- KPI-Boxen: Fixe Perioden + Zeitraum -->
    <div class="row g-3 mb-3">
        {{-- P&L Monat --}}
        <div class="col-md-4">
            <x-stat-card
                title="Brutto P&L {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}"
                :value="'$ ' . number_format($monthProfit, 2)"
                icon="calendar-check"
                :color="$monthProfit >= 0 ? 'success' : 'danger'"
                :trend="$monthTrend['direction']"
                :trend-value="$monthTrend['label']"
                :subtitle="'Netto: $ ' . number_format($monthNetProfit, 2)"
            />
        </div>
        {{-- P&L YTD --}}
        <div class="col-md-4">
            <x-stat-card
                title="Brutto P&L YTD {{ \Carbon\Carbon::now()->year }}"
                :value="'$ ' . number_format($ytdProfit, 2)"
                icon="calendar-range"
                :color="$ytdProfit >= 0 ? 'success' : 'danger'"
                :subtitle="'Netto: $ ' . number_format($ytdNetProfit, 2)"
            />
        </div>
        {{-- P&L Zeitraum (zeitraumgefiltert) --}}
        <div class="col-md-4">
            <x-stat-card
                title="Brutto P&L Zeitraum"
                :value="'$ ' . number_format($totalProfit, 2)"
                icon="graph-up-arrow"
                :color="$totalProfit >= 0 ? 'success' : 'danger'"
                :subtitle="'Netto: $ ' . number_format($periodNetProfit, 2) . ' · ' . $zeitraumLabel . ' · ' . number_format($closedTradesCount) . ' Trades'"
            />
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Win Rate --}}
        <div class="col-md-4">
            <x-stat-card
                title="Win Rate"
                :value="$winRate . '%'"
                icon="trophy"
                :color="$winRate >= 50 ? 'success' : 'warning'"
                :subtitle="number_format($closedTradesCount) . ' Trades · Ø ' . $avgDaysHeld . ' Tage'"
            />
        </div>
        {{-- Ø P&L pro Trade --}}
        <div class="col-md-4">
            <x-stat-card
                title="Ø Brutto P&L / Trade"
                :value="'$ ' . number_format($avgProfitPerTrade, 2)"
                icon="bar-chart-line"
                :color="$avgProfitPerTrade >= 0 ? 'success' : 'danger'"
                :subtitle="'Ø Netto: $ ' . number_format($avgNetProfitPerTrade, 2) . ' · Komm.: $ ' . number_format($totalCommissions, 2)"
            />
        </div>
        {{-- Offene Kampagnen --}}
        <div class="col-md-4">
            <x-stat-card
                title="Offene Kampagnen"
                :value="number_format($activeCampaignsCount)"
                icon="briefcase"
                color="primary"
                :subtitle="'Kapital gebunden: $ ' . number_format($capitalAtRisk, 0)"
            />
        </div>
    </div>
    <!-- NAV KPI-Karten (nur wenn Snapshot-Daten vorhanden) -->
    @if($navCurrent !== null)
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <x-stat-card
                title="NAV aktuell"
                :value="'€ ' . number_format($navCurrent, 2, ',', '.')"
                icon="wallet2"
                color="primary"
                :subtitle="'Stand: ' . $navDate"
            />
        </div>
        <div class="col-md-4">
            <x-stat-card
                title="NAV Änderung (Tag)"
                :value="($navChange >= 0 ? '+' : '') . '€ ' . number_format($navChange, 2, ',', '.')"
                icon="arrow-up-down"
                :color="$navChange >= 0 ? 'success' : 'danger'"
            />
        </div>
        <div class="col-md-4">
            <x-stat-card
                title="TWRR"
                :value="number_format($navTwrr, 2, ',', '.') . ' %'"
                icon="percent"
                :color="$navTwrr >= 0 ? 'success' : 'danger'"
                subtitle="Time-Weighted Rate of Return"
            />
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <x-stat-card
                title="Cash EUR"
                :value="'€ ' . number_format($navCashEur ?? 0, 2, ',', '.')"
                icon="cash-coin"
                color="secondary"
                :subtitle="'Stand: ' . $navDate"
            />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Cash USD"
                :value="'$ ' . number_format($navCashUsd ?? 0, 2, ',', '.')"
                icon="currency-dollar"
                color="secondary"
                :subtitle="'Stand: ' . $navDate"
            />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Realisiert (Tag)"
                :value="($navRealizedPnl >= 0 ? '+' : '') . '€ ' . number_format($navRealizedPnl ?? 0, 2, ',', '.')"
                icon="check-circle"
                :color="($navRealizedPnl ?? 0) >= 0 ? 'success' : 'danger'"
            />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Unrealisiert (Tag)"
                :value="($navUnrealizedPnl >= 0 ? '+' : '') . '€ ' . number_format($navUnrealizedPnl ?? 0, 2, ',', '.')"
                icon="hourglass-split"
                :color="($navUnrealizedPnl ?? 0) >= 0 ? 'success' : 'danger'"
            />
        </div>
    </div>
    @endif

    <!-- NAV-Kurve (immer im DOM, Sichtbarkeit per CSS) -->
    <div class="row g-3 mb-3 {{ $navCurrent !== null ? '' : 'd-none' }}">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-graph-up"></i> NAV-Verlauf</h6>
                    <small class="text-muted">Account-Wert täglich</small>
                </div>
                <div class="card-body p-2">
                    <div id="chart-nav" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts: Kumulativer P&L + Strategie-Donut -->
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Kumulativer P&L {{ now()->year }}</h6>
                    <small class="text-muted">monatlich</small>
                </div>
                <div class="card-body p-2">
                    <div id="chart-cumulative" wire:ignore></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-pie-chart"></i> P&L nach Spreads</h6>
                    <small class="text-muted">{{ $zeitraumLabel }}</small>
                </div>
                <div class="card-body p-2">
                    @if(count($chartStrategyValues) > 0)
                        <div id="chart-strategy" wire:ignore></div>
                    @else
                        <div class="d-flex align-items-center justify-content-center text-muted" style="height:260px">
                            <div class="text-center">
                                <i class="bi bi-pie-chart fs-1"></i>
                                <p class="mt-2 small">Keine positiven Spreads im Zeitraum</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Monatlicher P&L -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Monatlicher P&L</h6>
                    <small class="text-muted">letzte 12 Monate</small>
                </div>
                <div class="card-body p-2">
                    <div id="chart-monthly" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var C = window.__dashCharts = window.__dashCharts || {};

        var d = {
            cumulative:     @json($chartCumulative),
            monthly:        @json($chartMonthly),
            strategyLabels: @json($chartStrategyLabels),
            strategyValues: @json($chartStrategyValues),
            strategyActual: @json($chartStrategyActual),
            nav:            @json($chartNav)
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
                markers:    { size: 5, strokeWidth: 0 },
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
            });
        }
    })();
    </script>

    <!-- Tag Statistics -->
    {{-- DEAKTIVIERT für Option C
    <div class="row mb-4">
        <div class="col-12">
            @livewire('tags.tag-statistics')
        </div>
    </div>
    --}}

    <!-- Aktuelle offene Positionen -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Aktuelle offene Positionen</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Typ</th>
                        <th>Einstieg</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Preis</th>
                        <th class="text-end">NetCash</th>
                        <th>Strategie</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($currentOpenTrades as $trade)
                        <tr>
                            <td>
                                <strong>{{ $trade->symbol }}</strong>
                                @if($trade->putCall)
                                    <br><small class="text-muted">
                                        {{ $trade->strike }} {{ $trade->putCall }}
                                        @if($trade->expiry)
                                            | {{ $trade->expiry->format('d.m.Y') }}
                                        @endif
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $trade->assetCategory }}</span>
                            </td>
                            <td>{{ $trade->dateTime?->format('d.m.Y') }}</td>
                            <td class="text-end">{{ number_format($trade->quantity, 0) }}</td>
                            <td class="text-end">{{ number_format($trade->tradePrice, 2) }}</td>
                            <td class="text-end">{{ number_format($trade->netCash, 2) }}</td>
                            <td><small>{{ $trade->strategy ?: '-' }}</small></td>
                            <td>
                                <span class="badge bg-primary">{{ $trade->status ?: 'OPEN' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">Keine offenen Trades</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

