<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">
            <i class="bi bi-globe me-2 text-info"></i>
            Symbol Analyse: {{ $symbol }} – über alle Kampagnen hinweg
        </h5>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-info text-dark">{{ $metrics->campaign_count }} Kampagnen</span>
            <button wire:click="$dispatch('clear-search')" class="btn btn-sm btn-outline-light">
                Schließen
            </button>
        </div>
    </div>

    {{-- Metriken --}}
    <div class="card-body bg-light border-bottom">
        <div class="row g-3 text-center row-cols-2 row-cols-md-auto justify-content-md-evenly">
            <div class="col">
                <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Aktien-Bestand</small>
                <span class="fs-5 fw-bold">{{ number_format($metrics->shares_balance ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="col">
                <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Optionen-Bestand</small>
                <span class="fs-5 fw-bold">{{ number_format($metrics->options_balance ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="col">
                <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Net Cash</small>
                <x-currency-value :value="$metrics->total_netcash ?? 0" :currency="'USD'" class="fs-5" />
            </div>
            <div class="col">
                <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Proceeds</small>
                <x-currency-value :value="$metrics->total_proceeds ?? 0" :currency="'USD'" class="fs-5" />
            </div>
            <div class="col">
                <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Commission</small>
                <x-currency-value :value="$metrics->total_commission ?? 0" :currency="'USD'" class="fs-5" />
            </div>
            <div class="col">
                <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Brutto PnL</small>
                <x-currency-value :value="$metrics->total_pnl ?? 0" :currency="'USD'" class="fs-5" :colored="true" />
            </div>
            <div class="col">
                <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Netto PnL</small>
                <x-currency-value :value="$metrics->total_net_pnl ?? 0" :currency="'USD'" class="fs-5 fw-bold" :colored="true" />
            </div>
        </div>
    </div>

    {{-- P&L-Verlauf --}}
    @if(count($chartData) > 2)
    <div class="card-body border-bottom py-2 px-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <small class="fw-semibold text-muted text-uppercase" style="font-size: 0.7rem;">
                <i class="bi bi-graph-up me-1 text-primary"></i>Kumulierter P&L-Verlauf – alle Kampagnen
            </small>
            <small class="text-muted" style="font-size: 0.7rem;">{{ count($chartData) - 1 }} Ereignistage</small>
        </div>
        <canvas id="globalPnlChart" style="height: 160px; max-height: 160px;"></canvas>
    </div>
    @endif

    {{-- Tabelle --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light sticky-top" style="z-index: 1;">
                <tr class="text-uppercase text-muted" style="font-size: 0.7rem;">
                    <th style="width: 110px;">Datum & Zeit</th>
                    <th style="width: 140px;">Ereignis</th>
                    <th>Beschreibung</th>
                    <th class="text-end" style="width: 80px;">Δ Menge</th>
                    <th class="text-end" style="width: 110px;">Net Cash</th>
                    <th class="text-end" style="width: 100px;">Proceeds</th>
                    <th class="text-end" style="width: 100px;">Commission</th>
                    <th class="text-end" style="width: 95px;">Brutto PnL</th>
                    <th class="text-end" style="width: 95px;">Netto PnL</th>
                    <th class="text-end" style="width: 90px;">
                        <i class="bi bi-stack" title="Laufender Aktienbestand"></i> Bestand
                    </th>
                </tr>
            </thead>
            <tbody>

                @php
                    $currentDateKey      = null;
                    $runningStockBalance = 0;
                @endphp

                @forelse($ledgerEntries as $entry)
                    @php
                        $entryDt  = \Carbon\Carbon::parse($entry->dateTime);
                        $dateKey  = $entryDt->format('Y-m-d');
                        $isStk    = $entry->assetCategory === 'STK';
                        $deltaPos = (float) $entry->inventory_delta > 0;
                        $deltaNeg = (float) $entry->inventory_delta < 0;
                        $narrative = $this->buildNarrative($entry);
                        if ($isStk) {
                            $runningStockBalance += (float) $entry->inventory_delta;
                        }
                    @endphp

                    {{-- Tages-Trennzeile --}}
                    @if($dateKey !== $currentDateKey)
                        @php $currentDateKey = $dateKey; @endphp
                        <tr class="date-separator">
                            <td colspan="10">
                                <i class="bi bi-calendar3 me-1"></i>
                                {{ $entryDt->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
                            </td>
                        </tr>
                    @endif

                    <tr class="ledger-row {{ $deltaPos ? 'row-buy' : ($deltaNeg ? 'row-sell' : '') }}">
                        <td class="text-muted text-nowrap" style="font-size: 0.75rem;">
                            {{ $entryDt->format('H:i') }}
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
                            <x-currency-value :value="$entry->netcash" :currency="$entry->currency" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$entry->proceeds" :currency="$entry->currency" />
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
                        <td class="text-end fw-bold">
                            @php $netPnlGlobal = (float) $entry->realized_pnl + (float) $entry->commission; @endphp
                            @if($netPnlGlobal != 0)
                                <x-currency-value :value="$netPnlGlobal" :currency="$entry->currency" :colored="true" />
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
                        <td colspan="10" class="text-center text-muted py-5">
                            Keine Buchungen für diesen Zeitraum.
                        </td>
                    </tr>
                @endforelse

            </tbody>

            @if($ledgerEntries->count() > 0)
                <tfoot>
                    @if($ledgerEntries->lastPage() > 1)
                        <tr class="table-light fw-bold" style="font-size: 0.8rem;">
                            <td colspan="4" class="text-muted">
                                Seite {{ $ledgerEntries->currentPage() }} von {{ $ledgerEntries->lastPage() }}:
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$ledgerEntries->sum('netcash')" :currency="'USD'" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$ledgerEntries->sum('proceeds')" :currency="'USD'" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$ledgerEntries->sum('commission')" :currency="'USD'" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$ledgerEntries->sum('realized_pnl')" :currency="'USD'" :colored="true" />
                            </td>
                            <td class="text-end fw-bold">
                                @php $pageNetPnlGlobal = $ledgerEntries->sum('realized_pnl') + $ledgerEntries->sum('commission'); @endphp
                                <x-currency-value :value="$pageNetPnlGlobal" :currency="'USD'" :colored="true" />
                            </td>
                            <td></td>
                        </tr>
                    @endif
                    <tr class="table-dark" style="font-size: 0.8rem;">
                        <td colspan="4" class="text-uppercase">Gesamtergebnis aller Kampagnen:</td>
                        <td class="text-end">
                            <x-currency-value :value="$metrics->total_netcash ?? 0" :currency="'USD'" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$metrics->total_proceeds ?? 0" :currency="'USD'" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$metrics->total_commission ?? 0" :currency="'USD'" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$metrics->total_pnl ?? 0" :currency="'USD'" :colored="true" />
                        </td>
                        <td class="text-end fw-bold">
                            <x-currency-value :value="$metrics->total_net_pnl ?? 0" :currency="'USD'" :colored="true" />
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

    @assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    @endassets

    @script
    <script>
        (function () {
            let chart = null;

            function buildChart() {
                if (chart) { chart.destroy(); chart = null; }

                const canvas = document.getElementById('globalPnlChart');
                if (!canvas) return;

                const raw = @json($chartData);
                if (raw.length < 3) return;

                const labels = raw.map(d => d.label);
                const cumuls = raw.map(d => d.cumul);
                const final  = cumuls[cumuls.length - 1];
                const color  = final >= 0 ? '#198754' : '#dc3545';
                const fill   = final >= 0 ? 'rgba(25,135,84,0.08)' : 'rgba(220,53,69,0.08)';

                chart = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Kumulativer P&L',
                            data: cumuls,
                            borderColor: color,
                            backgroundColor: fill,
                            fill: true,
                            stepped: true,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => {
                                        const v = ctx.parsed.y;
                                        return ` P&L: ${v >= 0 ? '+' : ''}${v.toFixed(2)} $`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { ticks: { font: { size: 10 }, maxRotation: 45 } },
                            y: {
                                ticks: { font: { size: 10 }, callback: v => v.toFixed(0) + ' $' },
                                grid: {
                                    color: ctx => ctx.tick.value === 0
                                        ? 'rgba(0,0,0,0.25)'
                                        : 'rgba(0,0,0,0.05)'
                                }
                            }
                        }
                    }
                });
            }

            buildChart();

            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => queueMicrotask(buildChart));
            });
        })();
    </script>
    @endscript

    <style>
        tr.date-separator td {
            background-color: #f0f4f8;
            color: #495057;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 6px 12px;
            border-top: 2px solid #dee2e6;
            letter-spacing: 0.02em;
        }
        .table tbody tr.row-buy td  { background-color: rgba(13, 110, 253, 0.04) !important; }
        .table tbody tr.row-sell td { background-color: rgba(255, 193, 7, 0.05) !important; }
        .table-hover tbody tr.row-buy:hover td,
        .table-hover tbody tr.row-sell:hover td {
            background-color: rgba(0, 0, 0, 0.06) !important;
        }
        .narrative-text {
            font-size: 0.85rem;
            color: #212529;
        }
    </style>
</div>