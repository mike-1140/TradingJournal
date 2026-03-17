<div>
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="mb-0 fw-bold">
                {{ $campaign->symbol }}
                <span class="text-muted fw-normal fs-5 ms-2">{{ $campaign->name }}</span>
            </h2>
            <small class="text-muted">
                <i class="bi bi-calendar3"></i>
                Eröffnet am {{ $campaign->start_date->format('d.m.Y') }}
                @if($campaign->status === 'CLOSED' && $campaign->closed_at)
                    &nbsp;·&nbsp; Geschlossen am {{ \Carbon\Carbon::parse($campaign->closed_at)->format('d.m.Y') }}
                @endif
            </small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge fs-6 bg-{{ $campaign->status === 'ACTIVE' ? 'success' : ($campaign->status === 'PENDING' ? 'warning text-dark' : 'secondary') }}">
                {{ $campaign->status }}
            </span>
            <a href="{{ route('campaigns.campaigns') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </div>
    </div>

    {{-- Metriken --}}
    <div class="card mb-4 border-start border-primary border-4 border-top-0 border-end-0 border-bottom-0">
        <div class="card-body bg-light">
            <div class="row g-3 text-center row-cols-2 row-cols-md-5">
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Aktien-Bestand</small>
                    <span class="fs-5 fw-bold">{{ number_format($metrics->shares_balance ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Optionen-Bestand</small>
                    <span class="fs-5 fw-bold">{{ number_format($metrics->options_balance ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Cashflow</small>
                    <x-currency-value :value="$metrics->total_cashflow ?? 0" :currency="'USD'" class="fs-5" />
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Commission</small>
                    <x-currency-value :value="$metrics->total_commission ?? 0" :currency="'USD'" class="fs-5" />
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Gesamt PnL</small>
                    <x-currency-value :value="$metrics->total_pnl ?? 0" :currency="'USD'" class="fs-5" :colored="true" />
                </div>
            </div>
        </div>
    </div>

    {{-- Cashflow-Chart --}}
    @if(count($chartData) > 0)
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Cashflow-Verlauf</h6>
            <small class="text-muted">Balken = je Buchung · Linie = kumulativer Saldo</small>
        </div>
        <div class="card-body py-2 px-3">
            <canvas id="cashflowChart" style="height: 190px; max-height: 190px;"></canvas>
        </div>
    </div>
    @endif

    {{-- Tages-Übersicht --}}
    @if($dailySummaries->count() > 0)
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0"><i class="bi bi-calendar3-week me-2 text-secondary"></i>Tages-Übersicht</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" style="font-size: 0.82rem;">
                <thead class="table-light">
                    <tr class="text-uppercase text-muted" style="font-size: 0.68rem;">
                        <th>Datum</th>
                        <th class="text-center">Buchungen</th>
                        <th class="text-end">Cashflow</th>
                        <th class="text-end">Commission</th>
                        <th class="text-end">PnL</th>
                        <th>Ereignisse</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailySummaries as $day)
                    <tr>
                        <td class="fw-bold text-nowrap">
                            {{ $day['date']->locale('de')->isoFormat('dd, D.M.YY') }}
                        </td>
                        <td class="text-center text-muted">{{ $day['count'] }}</td>
                        <td class="text-end">
                            <x-currency-value :value="$day['cashflow']" :currency="'USD'" :colored="true" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$day['commission']" :currency="'USD'" />
                        </td>
                        <td class="text-end">
                            @if($day['pnl'] != 0)
                                <x-currency-value :value="$day['pnl']" :currency="'USD'" :colored="true" />
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($day['has_settlement'])
                                <span class="badge bg-info text-dark" style="font-size: 0.65rem;">Settlement</span>
                            @endif
                            @if($day['has_expiration'])
                                <span class="badge bg-secondary" style="font-size: 0.65rem;">Expiration</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Ledger-Tabelle --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">
                <i class="bi bi-clock-history me-2"></i>Transaktions-Historie
            </h5>
            @if($ledgerEntries)
                <span class="badge bg-secondary">{{ $ledgerEntries->total() }} Buchungen</span>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light sticky-top" style="z-index: 1;">
                    <tr class="text-uppercase text-muted" style="font-size: 0.7rem;">
                        <th style="width: 55px;">Zeit</th>
                        <th style="width: 140px;">Ereignis</th>
                        <th>Beschreibung</th>
                        <th class="text-end" style="width: 80px;">Δ Menge</th>
                        <th class="text-end" style="width: 110px;">Cashflow</th>
                        <th class="text-end" style="width: 100px;">Commission</th>
                        <th class="text-end" style="width: 100px;">PnL</th>
                        <th class="text-end" style="width: 90px;">
                            <i class="bi bi-stack" title="Laufender Aktienbestand"></i> Bestand
                        </th>
                    </tr>
                </thead>
                <tbody>

                    @php
                        $currentDateKey      = null;
                        $runningStockBalance = $startingStockBalance;

                        // linkID-Farbzuweisung: mehrfach vorkommende linkIDs bekommen eine Farbe
                        $linkIdColors  = [];
                        $colorPalette  = ['#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#17a2b8'];
                        $colorIdx      = 0;
                        foreach ($ledgerEntries->getCollection()->groupBy('linkID') as $lid => $group) {
                            if ($lid && $group->count() > 1) {
                                $linkIdColors[$lid] = $colorPalette[$colorIdx % count($colorPalette)];
                                $colorIdx++;
                            }
                        }
                    @endphp

                    @forelse($ledgerEntries as $entry)
                        @php
                            $entryDt   = \Carbon\Carbon::parse($entry->dateTime);
                            $dateKey   = $entryDt->format('Y-m-d');
                            $isStk     = $entry->assetCategory === 'STK';

                            if ($isStk) {
                                $runningStockBalance += (float) $entry->inventory_delta;
                            }

                            $linkColor  = ($entry->linkID && isset($linkIdColors[$entry->linkID]))
                                            ? $linkIdColors[$entry->linkID]
                                            : null;
                            $narrative  = $this->buildNarrative($entry);
                            $deltaPos   = (float) $entry->inventory_delta > 0;
                            $deltaNeg   = (float) $entry->inventory_delta < 0;

                            // Effektiver Cashflow: bei CASH_SETTLEMENT proceeds nehmen, sonst netcash
                            $effectiveCashflow = $entry->action === 'CASH_SETTLEMENT'
                                ? $entry->proceeds
                                : $entry->netcash;
                        @endphp

                        {{-- Tages-Trennzeile --}}
                        @if($dateKey !== $currentDateKey)
                            @php $currentDateKey = $dateKey; @endphp
                            <tr class="date-separator">
                                <td colspan="8">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    {{ $entryDt->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
                                </td>
                            </tr>
                        @endif

                        {{-- Buchungszeile --}}
                        <tr class="ledger-row {{ $deltaPos ? 'row-buy' : ($deltaNeg ? 'row-sell' : '') }}"
                            style="border-left: 3px solid {{ $linkColor ?? 'transparent' }};">
                            <td class="text-muted text-nowrap" style="font-size: 0.75rem;">
                                {{ $entryDt->format('H:i') }}
                                @if($linkColor)
                                    <span class="link-dot" style="background: {{ $linkColor }};"></span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $this->getActionBadgeClass($entry->action) }}"
                                      style="font-size: 0.7rem;">
                                    {{ $entry->action }}
                                </span>
                            </td>
                            <td class="narrative-text">{{ $narrative }}</td>
                            <td class="text-end fw-bold {{ $deltaPos ? 'text-primary' : ($deltaNeg ? 'text-warning' : 'text-muted') }}">
                                {{ $deltaPos ? '+' : '' }}{{ number_format((float) $entry->inventory_delta, 0, ',', '.') }}
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$effectiveCashflow" :currency="$entry->currency" />
                                @if($entry->action === 'CASH_SETTLEMENT')
                                    <br><small class="text-muted" style="font-size: 0.7rem;">Settlement</small>
                                @endif
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$entry->commission" :currency="$entry->currency" />
                            </td>
                            <td class="text-end fw-bold">
                                @if((float) $entry->realized_pnl != 0)
                                    <x-currency-value :value="$entry->realized_pnl" :currency="$entry->currency" :colored="true" />
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($isStk)
                                    <span class="fw-bold {{ $runningStockBalance != 0 ? 'text-dark' : 'text-muted' }}">
                                        {{ number_format($runningStockBalance, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 0.78rem;">
                                        {{ number_format($runningStockBalance, 0, ',', '.') }}
                                    </span>
                                @endif
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                Keine Buchungen für diesen Zeitraum.
                            </td>
                        </tr>
                    @endforelse

                </tbody>

                @if($ledgerEntries->count() > 0)
                    <tfoot>
                        @if($ledgerEntries->lastPage() > 1)
                            <tr class="table-light fw-bold" style="font-size: 0.8rem;">
                                <td colspan="3" class="text-muted">
                                    Seite {{ $ledgerEntries->currentPage() }} von {{ $ledgerEntries->lastPage() }}:
                                </td>
                                <td></td>
                                <td class="text-end">
                                    @php
                                        $pageCashflow = $ledgerEntries->getCollection()->sum(
                                            fn($e) => $e->action === 'CASH_SETTLEMENT' ? $e->proceeds : $e->netcash
                                        );
                                    @endphp
                                    <x-currency-value :value="$pageCashflow" :currency="'USD'" />
                                </td>
                                <td class="text-end">
                                    <x-currency-value :value="$ledgerEntries->sum('commission')" :currency="'USD'" />
                                </td>
                                <td class="text-end">
                                    <x-currency-value :value="$ledgerEntries->sum('realized_pnl')" :currency="'USD'" :colored="true" />
                                </td>
                                <td></td>
                            </tr>
                        @endif
                        <tr class="table-dark" style="font-size: 0.8rem;">
                            <td colspan="3" class="text-uppercase">Gesamtergebnis Kampagne:</td>
                            <td></td>
                            <td class="text-end">
                                <x-currency-value :value="$metrics->total_cashflow ?? 0" :currency="'USD'" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$metrics->total_commission ?? 0" :currency="'USD'" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$metrics->total_pnl ?? 0" :currency="'USD'" :colored="true" />
                            </td>
                            <td class="text-end">
                                <small class="fw-normal opacity-75">{{ $ledgerEntries->total() }} Buchungen</small>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($ledgerEntries->hasPages())
            <div class="card-footer bg-white">
                {{ $ledgerEntries->links('components.pagination') }}
            </div>
        @endif
    </div>

    @assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    @endassets

    @script
    <script>
        (function () {
            let chart = null;

            function buildChart() {
                if (chart) { chart.destroy(); chart = null; }

                const canvas = document.getElementById('cashflowChart');
                if (!canvas) return;

                const raw = @json($chartData);
                if (!raw.length) return;

                const labels     = raw.map(d => d.label);
                const deltas     = raw.map(d => d.delta);
                const cumuls     = raw.map(d => d.cumul);
                const barColors  = deltas.map(v => v >= 0
                    ? 'rgba(25, 135, 84, 0.75)'
                    : 'rgba(220, 53, 69, 0.75)');

                chart = new Chart(canvas, {
                    data: {
                        labels,
                        datasets: [
                            {
                                type: 'bar',
                                label: 'Cashflow je Buchung',
                                data: deltas,
                                backgroundColor: barColors,
                                borderColor: barColors,
                                yAxisID: 'yDelta',
                                order: 2,
                            },
                            {
                                type: 'line',
                                label: 'Kumulativer Saldo',
                                data: cumuls,
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13,110,253,0.07)',
                                fill: true,
                                tension: 0.3,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                yAxisID: 'yCumul',
                                order: 1,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { font: { size: 11 }, boxWidth: 12 }
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => {
                                        const v = ctx.parsed.y;
                                        return ` ${ctx.dataset.label}: ${v >= 0 ? '+' : ''}${v.toFixed(2)} $`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { font: { size: 10 }, maxRotation: 45, maxTicksLimit: 20 }
                            },
                            yDelta: {
                                type: 'linear',
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                ticks: { font: { size: 10 }, callback: v => v.toFixed(0) + ' $' }
                            },
                            yCumul: {
                                type: 'linear',
                                position: 'left',
                                ticks: { font: { size: 10 }, callback: v => v.toFixed(0) + ' $' }
                            }
                        }
                    }
                });
            }

            buildChart();
            document.addEventListener('livewire:update', buildChart);
        })();
    </script>
    @endscript

    <style>
        /* Tages-Trennzeile */
        tr.date-separator td {
            background-color: #f0f4f8;
            color: #495057;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 6px 12px;
            border-top: 2px solid #dee2e6;
            letter-spacing: 0.02em;
        }

        /* Zeileneinfärbung kaufen/verkaufen */
        .table tbody tr.row-buy td  { background-color: rgba(13, 110, 253, 0.04) !important; }
        .table tbody tr.row-sell td { background-color: rgba(255, 193, 7, 0.05) !important; }
        .table-hover tbody tr.row-buy:hover td,
        .table-hover tbody tr.row-sell:hover td {
            background-color: rgba(0, 0, 0, 0.06) !important;
        }

        /* Narrativ-Text */
        .narrative-text {
            font-size: 0.85rem;
            color: #212529;
        }

        /* linkID Punkt-Indikator */
        .link-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-left: 3px;
            vertical-align: middle;
        }
    </style>
</div>
