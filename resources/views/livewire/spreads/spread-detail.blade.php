<div>
    <x-page-header
        :title="$this->spread['opt_strategyName'] . ' auf ' . $this->spread['underlyingSymbol']"
        icon="bezier2"
        :backUrl="$backUrl">
        <x-slot:actions>
            <span class="badge bg-{{ $status === 'open' ? 'warning' : 'success' }} fs-6">
                <i class="bi bi-{{ $status === 'open' ? 'hourglass-split' : 'check-circle' }}"></i>
                {{ $status === 'open' ? 'Offen' : 'Geschlossen' }}
            </span>
        </x-slot:actions>
    </x-page-header>

    {{-- Statistiken --}}
    @if($status === 'closed')
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <x-stat-card
                    title="Gesamtgewinn/-verlust"
                    :value="number_format($this->statistics['total_profit'], 2, ',', '') . ' $'"
                    :subtitle="'Netto: ' . number_format($this->statistics['net_profit'], 2, ',', '') . ' $'"
                    :color="$this->statistics['total_profit'] >= 0 ? 'success' : 'danger'"
                    icon="graph-up" />
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Premium</div>
                        <div class="mb-1">
                            <span class="text-success fw-bold">+{{ number_format($this->statistics['total_premium_received'], 2, ',', '') }} $</span>
                            <small class="text-muted ms-1">erhalten</small>
                        </div>
                        <div>
                            <span class="text-danger fw-bold">-{{ number_format($this->statistics['total_premium_paid'], 2, ',', '') }} $</span>
                            <small class="text-muted ms-1">gezahlt</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <x-stat-card
                    title="Haltedauer"
                    :value="round($this->statistics['avg_days_held'], 0) . ' Tage'"
                    :subtitle="\Carbon\Carbon::parse($this->statistics['earliest_open'])->format('d.m.Y') . ' – ' . \Carbon\Carbon::parse($this->statistics['latest_close'])->format('d.m.Y')"
                    color="info"
                    icon="clock" />
            </div>
            <div class="col-md-3">
                <x-stat-card
                    title="Kommissionen"
                    :value="number_format($this->statistics['total_commission'], 2, ',', '') . ' $'"
                    :subtitle="$this->spread['leg_count'] . ' Legs'"
                    color="secondary"
                    icon="receipt" />
            </div>
        </div>

        @if($this->statistics['assignment_stock_profit'] != 0 || $this->assignmentTrades->isNotEmpty())
        <div class="alert alert-warning py-2 mb-4 small d-flex align-items-start gap-2">
            <i class="bi bi-arrow-left-right mt-1"></i>
            <div>
                Dieser Spread wurde per <strong>Zuteilung/Ausübung</strong> glattgestellt — die reinen Options-Legs
                ergeben <strong>{{ number_format($this->statistics['options_profit'], 2, ',', '') }} $</strong>,
                die dadurch ausgelöste Aktien-Transaktion bringt
                <strong class="{{ $this->statistics['assignment_stock_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $this->statistics['assignment_stock_profit'] >= 0 ? '+' : '' }}{{ number_format($this->statistics['assignment_stock_profit'], 2, ',', '') }} $
                </strong>
                dazu. Gesamtergebnis oben bereits inklusive.
                @foreach($this->assignmentTrades as $stk)
                    <div class="text-muted mt-1">
                        <i class="bi bi-arrow-right"></i>
                        {{ \Carbon\Carbon::parse($stk->dateTimeClose)->format('d.m.Y') }} ·
                        {{ number_format(abs($stk->quantity), 0) }} Stk. {{ $stk->buySellOpen }}→{{ $stk->buySellClose }} ·
                        <x-currency-value :value="$stk->profit" currency="USD" :colored="true" />
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <x-stat-card
                    title="Total Premium"
                    :value="number_format($this->statistics['total_premium'], 2, ',', '') . ' $'"
                    :color="$this->statistics['total_premium'] >= 0 ? 'success' : 'danger'"
                    icon="cash-stack" />
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Premium Details</div>
                        <div class="mb-1">
                            <span class="text-success fw-bold">+{{ number_format($this->statistics['premium_received'], 2, ',', '') }} $</span>
                            <small class="text-muted ms-1">erhalten</small>
                        </div>
                        <div>
                            <span class="text-danger fw-bold">-{{ number_format($this->statistics['premium_paid'], 2, ',', '') }} $</span>
                            <small class="text-muted ms-1">gezahlt</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <x-stat-card
                    title="Eröffnet am"
                    :value="\Carbon\Carbon::parse($this->statistics['earliest_date'])->format('d.m.Y')"
                    :subtitle="\Carbon\Carbon::parse($this->statistics['earliest_date'])->diffForHumans()"
                    color="warning"
                    icon="hourglass-split" />
            </div>
        </div>
    @endif

    {{-- Iron Condor Visualisierung --}}
    @if(str_contains(strtolower($this->spread['opt_strategyName']), 'iron condor') && $this->spread['leg_count'] == 4)
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-diagram-3"></i> Iron Condor Struktur</h6>
            </div>
            <div class="card-body">
                @php
                    $puts = $this->legs->where('putCall', 'P')->sortBy(fn($leg) => $leg->strike ?? 0);
                    $calls = $this->legs->where('putCall', 'C')->sortBy(fn($leg) => $leg->strike ?? 0);

                    $buySellField = $status === 'closed' ? 'buySellOpen' : 'buySell';
                    $priceField   = $status === 'closed' ? 'tradePriceOpen' : 'tradePrice';

                    $putSpread  = ['sell' => $puts->where($buySellField, 'SELL')->first(), 'buy' => $puts->where($buySellField, 'BUY')->first()];
                    $callSpread = ['sell' => $calls->where($buySellField, 'SELL')->first(), 'buy' => $calls->where($buySellField, 'BUY')->first()];
                @endphp

                <div class="row">
                    {{-- PUT Spread --}}
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white py-1">
                                <strong>PUT Spread</strong>
                            </div>
                            <div class="card-body py-2">
                                @if($putSpread['buy'] && $putSpread['buy']->hasStrike())
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <div><span class="badge bg-success">BUY</span> <strong class="ms-1">{{ number_format($putSpread['buy']->strike, 2) }}</strong></div>
                                        <small class="text-muted">{{ number_format($putSpread['buy']->$priceField, 2) }} $</small>
                                    </div>
                                @endif
                                @if($putSpread['sell'] && $putSpread['sell']->hasStrike())
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div><span class="badge bg-danger">SELL</span> <strong class="ms-1">{{ number_format($putSpread['sell']->strike, 2) }}</strong></div>
                                        <small class="text-muted">{{ number_format($putSpread['sell']->$priceField, 2) }} $</small>
                                    </div>
                                @endif
                                @if($putSpread['sell'] && $putSpread['buy'] && $putSpread['sell']->hasStrike() && $putSpread['buy']->hasStrike())
                                    <div class="mt-2 pt-2 border-top small">
                                        <strong>Width:</strong> {{ number_format($putSpread['sell']->strike - $putSpread['buy']->strike, 2) }}
                                        @if($status === 'closed')
                                            &nbsp;|&nbsp;
                                            <strong>P/L:</strong>
                                            <span class="{{ ($putSpread['sell']->profit + $putSpread['buy']->profit) >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($putSpread['sell']->profit + $putSpread['buy']->profit, 2) }} $
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- CALL Spread --}}
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white py-1">
                                <strong>CALL Spread</strong>
                            </div>
                            <div class="card-body py-2">
                                @if($callSpread['sell'] && $callSpread['sell']->hasStrike())
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <div><span class="badge bg-danger">SELL</span> <strong class="ms-1">{{ number_format($callSpread['sell']->strike, 2) }}</strong></div>
                                        <small class="text-muted">{{ number_format($callSpread['sell']->$priceField, 2) }} $</small>
                                    </div>
                                @endif
                                @if($callSpread['buy'] && $callSpread['buy']->hasStrike())
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div><span class="badge bg-success">BUY</span> <strong class="ms-1">{{ number_format($callSpread['buy']->strike, 2) }}</strong></div>
                                        <small class="text-muted">{{ number_format($callSpread['buy']->$priceField, 2) }} $</small>
                                    </div>
                                @endif
                                @if($callSpread['sell'] && $callSpread['buy'] && $callSpread['sell']->hasStrike() && $callSpread['buy']->hasStrike())
                                    <div class="mt-2 pt-2 border-top small">
                                        <strong>Width:</strong> {{ number_format($callSpread['buy']->strike - $callSpread['sell']->strike, 2) }}
                                        @if($status === 'closed')
                                            &nbsp;|&nbsp;
                                            <strong>P/L:</strong>
                                            <span class="{{ ($callSpread['sell']->profit + $callSpread['buy']->profit) >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($callSpread['sell']->profit + $callSpread['buy']->profit, 2) }} $
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if($putSpread['sell'] && $callSpread['sell'] && $putSpread['sell']->hasStrike() && $callSpread['sell']->hasStrike())
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="bi bi-graph-up"></i>
                        <strong>Profit Range:</strong>
                        {{ number_format($putSpread['sell']->strike, 2) }} – {{ number_format($callSpread['sell']->strike, 2) }}
                        &nbsp;|&nbsp;
                        <strong>Range Width:</strong> {{ number_format($callSpread['sell']->strike - $putSpread['sell']->strike, 2) }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Alle Legs --}}
    <x-trade-card title="Alle Legs ({{ $this->spread['leg_count'] }})" icon="list-ul">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                <tr>
                    <th>Symbol</th>
                    <th>Type</th>
                    <th class="text-end">Strike</th>
                    <th>Expiry</th>
                    <th>Action</th>
                    <th class="text-center">Contracts</th>
                    @if($status === 'closed')
                        <th class="text-end">Preis Open</th>
                        <th class="text-end">Preis Close</th>
                        <th class="text-end">Profit</th>
                        <th class="text-center">Tage</th>
                    @else
                        <th class="text-end">Preis</th>
                        <th class="text-end">Premium</th>
                    @endif
                    <th class="text-end">Kommission</th>
                </tr>
                </thead>
                <tbody>
                @foreach($this->legs as $leg)
                    <tr>
                        <td>
                            <strong>{{ $leg->symbol }}</strong>
                            <br><small class="text-muted">{{ $leg->underlyingSymbol }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ ($leg->putCall ?? '') === 'C' ? 'success' : 'danger' }}">
                                {{ $leg->putCall }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($leg->hasStrike())
                                <strong>{{ number_format($leg->strike, 2) }}</strong>
                            @else
                                <small class="text-muted">N/A</small>
                            @endif
                        </td>
                        <td>
                            {{ $leg->expiry?->format('d.m.Y') }}
                            @if($leg->expiry && $status === 'open')
                                <br><small class="text-muted">{{ $leg->expiry->diffForHumans() }}</small>
                            @endif
                        </td>
                        <td>
                            @if($status === 'closed')
                                <span class="badge bg-{{ $leg->buySellOpen === 'BUY' ? 'success' : 'danger' }}">{{ $leg->buySellOpen }}</span>
                            @else
                                <span class="badge bg-{{ $leg->buySell === 'BUY' ? 'success' : 'danger' }}">{{ $leg->buySell }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format(abs($leg->quantity), 0) }}</td>
                        @if($status === 'closed')
                            <td class="text-end">{{ number_format($leg->tradePriceOpen, 2) }}</td>
                            <td class="text-end">{{ number_format($leg->tradePriceClose, 2) }}</td>
                            <td class="text-end">
                                <x-currency-value :value="$leg->profit" currency="USD" :colored="true" />
                            </td>
                            <td class="text-center">{{ $leg->daysHeld }}</td>
                        @else
                            <td class="text-end">{{ number_format($leg->tradePrice, 2) }}</td>
                            <td class="text-end">
                                <x-currency-value :value="$leg->netCash" currency="USD" :colored="true" />
                            </td>
                        @endif
                        <td class="text-end"><x-currency-value :value="$leg->ibCommission" currency="USD" /></td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="{{ $status === 'closed' ? 8 : 6 }}" class="text-end">TOTAL:</td>
                    @if($status === 'closed')
                        <td class="text-end">
                            <x-currency-value :value="$this->statistics['total_profit']" currency="USD" :colored="true" />
                        </td>
                        <td></td>
                    @else
                        <td class="text-end">
                            <x-currency-value :value="$this->statistics['total_premium']" currency="USD" :colored="true" />
                        </td>
                    @endif
                    <td class="text-end">
                        <x-currency-value :value="$this->statistics['total_commission']" currency="USD" />
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </x-trade-card>

    {{-- Performance (nur Closed) --}}
    @if($status === 'closed')
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Performance</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted">ROI</dt>
                            <dd class="col-sm-7">
                                @php
                                    $totalPremiumPaid = abs($this->statistics['total_premium_paid']);
                                    $roi = $totalPremiumPaid > 0 ? ($this->statistics['total_profit'] / $totalPremiumPaid) * 100 : 0;
                                @endphp
                                <span class="{{ $roi >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($roi, 2) }}%
                                </span>
                            </dd>
                            <dt class="col-sm-5 text-muted">Profit / Tag</dt>
                            <dd class="col-sm-7">
                                {{ number_format($this->statistics['total_profit'] / max($this->statistics['avg_days_held'], 1), 2) }} $
                            </dd>
                            <dt class="col-sm-5 text-muted">Max Risk</dt>
                            <dd class="col-sm-7">
                                @php
                                    $maxRisk = 0;
                                    if($this->spread['leg_count'] == 4) {
                                        $putsWithStrike  = $this->legs->where('putCall', 'P')->filter(fn($l) => $l->hasStrike());
                                        $callsWithStrike = $this->legs->where('putCall', 'C')->filter(fn($l) => $l->hasStrike());
                                        if($putsWithStrike->count() >= 2 && $callsWithStrike->count() >= 2) {
                                            $putWidth  = abs($putsWithStrike->max('strike')  - $putsWithStrike->min('strike'));
                                            $callWidth = abs($callsWithStrike->max('strike') - $callsWithStrike->min('strike'));
                                            $maxRisk   = max($putWidth, $callWidth) * 100 * abs($this->legs->first()->quantity);
                                        }
                                    }
                                @endphp
                                {{ number_format($maxRisk, 2) }} $
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted">Strategie</dt>
                            <dd class="col-sm-7">{{ $this->spread['opt_strategyName'] }}</dd>
                            <dt class="col-sm-5 text-muted">Underlying</dt>
                            <dd class="col-sm-7">{{ $this->spread['underlyingSymbol'] }}</dd>
                            <dt class="col-sm-5 text-muted">Legs</dt>
                            <dd class="col-sm-7">{{ $this->spread['leg_count'] }}</dd>
                            <dt class="col-sm-5 text-muted">Strategy ID</dt>
                            <dd class="col-sm-7"><code class="small">{{ $opt_strategyID }}</code></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Psychologie --}}
    @include('livewire.psychology.partials.entry-summary-card', [
        'psychEntry'     => $psychEntry,
        'psychCreateUrl' => route('psychology.create') . '?opt_strategyID=' . $opt_strategyID
            . '&trade_type=' . ($status === 'open' ? 'open_spread' : 'spread')
            . '&symbol=' . ($this->spread['underlyingSymbol'] ?? ''),
    ])

</div>
