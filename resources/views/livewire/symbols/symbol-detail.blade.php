<div>
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="mb-0 fw-bold">
                {{ $symbol }}
                @if($latestCampaign)
                    <span class="badge fs-6 ms-2
                        bg-{{ $latestCampaign->status === 'ACTIVE' ? 'success' : ($latestCampaign->status === 'PENDING' ? 'warning text-dark' : 'secondary') }}">
                        {{ $latestCampaign->status }}
                    </span>
                @endif
            </h2>
            @if($metrics && $metrics->first_trade)
                <small class="text-muted">
                    <i class="bi bi-calendar3"></i>
                    {{ \Carbon\Carbon::parse($metrics->first_trade)->format('d.m.Y') }}
                    &nbsp;–&nbsp;
                    {{ \Carbon\Carbon::parse($metrics->last_trade)->format('d.m.Y') }}
                </small>
            @endif
        </div>
        <a href="{{ route('symbols') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
    </div>

    <x-date-range-info :startDate="$this->startDate" :endDate="$this->endDate" />

    @if($campaigns->isEmpty())
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle me-2"></i>
            Keine Kampagnen für <strong>{{ $symbol }}</strong> im aktuellen Environment gefunden.
        </div>
    @else

    {{-- Summary-Kacheln --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md">
            <div class="card h-100 text-center">
                <div class="card-body py-3">
                    <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem;">Kampagnen</small>
                    <span class="fs-4 fw-bold">{{ $campaigns->count() }}</span>
                    @if($closedCount > 0)
                        <div class="text-muted" style="font-size: 0.75rem;">{{ $closedCount }} geschlossen</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card h-100 text-center">
                <div class="card-body py-3">
                    <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem;">Brutto P&L</small>
                    <x-currency-value :value="$metrics->total_pnl ?? 0" :currency="'USD'" class="fs-4 fw-bold" :colored="true" />
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card h-100 text-center">
                <div class="card-body py-3">
                    <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem;">Kommission</small>
                    <x-currency-value :value="$metrics->total_commission ?? 0" :currency="'USD'" class="fs-4" />
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card h-100 text-center border-start border-primary border-3">
                <div class="card-body py-3">
                    <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem;">Netto P&L</small>
                    <x-currency-value :value="$metrics->total_net_pnl ?? 0" :currency="'USD'" class="fs-4 fw-bold" :colored="true" />
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card h-100 text-center">
                <div class="card-body py-3">
                    <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem;">Win Rate</small>
                    @if($winRate !== null)
                        <span class="fs-4 fw-bold text-{{ $winRate >= 50 ? 'success' : 'danger' }}">
                            {{ $winRate }}%
                        </span>
                        <div class="text-muted" style="font-size: 0.75rem;">{{ $winCount }}/{{ $closedCount }} Kampagnen</div>
                    @else
                        <span class="text-muted fs-5">—</span>
                        <div class="text-muted" style="font-size: 0.75rem;">Keine geschl. Kampagnen</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- P&L-Verlauf Chart --}}
    @if(count($chartData) > 2)
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Kumulativer P&L-Verlauf</h6>
            <small class="text-muted">Alle Kampagnen · realisierter P&L</small>
        </div>
        <div class="card-body py-2 px-3">
            <canvas id="symbolChart" style="height: 200px; max-height: 200px;"></canvas>
        </div>
    </div>
    @endif

    {{-- Kampagnen-Tabelle --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-currency-exchange me-2 text-secondary"></i>Kampagnen</h6>
            <span class="badge bg-secondary">{{ $campaigns->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                <thead class="table-light">
                    <tr class="text-uppercase text-muted" style="font-size: 0.68rem;">
                        <th>#</th>
                        <th>Zeitraum</th>
                        <th class="text-center">Dauer</th>
                        <th class="text-center">Buchungen</th>
                        <th class="text-end">Brutto P&L</th>
                        <th class="text-end">Kommission</th>
                        <th class="text-end">Netto P&L</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" title="Trade Log">Log</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaigns as $i => $campaign)
                        @php
                            $stats    = $campaignStats->get($campaign->id);
                            $pnl      = (float) ($stats->total_pnl      ?? 0);
                            $comm     = (float) ($stats->total_commission ?? 0);
                            $netPnl   = (float) ($stats->total_net_pnl   ?? 0);
                            $count    = (int)   ($stats->ledger_count    ?? 0);

                            $start    = \Carbon\Carbon::parse($campaign->start_date);
                            $endDate  = $campaign->end_date
                                            ? \Carbon\Carbon::parse($campaign->end_date)
                                            : \Carbon\Carbon::now();
                            $days     = $start->diffInDays($endDate);

                            $endLabel = match($campaign->status) {
                                'ACTIVE'  => '<span class="text-success fw-bold">laufend</span>',
                                'PENDING' => '<span class="text-warning fw-bold">wartend</span>',
                                default   => $campaign->end_date
                                    ? \Carbon\Carbon::parse($campaign->end_date)->format('d.m.Y')
                                    : '—',
                            };
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td class="text-nowrap">
                                {{ $start->format('d.m.Y') }}
                                <span class="text-muted mx-1">–</span>
                                {!! $endLabel !!}
                            </td>
                            <td class="text-center text-muted">{{ $days }}d</td>
                            <td class="text-center">
                                @if($count > 0)
                                    <span class="badge bg-light text-dark border">{{ $count }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($count > 0)
                                    <x-currency-value :value="$pnl" :currency="'USD'" :colored="true" />
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($count > 0)
                                    <x-currency-value :value="$comm" :currency="'USD'" />
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">
                                @if($count > 0)
                                    <x-currency-value :value="$netPnl" :currency="'USD'" :colored="true" />
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge
                                    bg-{{ $campaign->status === 'ACTIVE' ? 'success' : ($campaign->status === 'PENDING' ? 'warning text-dark' : 'secondary') }}"
                                    style="font-size: 0.7rem;">
                                    {{ $campaign->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($loggedCampaignIds->contains($campaign->id))
                                    <i class="bi bi-journal-check text-success" title="Dokumentiert"></i>
                                @else
                                    <i class="bi bi-journal text-muted" title="Nicht dokumentiert" style="opacity: 0.4;"></i>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('campaigns.campaign.detail', $campaign->id) }}"
                                   class="btn btn-outline-secondary btn-sm py-0 px-2"
                                   title="Kampagne öffnen">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                @if($campaigns->count() > 1)
                <tfoot>
                    <tr class="table-light fw-bold" style="font-size: 0.82rem;">
                        <td colspan="4" class="text-muted text-uppercase">Gesamt</td>
                        <td class="text-end">
                            <x-currency-value :value="$metrics->total_pnl ?? 0" :currency="'USD'" :colored="true" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$metrics->total_commission ?? 0" :currency="'USD'" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$metrics->total_net_pnl ?? 0" :currency="'USD'" :colored="true" />
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    @endif

    @if(count($chartData) > 2)
    @assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    @endassets

    @script
    <script>
        (function () {
            let chart = null;

            function buildChart() {
                if (chart) { chart.destroy(); chart = null; }

                const canvas = document.getElementById('symbolChart');
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
    @endif
</div>
