<div>
    @php
        $pageTitle = match(true) {
            $filterStrategyGroup === 'verticals' => 'Vertical Spreads',
            $filterStrategy === 'Iron Condor'    => 'Iron Condors',
            default                              => 'Options Spreads',
        };
    @endphp
    <x-page-header
        :title="$pageTitle"
        icon="bezier2">
        @if($filterStrategy || $filterStrategyGroup)
        <x-slot:actions>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button"
                        class="btn btn-{{ $filterStatus === 'open' ? 'warning' : 'outline-warning' }}"
                        wire:click="$set('filterStatus', 'open')">
                    <i class="bi bi-hourglass-split"></i> Offen
                </button>
                <button type="button"
                        class="btn btn-{{ $filterStatus === 'closed' ? 'success' : 'outline-success' }}"
                        wire:click="$set('filterStatus', 'closed')">
                    <i class="bi bi-check-circle"></i> Geschlossen
                </button>
            </div>
        </x-slot:actions>
        @endif
    </x-page-header>

    <x-date-range-info :startDate="$this->startDate" :endDate="$this->endDate" />

    {{-- Statistiken --}}
    @if($filterStatus === 'closed')
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <x-stat-card
                    title="Anzahl Spreads"
                    :value="number_format($statistics['total_spreads'])"
                    :subtitle="$statistics['winning_spreads'] . ' Gewinner / ' . $statistics['losing_spreads'] . ' Verlierer'"
                    color="primary"
                    icon="bezier2" />
            </div>
            <div class="col-md-3">
                <x-stat-card
                    title="Brutto Gewinn/-verlust"
                    :value="number_format($statistics['total_profit'], 2, ',', '') . ' $'"
                    :color="$statistics['total_profit'] >= 0 ? 'success' : 'danger'"
                    icon="graph-up" />
            </div>
            <div class="col-md-3">
                <x-stat-card
                    title="Win Rate"
                    :value="$statistics['win_rate'] . '%'"
                    color="info"
                    icon="trophy" />
            </div>
            <div class="col-md-3">
                <x-stat-card
                    title="Ø Brutto / Spread"
                    :value="number_format($statistics['avg_profit'], 2, ',', '') . ' $'"
                    :color="$statistics['avg_profit'] >= 0 ? 'success' : 'danger'"
                    icon="calculator" />
            </div>
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <x-stat-card
                    title="Offene Spreads"
                    :value="number_format($statistics['total_spreads'])"
                    color="warning"
                    icon="hourglass-split" />
            </div>
            <div class="col-md-6">
                <x-stat-card
                    title="Total Premium"
                    :value="number_format($statistics['total_premium'], 2, ',', '') . ' $'"
                    :color="$statistics['total_premium'] >= 0 ? 'success' : 'danger'"
                    icon="cash-stack" />
            </div>
        </div>
    @endif

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-end gap-3">
                <div>
                    <label class="form-label form-label-sm mb-1">Suche Underlying</label>
                    <input type="text" class="form-control form-control-sm"
                           placeholder="SPY, AAPL, TSLA..."
                           wire:model.live.debounce.300ms="search">
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1">Strategie</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStrategy">
                        <option value="">Alle Strategien</option>
                        @foreach($strategies as $strat)
                            <option value="{{ $strat }}">{{ $strat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1">Pro Seite</label>
                    <select class="form-select form-select-sm" wire:model.live="perPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="ms-auto">
                    <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabelle --}}
    <x-trade-card :title="($filterStatus === 'closed' ? 'Geschlossene' : 'Offene') . ' Spreads'" icon="list-ul">
        <x-slot:actions>
            <span class="badge bg-primary">{{ $spreads->total() }}</span>
        </x-slot:actions>

        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                <tr>
                    <th>Strategie</th>
                    <th>Underlying</th>
                    <th>Strikes</th>
                    <th>Expiry</th>
                    <th>Eröffnet</th>
                    @if($filterStatus === 'closed')
                        <th>Geschlossen</th>
                        <th class="text-end">Profit (SELL / BUY)</th>
                        <th class="text-center">% of max</th>
                        <th class="text-end">Netto</th>
                        <th class="text-center">Ø Tage</th>
                    @else
                        <th class="text-end">Premium (SELL / BUY)</th>
                        <th class="text-end">Max P / 50% / Max L</th>
                        <th class="text-end">Netto</th>
                    @endif
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($spreads as $spread)
                    <tr>
                        <td><strong>{{ $spread->opt_strategyName }}</strong></td>
                        <td><strong class="fs-5">{{ $spread->underlyingSymbol }}</strong></td>
                        <td>
                            @php
                                $parts = [];
                                if ($spread->put_strikes) $parts[] = '<span class="text-danger">P: ' . $spread->put_strikes . '</span>';
                                if ($spread->call_strikes) $parts[] = '<span class="text-success">C: ' . $spread->call_strikes . '</span>';
                            @endphp
                            <span class="font-monospace small">{!! implode('<br>', $parts) !!}</span>
                            @if($filterStatus === 'closed' && $spread->spread_width && (!$spread->put_strikes || !$spread->call_strikes))
                                <br><small class="text-muted">{{ (int)$spread->spread_width }}-wide</small>
                            @endif
                        </td>
                        <td>
                            @if($spread->expiry)
                                @php $expiry = \Carbon\Carbon::parse($spread->expiry); @endphp
                                {{ $expiry->format('d.m.Y') }}
                                @if($filterStatus === 'open')
                                    @php $dte = (int) now()->startOfDay()->diffInDays($expiry->startOfDay(), false); @endphp
                                    @if($dte > 0)
                                        <br><small class="text-muted"><strong>{{ $dte }}</strong> DTE</small>
                                    @elseif($dte === 0)
                                        <br><small class="text-warning fw-bold">0 DTE</small>
                                    @else
                                        <br><small class="text-danger">abgelaufen</small>
                                    @endif
                                @endif
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($spread->earliest_date)->format('d.m.Y') }}</td>
                        @if($filterStatus === 'closed')
                            <td>{{ \Carbon\Carbon::parse($spread->latest_date)->format('d.m.Y') }}</td>
                            <td class="text-end">
                                <strong class="{{ $spread->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($spread->total_profit, 2, ',', '') }} $
                                </strong>
                                @if(isset($spread->assignment_stock_profit))
                                    <i class="bi bi-info-circle text-warning"
                                       title="Inkl. Aktien-Zuteilung: {{ $spread->assignment_stock_profit >= 0 ? '+' : '' }}{{ number_format($spread->assignment_stock_profit, 2, ',', '') }} $ — reine Options-Legs: {{ number_format($spread->total_profit - $spread->assignment_stock_profit, 2, ',', '') }} $"></i>
                                @endif
                                <br>
                                <small class="text-muted">
                                    <span class="text-success">{{ number_format($spread->profit_sell, 2, ',', '') }}</span>
                                    /
                                    <span class="text-danger">{{ number_format($spread->profit_buy, 2, ',', '') }}</span>
                                </small>
                            </td>
                            <td class="text-center">
                                @php
                                    $pctOfMax = null;
                                    if ($spread->net_credit_open > 0) {
                                        // Credit Spread (Bull Put, Bear Call, Iron Condor)
                                        $pctOfMax = round($spread->total_profit / $spread->net_credit_open * 100);
                                    } elseif ($spread->net_credit_open < 0 && $spread->spread_width && $spread->multiplier) {
                                        // Debit Spread (Bull Call, Bear Put)
                                        $maxProfit = ($spread->spread_width * $spread->multiplier) + $spread->net_credit_open;
                                        if ($maxProfit > 0) {
                                            $pctOfMax = round($spread->total_profit / $maxProfit * 100);
                                        }
                                    }
                                @endphp
                                @if($pctOfMax !== null)
                                    @php $badgeColor = $pctOfMax >= 50 ? 'success' : ($pctOfMax >= 0 ? 'warning' : 'danger'); @endphp
                                    <span class="badge bg-{{ $badgeColor }}">{{ $pctOfMax }}%</span>
                                @else
                                    <small class="text-muted">—</small>
                                @endif
                            </td>
                            <td class="text-end">
                                @php $netto = $spread->total_profit + $spread->total_commission; @endphp
                                <x-currency-value :value="$netto" currency="USD" :colored="true" />
                            </td>
                            <td class="text-center">{{ round($spread->avg_days_held, 0) }}</td>
                        @else
                            <td class="text-end">
                                <strong class="{{ $spread->total_premium >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($spread->total_premium, 2, ',', '') }} $
                                </strong>
                                <br>
                                <small class="text-muted">
                                    <span class="text-success">{{ number_format($spread->premium_sell, 2, ',', '') }}</span>
                                    /
                                    <span class="text-danger">{{ number_format($spread->premium_buy, 2, ',', '') }}</span>
                                </small>
                            </td>
                            <td class="text-end">
                                @php
                                    $isPure = !$spread->put_strikes || !$spread->call_strikes;
                                    $maxProfit = null;
                                    $maxLoss   = null;
                                    if ($isPure && $spread->spread_width && $spread->multiplier) {
                                        $width = $spread->spread_width * $spread->multiplier;
                                        if ($spread->total_premium > 0) {
                                            $maxProfit = $spread->total_premium;
                                            $maxLoss   = $width - $spread->total_premium;
                                        } elseif ($spread->total_premium < 0) {
                                            $maxProfit = $width + $spread->total_premium;
                                            $maxLoss   = abs($spread->total_premium);
                                        }
                                    }
                                @endphp
                                @if($maxProfit !== null && $maxProfit > 0)
                                    <small class="text-success">Max {{ number_format($maxProfit, 0, ',', '') }} $</small>
                                    <br><strong class="text-success">50% {{ number_format($maxProfit * 0.5, 0, ',', '') }} $</strong>
                                    <br><small class="text-danger">Loss {{ number_format($maxLoss, 0, ',', '') }} $</small>
                                @elseif($spread->total_premium > 0)
                                    <span class="text-success fw-bold">
                                        {{ number_format($spread->total_premium * 0.5, 2, ',', '') }} $
                                    </span>
                                @else
                                    <small class="text-muted">—</small>
                                @endif
                            </td>
                            <td class="text-end">
                                @php $netto = $spread->total_premium + $spread->total_commission; @endphp
                                <x-currency-value :value="$netto" currency="USD" :colored="true" />
                            </td>
                        @endif
                        <td>
                            <a href="{{ route('spreads.detail', ['opt_strategyID' => $spread->opt_strategyID]) }}?status={{ $filterStatus }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $filterStatus === 'closed' ? 11 : 9 }}">
                            <x-empty-state
                                icon="bezier2"
                                title="Keine Spreads gefunden"
                                message="Für den gewählten Zeitraum und Filter wurden keine Spreads gefunden." />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            {{ $spreads->links('components.pagination') }}
        </x-slot:footer>
    </x-trade-card>
</div>
