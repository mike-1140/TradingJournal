<div>
    <x-page-header
        title="Offene Options-Positionen"
        subtitle="Offene Positionen"
        icon="graph-up">
    </x-page-header>

    {{-- Statistiken --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <x-stat-card
                title="Anzahl Options Positionen"
                :value="number_format($totalTrades)"
                color="primary"
                icon="hash" />
        </div>
        <div class="col-md-4">
            <x-stat-card
                title="Total Premium"
                :value="number_format($totalPremium, 2, ',', '') . ' $'"
                :color="$totalPremium >= 0 ? 'success' : 'danger'"
                icon="graph-up" />
        </div>
        <div class="col-md-4">
            <x-stat-card
                title="Gesamte Kommissionen"
                :value="number_format($totalCommissions, 2, ',', '') . ' $'"
                color="secondary"
                icon="receipt" />
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-end gap-3">
                <div>
                    <label class="form-label form-label-sm mb-1">Suche</label>
                    <input type="text" class="form-control form-control-sm"
                           placeholder="Symbol, Underlying..."
                           wire:model.live.debounce.300ms="search">
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1">Put/Call</label>
                    <select class="form-select form-select-sm" wire:model.live="filterPutCall">
                        <option value="">Alle</option>
                        <option value="CALL">CALL</option>
                        <option value="PUT">PUT</option>
                    </select>
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1">Strategie</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStrategy">
                        <option value="">Alle</option>
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
    <x-trade-card title="offene OPT-Positionen" icon="list-ul">
        <x-slot:actions>
            <span class="badge bg-primary">{{ $trades->total() }}</span>
        </x-slot:actions>

        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                <tr>
                    <th wire:click="sortBy('underlyingSymbol')" style="cursor: pointer;">
                        Underlying
                        @if($sortField === 'underlyingSymbol')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('dateTime')" style="cursor: pointer;">
                        Eröffnet
                        @if($sortField === 'dateTime')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th class="text-center">Contracts</th>
                    <th class="text-center">Type</th>
                    <th class="text-center">Buy/Sell</th>
                    <th class="text-end" wire:click="sortBy('strike')" style="cursor: pointer;">
                        Strike
                        @if($sortField === 'strike')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('expiry')" style="cursor: pointer;">
                        Expiry
                        @if($sortField === 'expiry')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th class="text-end">Premium</th>
                    <th class="text-end">50% Ziel</th>
                    <th>Strategie</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($trades as $trade)
                    <tr class="{{ $trade->expiry && $trade->expiry->isPast() ? 'table-warning' : '' }}">
                        <td>
                            <strong class="fs-5">{{ $trade->underlyingSymbol }}</strong>
                            <br><small class="text-muted">{{ $trade->symbol }}</small>
                        </td>
                        <td>
                            {{ $trade->dateTime?->format('d.m.Y') }}
                            <br><small class="text-muted">{{ $trade->dateTime?->diffForHumans() }}</small>
                        </td>
                        <td class="text-center">{{ number_format(abs($trade->quantity), 0) }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $trade->putCall === 'CALL' ? 'success' : 'danger' }}">
                                {{ $trade->putCall }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $trade->buySell === 'BUY' ? 'success' : 'danger' }}">
                                {{ $trade->buySell }}
                            </span>
                        </td>
                        <td class="text-end">
                            {{ number_format($trade->strike, 2) }}
                        </td>
                        <td>
                            {{ $trade->expiry?->format('d.m.Y') }}
                            <br>
                            @if($trade->expiry)
                                @if($trade->expiry->isPast())
                                    <small class="text-danger">Expired</small>
                                @else
                                    <small class="text-muted">{{ $trade->expiry->diffForHumans() }}</small>
                                @endif
                            @endif
                        </td>
                        <td class="text-end"><x-currency-value :value="$trade->netCash" currency="USD" :colored="true" /></td>
                        <td class="text-end">
                            @if($trade->buySell === 'SELL' && $trade->netCash > 0)
                                <span class="text-success fw-bold">
                                    {{ number_format($trade->netCash * 0.5, 2, ',', '') }} $
                                </span>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td>
                            @if($trade->strategy)
                                <small>{{ $trade->strategy }}</small>
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('trades.open.options.detail', $trade->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            <x-empty-state
                                icon="inbox"
                                title="Keine offenen Options-Positionen gefunden"
                                message="" />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            {{ $trades->links('components.pagination') }}
        </x-slot:footer>
    </x-trade-card>
</div>
