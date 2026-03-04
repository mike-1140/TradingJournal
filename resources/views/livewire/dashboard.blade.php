<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="mb-0">Trading Dashboard</h1>
            <small class="text-muted">Analyse & Statistik</small>
        </div>

        <div class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label class="d-block small text-muted mb-1">Environment:</label>
                <div class="btn-group" role="group">
                    @foreach ($environments as $env)
                        <button wire:click="changeEnvironment('{{ $env }}')"
                                class="btn btn-sm btn-{{ $selectedEnvironment === $env ? 'primary' : 'outline-primary' }}">
                            {{ $env }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="btn-group btn-group-sm">
                @foreach($users as $u)
                    <button class="btn btn-{{ auth()->id() === $u->id ? 'primary' : 'outline-primary' }}" wire:click="switchUser({{ $u->id }})">
                        {{ $u->name }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Statistik-Karten -->
    <div class="row g-4 mb-4">
        <!-- Open Trades -->
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-hourglass-split"></i> Open Trades</h5>
                    <h2>{{ number_format($openTradesCount) }}</h2>
                </div>
            </div>
        </div>
        <!-- Closed Trades -->
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-check-circle"></i> Closed Trades</h5>
                    <h2>{{ number_format($closedTradesCount) }}</h2>
                </div>
            </div>
        </div>
        <!-- Total Profit -->
        <div class="col-md-3">
            <div class="card {{ $totalProfit >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-currency-dollar"></i> Total P/L</h5>
                    <h2>{{ number_format($totalProfit, 2) }} $</h2>
                    <small>Commissions: {{ number_format($totalCommissions, 2) }} $</small>
                </div>
            </div>
        </div>
        <!-- Win Rate -->
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-percent"></i> Win Rate</h5>
                    <h2>{{ $winRate }}%</h2>
                    <small>Ø {{ $avgDaysHeld }} Tage</small>
                </div>
            </div>
        </div>
        <!-- Spreads KPI Card -->
        <div class="col-md-3">
            <div class="card border-purple">
                <div class="card-body">
                    <h6 class="text-muted"><i class="bi bi-bezier2"></i> Spreads</h6>
                    <h2>{{ number_format($spreadsCount) }}</h2>
                    <small class="text-muted">
                        Profit:
                        <span class="text-{{ $spreadsProfit >= 0 ? 'success' : 'danger' }}">
                    {{ number_format($spreadsProfit, 2) }} $
                </span>
                    </small><br>
                    <a href="{{ route('spreads.list') }}" class="btn btn-sm btn-outline-purple mt-2">
                        Details <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Tag Statistics -->
    {{-- DEAKTIVIERT für Option C
    <div class="row mb-4">
        <div class="col-12">
            @livewire('tags.tag-statistics')
        </div>
    </div>
    --}}

    <div class="row g-4 mb-4">
        <!-- Top Symbole -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> Top 5 Symbole</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>Symbol</th>
                            <th class="text-end">Trades</th>
                            <th class="text-end">Profit</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($topSymbols as $item)
                            <tr>
                                <td><strong>{{ $item->symbol }}</strong></td>
                                <td class="text-end">{{ $item->trade_count }}</td>
                                <td class="text-end text-{{ $item->total_profit >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($item->total_profit, 2) }} $
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Keine Daten</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Performance nach Strategie -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Strategien Performance</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>Strategie</th>
                            <th class="text-end">Trades</th>
                            <th class="text-end">Profit</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($strategyPerformance as $item)
                            <tr>
                                <td>{{ $item->strategy ?: 'N/A' }}</td>
                                <td class="text-end">{{ $item->trade_count }}</td>
                                <td class="text-end text-{{ $item->total_profit >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($item->total_profit, 2) }} $
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Keine Daten</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Letzte geschlossene Trades -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Letzte geschlossene Trades</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Open</th>
                        <th>Close</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Tage</th>
                        <th class="text-end">Profit</th>
                        <th class="text-end">ROI</th>
                        <th>Closed By</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentClosedTrades as $trade)
                        <tr>
                            <td>
                                <strong>{{ $trade->symbol }}</strong>
                                @if($trade->putCall)
                                    <br><small class="text-muted">{{ $trade->putCall }}</small>
                                @endif
                            </td>
                            <td>{{ $trade->dateTimeOpen?->format('d.m.Y') }}</td>
                            <td>{{ $trade->dateTimeClose?->format('d.m.Y') }}</td>
                            <td class="text-end">{{ number_format($trade->quantity, 0) }}</td>
                            <td class="text-end">{{ $trade->daysHeld }}</td>
                            <td class="text-end">
                                <strong class="text-{{ $trade->profit >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($trade->profit, 2) }} $
                                </strong>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-{{ $trade->ROI >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($trade->ROI, 2) }}%
                                </span>
                            </td>
                            <td><small>{{ $trade->closedBy ?: '-' }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">Keine geschlossenen Trades</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Performing Spreads Section -->
    @if($spreadsCount > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-purple">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-trophy"></i> Top 5 Performing Spreads</h5>
                        <a href="{{ route('spreads.list') }}" class="btn btn-sm btn-outline-purple">
                            Alle Spreads <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                <tr>
                                    <th>Strategie</th>
                                    <th class="text-end">Profit</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($topSpreads as $spread)
                                    <tr>
                                        <td>
                                            <strong>{{ $spread->opt_strategyName }}</strong>
                                            @if(str_contains(strtolower($spread->opt_strategyName), 'iron condor'))
                                                <span class="badge bg-purple ms-2">IC</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-{{ $spread->spread_profit >= 0 ? 'success' : 'danger' }}">
                                                {{ number_format($spread->spread_profit, 2) }} $
                                            </strong>
                                        </td>
                                        <td>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

