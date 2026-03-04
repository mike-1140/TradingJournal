<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-bezier2"></i> Options Spreads</h1>

        <div class="d-flex gap-3 align-items-center">
            <!-- Status Filter -->
            <div>
                <label class="me-2 small fw-bold">Status:</label>
                <div class="btn-group" role="group">
                    <button
                        type="button"
                        class="btn btn-sm btn-{{ $filterStatus === 'open' ? 'warning' : 'outline-warning' }}"
                        wire:click="$set('filterStatus', 'open')">
                        <i class="bi bi-hourglass-split"></i> Open
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-{{ $filterStatus === 'closed' ? 'success' : 'outline-success' }}"
                        wire:click="$set('filterStatus', 'closed')">
                        <i class="bi bi-check-circle"></i> Closed
                    </button>
                </div>
            </div>

            <!-- Environment Filter -->
            <div>
                <label class="me-2 small fw-bold">Environment:</label>
                <div class="btn-group" role="group">
                    @foreach($environments as $env)
                        <button
                            type="button"
                            class="btn btn-sm btn-{{ $selectedEnvironment === $env ? 'primary' : 'outline-primary' }}"
                            wire:click="changeEnvironment('{{ $env }}')"
                            onclick="window.dispatchEvent(new CustomEvent('environment-changed', { detail: { environment: '{{ $env }}' } }))">
                            {{ $env }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="text-muted small mt-1">
        @if($this->startDate)
            <i class="bi bi-calendar3"></i>
            Daten seit dem {{ $this->startDate->format('d.m.Y') }}
        @else
            <i class="bi bi-calendar3"></i>
            Alle historischen Daten
        @endif
    </div>

    <!-- Statistiken -->
    @if($filterStatus === 'closed')
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Anzahl Spreads</h6>
                        <h3>{{ number_format($statistics['total_spreads']) }}</h3>
                        <small class="text-success">{{ $statistics['winning_spreads'] }} Gewinner</small> |
                        <small class="text-danger">{{ $statistics['losing_spreads'] }} Verlierer</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Gesamtgewinn/-verlust</h6>
                        <h3>
                            <x-currency-value :value="$statistics['total_profit']" :colored="true" />
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Win Rate</h6>
                        <h3>{{ $statistics['win_rate'] }}%</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Ø Profit pro Spread</h6>
                        <h3>
                            <x-currency-value :value="($statistics['total_profit'] / $statistics['total_spreads'])" :colored="true" />
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Statistiken für OPEN -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Offene Spreads</h6>
                        <h3>{{ number_format($statistics['total_spreads']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Total Premium</h6>
                        <h3 class="text-{{ $statistics['total_premium'] >= 0 ? 'success' : 'danger' }}">
                            {{ number_format($statistics['total_premium'], 2) }} $
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Suche Underlying</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="SPY, AAPL, TSLA..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Strategie</label>
                    <select class="form-select" wire:model.live="filterStrategy">
                        <option value="">Alle Strategien</option>
                        @foreach($strategies as $strat)
                            <option value="{{ $strat }}">{{ $strat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pro Seite</label>
                    <select class="form-select" wire:model.live="perPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Spreads Liste -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Strategie</th>
                        <th>Underlying</th>
                        <th class="text-center">Legs</th>
                        <th>Datum</th>
                        @if($filterStatus === 'closed')
                            <th class="text-end">Profit</th>
                            <th class="text-center">Tage</th>
                        @else
                            <th class="text-end">Premium</th>
                        @endif
                        <th class="text-end">Kommission</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($spreads as $spread)
                        <tr>
                            <td>
                                <strong>{{ $spread->opt_strategyName }}</strong>
                                @if(str_contains(strtolower($spread->opt_strategyName), 'iron condor'))
                                    <span class="badge bg-purple ms-2">IC</span>
                                @elseif(str_contains(strtolower($spread->opt_strategyName), 'vertical'))
                                    <span class="badge bg-info ms-2">VS</span>
                                @endif
                            </td>
                            <td><strong class="fs-5">{{ $spread->underlyingSymbol }}</strong></td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $spread->leg_count }} Legs</span>
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($spread->earliest_date)->format('d.m.Y') }}
                                @if($filterStatus === 'closed')
                                    <br><small class="text-muted">bis {{ \Carbon\Carbon::parse($spread->latest_date)->format('d.m.Y') }}</small>
                                @endif
                            </td>
                            @if($filterStatus === 'closed')
                                <td class="text-end">
                                    <strong class="text-{{ $spread->total_profit >= 0 ? 'success' : 'danger' }}">
                                        {{ number_format($spread->total_profit, 2) }} $
                                    </strong>
                                </td>
                                <td class="text-center">
                                    {{ round($spread->avg_days_held, 0) }}
                                </td>
                            @else
                                <td class="text-end">
                                    <strong class="text-{{ $spread->total_premium >= 0 ? 'success' : 'danger' }}">
                                        {{ number_format($spread->total_premium, 2) }} $
                                    </strong>
                                </td>
                            @endif
                            <td class="text-end">{{ number_format($spread->total_commission, 2) }} $</td>
                            @if($spread->strategyID)
                                <td>
                                    <a href="{{ route('spreads.detail', ['strategyID' => $spread->strategyID]) }}?status={{ $filterStatus }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Details
                                    </a>

                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Keine Spreads gefunden
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white">
                {{ $spreads->links('components.pagination') }}
            </div>
        </div>
    </div>
</div>
