<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('spreads.list') }}?filterStatus={{ $status }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
            </a>
            <h1>
                <i class="bi bi-bezier2"></i>
                {{ $this->spread['opt_strategyName'] }}
                <span class="text-muted">auf {{ $this->spread['underlyingSymbol'] }}</span>
            </h1>
        </div>

        <!-- Environment Filter -->
        <div class="btn-group" role="group">
            @foreach($environments as $env)
                <button
                    type="button"
                    class="btn btn-{{ $selectedEnvironment === $env ? 'primary' : 'outline-primary' }}"
                    wire:click="changeEnvironment('{{ $env }}')"
                    onclick="window.dispatchEvent(new CustomEvent('environment-changed', { detail: { environment: '{{ $env }}' } }))">
                    {{ $env }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Spread Übersicht -->
    @if($status === 'closed')
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Gesamtgewinn/-verlust</h6>
                        <h3 class="text-{{ $this->statistics['total_profit'] >= 0 ? 'success' : 'danger' }}">
                            {{ number_format($this->statistics['total_profit'], 2) }} $
                        </h3>
                        <small class="text-muted">Netto: {{ number_format($this->statistics['net_profit'], 2) }} $</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Premium</h6>
                        <div>
                            <span class="text-success">+{{ number_format($this->statistics['total_premium_received'], 2) }} $</span>
                            <small class="text-muted">erhalten</small>
                        </div>
                        <div>
                            <span class="text-danger">-{{ number_format($this->statistics['total_premium_paid'], 2) }} $</span>
                            <small class="text-muted">gezahlt</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Haltedauer</h6>
                        <h3>{{ round($this->statistics['avg_days_held'], 0) }} Tage</h3>
                        <small class="text-muted">
                            {{ \Carbon\Carbon::parse($this->statistics['earliest_open'])->format('d.m.Y') }} -
                            {{ \Carbon\Carbon::parse($this->statistics['latest_close'])->format('d.m.Y') }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Kommissionen</h6>
                        <h3>{{ number_format($this->statistics['total_commission'], 2) }} $</h3>
                        <small class="text-muted">{{ $this->spread['leg_count'] }} Legs</small>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Total Premium</h6>
                        <h3 class="text-{{ $this->statistics['total_premium'] >= 0 ? 'success' : 'danger' }}">
                            {{ number_format($this->statistics['total_premium'], 2) }} $
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Premium Details</h6>
                        <div>
                            <span class="text-success">{{ number_format($this->statistics['premium_received'], 2) }} $</span>
                            <small class="text-muted">erhalten</small>
                        </div>
                        <div>
                            <span class="text-danger">{{ number_format($this->statistics['premium_paid'], 2) }} $</span>
                            <small class="text-muted">gezahlt</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Eröffnet am</h6>
                        <h3>{{ \Carbon\Carbon::parse($this->statistics['earliest_date'])->format('d.m.Y') }}</h3>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($this->statistics['earliest_date'])->diffForHumans() }}</small>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Iron Condor Visualisierung -->
    @if(str_contains(strtolower($this->spread['opt_strategyName']), 'iron condor') && $this->spread['leg_count'] == 4)
        <div class="card mb-4 border-purple">
            <div class="card-header bg-purple text-white">
                <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Iron Condor Struktur</h5>
            </div>
            <div class="card-body">
                @php
                    $puts = $this->legs->where('putCall', 'PUT')->sortBy(function($leg) {
                        return $leg->strike ?? 0;
                    });
                    $calls = $this->legs->where('putCall', 'CALL')->sortBy(function($leg) {
                        return $leg->strike ?? 0;
                    });

                    $buySellField = $status === 'closed' ? 'buySellOpen' : 'buySell';
                    $priceField = $status === 'closed' ? 'tradePriceOpen' : 'tradePrice';

                    $putSpread = [
                        'sell' => $puts->where($buySellField, 'SELL')->first(),
                        'buy' => $puts->where($buySellField, 'BUY')->first(),
                    ];

                    $callSpread = [
                        'sell' => $calls->where($buySellField, 'SELL')->first(),
                        'buy' => $calls->where($buySellField, 'BUY')->first(),
                    ];
                @endphp

                <div class="row">
                    <!-- Put Spread (links) -->
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <strong>PUT Spread</strong>
                            </div>
                            <div class="card-body">
                                @if($putSpread['buy'] && $putSpread['buy']->hasStrike())
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <div>
                                            <span class="badge bg-success">BUY</span>
                                            <strong class="ms-2">{{ number_format($putSpread['buy']->strike, 2) }}</strong>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                {{ number_format($putSpread['buy']->$priceField, 2) }} $
                                            </small>
                                        </div>
                                    </div>
                                @endif

                                @if($putSpread['sell'] && $putSpread['sell']->hasStrike())
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-danger">SELL</span>
                                            <strong class="ms-2">{{ number_format($putSpread['sell']->strike, 2) }}</strong>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                {{ number_format($putSpread['sell']->$priceField, 2) }} $
                                            </small>
                                        </div>
                                    </div>
                                @endif

                                @if($putSpread['sell'] && $putSpread['buy'] && $putSpread['sell']->hasStrike() && $putSpread['buy']->hasStrike())
                                    <div class="mt-3 pt-3 border-top">
                                        <strong>Width:</strong>
                                        {{ number_format($putSpread['sell']->strike - $putSpread['buy']->strike, 2) }}
                                        @if($status === 'closed')
                                            <br><strong>P/L:</strong>
                                            <span class="text-{{ ($putSpread['sell']->profit + $putSpread['buy']->profit) >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($putSpread['sell']->profit + $putSpread['buy']->profit, 2) }} $
                                </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Call Spread (rechts) -->
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <strong>CALL Spread</strong>
                            </div>
                            <div class="card-body">
                                @if($callSpread['sell'] && $callSpread['sell']->hasStrike())
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <div>
                                            <span class="badge bg-danger">SELL</span>
                                            <strong class="ms-2">{{ number_format($callSpread['sell']->strike, 2) }}</strong>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                {{ number_format($callSpread['sell']->$priceField, 2) }} $
                                            </small>
                                        </div>
                                    </div>
                                @endif

                                @if($callSpread['buy'] && $callSpread['buy']->hasStrike())
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-success">BUY</span>
                                            <strong class="ms-2">{{ number_format($callSpread['buy']->strike, 2) }}</strong>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                {{ number_format($callSpread['buy']->$priceField, 2) }} $
                                            </small>
                                        </div>
                                    </div>
                                @endif

                                @if($callSpread['sell'] && $callSpread['buy'] && $callSpread['sell']->hasStrike() && $callSpread['buy']->hasStrike())
                                    <div class="mt-3 pt-3 border-top">
                                        <strong>Width:</strong>
                                        {{ number_format($callSpread['buy']->strike - $callSpread['sell']->strike, 2) }}
                                        @if($status === 'closed')
                                            <br><strong>P/L:</strong>
                                            <span class="text-{{ ($callSpread['sell']->profit + $callSpread['buy']->profit) >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($callSpread['sell']->profit + $callSpread['buy']->profit, 2) }} $
                                </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Iron Condor Profit Range -->
                @if($putSpread['sell'] && $callSpread['sell'] && $putSpread['sell']->hasStrike() && $callSpread['sell']->hasStrike())
                    <div class="alert alert-info mt-3 mb-0">
                        <strong><i class="bi bi-graph-up"></i> Profit Range:</strong>
                        Zwischen {{ number_format($putSpread['sell']->strike, 2) }} und {{ number_format($callSpread['sell']->strike, 2) }}
                        <span class="ms-3">
                    <strong>Range Width:</strong> {{ number_format($callSpread['sell']->strike - $putSpread['sell']->strike, 2) }}
                </span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Alle Legs im Detail -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Alle Legs ({{ $this->spread['leg_count'] }})</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Type</th>
                        <th class="text-end">Strike</th>
                        <th>Expiry</th>
                        <th>Action</th>
                        <th class="text-end">Contracts</th>
                        @if($status === 'closed')
                            <th class="text-end">Price Open</th>
                            <th class="text-end">Price Close</th>
                            <th class="text-end">Profit</th>
                            <th class="text-center">Days</th>
                        @else
                            <th class="text-end">Price</th>
                            <th class="text-end">Premium</th>
                        @endif
                        <th class="text-end">Commission</th>
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
                                <span class="badge bg-{{ $leg->putCall === 'CALL' ? 'success' : 'danger' }}">
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
                                    <span class="badge bg-{{ $leg->buySellOpen === 'BUY' ? 'success' : 'danger' }}">
                                        {{ $leg->buySellOpen }}
                                    </span>
                                @else
                                    <span class="badge bg-{{ $leg->buySell === 'BUY' ? 'success' : 'danger' }}">
                                        {{ $leg->buySell }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format(abs($leg->quantity), 0) }}</td>
                            @if($status === 'closed')
                                <td class="text-end">{{ number_format($leg->tradePriceOpen, 2) }}</td>
                                <td class="text-end">{{ number_format($leg->tradePriceClose, 2) }}</td>
                                <td class="text-end">
                                    <strong class="text-{{ $leg->profit >= 0 ? 'success' : 'danger' }}">
                                        {{ number_format($leg->profit, 2) }} $
                                    </strong>
                                </td>
                                <td class="text-center">{{ $leg->daysHeld }}</td>
                            @else
                                <td class="text-end">{{ number_format($leg->tradePrice, 2) }}</td>
                                <td class="text-end">
                                    <strong class="text-{{ $leg->netCash >= 0 ? 'success' : 'danger' }}">
                                        {{ number_format($leg->netCash, 2) }} $
                                    </strong>
                                </td>
                            @endif
                            <td class="text-end">{{ number_format($leg->ibCommission, 2) }} $</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot class="table-light">
                    <tr>
                        <td colspan="{{ $status === 'closed' ? '8' : '6' }}" class="text-end">
                            <strong>TOTAL:</strong>
                        </td>
                        @if($status === 'closed')
                            <td class="text-end">
                                <strong class="text-{{ $this->statistics['total_profit'] >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($this->statistics['total_profit'], 2) }} $
                                </strong>
                            </td>
                            <td></td>
                        @else
                            <td class="text-end">
                                <strong class="text-{{ $this->statistics['total_premium'] >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($this->statistics['total_premium'], 2) }} $
                                </strong>
                            </td>
                        @endif
                        <td class="text-end">
                            <strong>{{ number_format($this->statistics['total_commission'], 2) }} $</strong>
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Trade Notes Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-journal-text"></i> Spread Notes & Analysis</h5>
        </div>
        <div class="card-body">
            @livewire('notes.note-manager', [
                'strategyID' => $strategyID,
                'tradeType' => 'spread'
            ])
        </div>
    </div>
    <!-- Trade Tags Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-tags"></i> Trade Tags</h5>
        </div>
        <div class="card-body">
            @livewire('tags.trade-tagger', [
                'tradeId' => $strategyID,
                'tradeType' => 'spread'
            ])
        </div>
    </div>
    <!-- Spread Tags Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-tags"></i> Spread Tags</h5>
        </div>
        <div class="card-body">
            @livewire('tags.trade-tagger', [
                'tradeId' => $strategyID,
                'tradeType' => 'spread'
            ])
        </div>
    </div>

    <!-- Performance Analysis -->
    @if($status === 'closed')
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Performance Analysis</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Performance Metriken</h6>
                        <ul>
                            <li>
                                <strong>ROI:</strong>
                                @php
                                    $totalPremiumPaid = abs($this->statistics['total_premium_paid']);
                                    $roi = $totalPremiumPaid > 0 ? ($this->statistics['total_profit'] / $totalPremiumPaid) * 100 : 0;
                                @endphp
                                <span class="text-{{ $roi >= 0 ? 'success' : 'danger' }}">
                                {{ number_format($roi, 2) }}%
                            </span>
                            </li>
                            <li>
                                <strong>Profit pro Tag:</strong>
                                {{ number_format($this->statistics['total_profit'] / max($this->statistics['avg_days_held'], 1), 2) }} $
                            </li>
                            <li>
                                <strong>Max Risk:</strong>
                                @php
                                    $maxRisk = 0;
                                    if($this->spread['leg_count'] == 4) {
                                        $putsWithStrike = $this->legs->where('putCall', 'PUT')->filter(fn($l) => $l->hasStrike());
                                        $callsWithStrike = $this->legs->where('putCall', 'CALL')->filter(fn($l) => $l->hasStrike());

                                        if($putsWithStrike->count() >= 2 && $callsWithStrike->count() >= 2) {
                                            $putWidth = abs($putsWithStrike->max('strike') - $putsWithStrike->min('strike'));
                                            $callWidth = abs($callsWithStrike->max('strike') - $callsWithStrike->min('strike'));
                                            $maxRisk = max($putWidth, $callWidth) * 100 * abs($this->legs->first()->quantity);
                                        }
                                    }
                                @endphp
                                {{ number_format($maxRisk, 2) }} $
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Trade Details</h6>
                        <ul>
                            <li><strong>Strategie:</strong> {{ $this->spread['opt_strategyName'] }}</li>
                            <li><strong>Underlying:</strong> {{ $this->spread['underlyingSymbol'] }}</li>
                            <li><strong>Legs:</strong> {{ $this->spread['leg_count'] }}</li>
                            <li><strong>Strategy ID:</strong> <code>{{ $strategyID }}</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
