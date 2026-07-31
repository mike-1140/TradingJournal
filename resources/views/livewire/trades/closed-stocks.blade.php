<div>
    <x-page-header
        title="Geschlossene STK-Positionen"
        subtitle="geschlossene Positionen"
        icon="check-circle">
    </x-page-header>

    <x-date-range-info :startDate="$this->startDate" :endDate="$this->endDate" />

    {{-- Statistiken --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <x-stat-card
                title="Anzahl Stock Positionen"
                :value="number_format($totalTrades)"
                color="primary"
                icon="hash" />
        </div>
        <div class="col-md-4">
            <x-stat-card
                title="Gebundenes Kapital"
                :value="number_format($totalValue ?? 0, 2, ',', '') . ' $'"
                color="info"
                icon="cash-stack" />
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
                    <label class="form-label form-label-sm mb-1">Symbol</label>
                    <input type="text" class="form-control form-control-sm"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Symbol, Beschreibung...">
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
                <div class="ms-auto">
                    <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. TRADES TABLE in TradeCard --}}
    <x-trade-card title="geschlossene STK-Positionen" icon="list-ul">
        <x-slot:actions>
            <span class="badge bg-primary">{{ $trades->total() }}</span>
        </x-slot:actions>
        <div class="table-responsive">
            <table class="table table-hover closed-stocks-table list-table-sticky-action">
                <thead>
                <tr>
                    <th class="text-truncate w-30" style="max-width: 130px;" rowspan="2" wire:click="sortBy('symbol')" style="cursor: pointer;">
                        Symbol
                        @if($sortField === 'symbol')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th rowspan="2" wire:click="sortBy('dateTimeOpen')" style="cursor: pointer;">
                        Eröffnet
                        @if($sortField === 'dateTimeOpen')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th rowspan="2" wire:click="sortBy('dateTimeClose')" style="cursor: pointer;">
                        Geschlossen
                        @if($sortField === 'dateTimeClose')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th rowspan="2" class="text-end">Daysheld</th>
                    <th rowspan="2" class="text-end">Buy/Sell</th>
                    <th colspan="2" class="text-end">NetCash</th>
                    <th  rowspan="2" class="text-end" wire:click="sortBy('profit')" style="cursor: pointer;">
                        Brutto PnL
                        @if($sortField === 'profit')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                        <br><small class="text-muted fw-normal" style="font-size: 0.65rem;">/ Netto</small>
                    </th>
                    <th rowspan="2" class="text-end" wire:click="sortBy('ROI')" style="cursor: pointer;">
                        ROI
                        @if($sortField === 'ROI')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th colspan="2" class="text-end">Stückpreis</th>
                    <th rowspan="2" class="text-end">Stück</th>
                    <th rowspan="2" class="text-end">Kommision</th>
                    <th rowspan="2" class="text-end">Strategie</th>
                    <th rowspan="2"></th>
                </tr>
                <tr>
                    <th class="text-end">opened</th>
                    <th class="text-end">closed</th>

                    <th class="text-end">opened</th>
                    <th class="text-end">closed</th>
                </tr>
                </thead>
                <tbody>
                @forelse($trades as $trade)
                    <tr>
                        <td><strong class="fs-5">{{ $trade->symbol }}</strong>
                            <br class="text-muted font-monospace">{{ $trade->description }}</td>
                        <td>
                            {{ $trade->dateTimeOpen?->format('d.m.Y') }}
                            <br><small class="text-muted">{{ $trade->dateTimeOpen?->diffForHumans() }}</small>
                        </td>
                        <td>
                            {{ $trade->dateTimeClose?->format('d.m.Y') }}
                            <br><small class="text-muted">{{ $trade->dateTimeClose?->diffForHumans() }}</small>
                        </td>

                        <td class="text-end">{{ number_format($trade->daysHeld, 0) }}</td>
                        <td class="text-end">
                            <span class="badge bg-{{ $trade->buySellOpen === 'BUY' ? 'success' : 'danger' }}">
                                {{ $trade->buySellOpen }}
                            </span>
                        </td>
                        <td class="text-end">{{ number_format($trade->netCashOpen, 2, ',', '') }}</td>
                        <td class="text-end">{{ number_format($trade->netCashClose, 2, ',', '') }}</td>
                        <td class="text-end">
                            <x-currency-value :value="$trade->profit" :currency="$trade->currency" :colored="true" />
                            <br><small class="text-muted" style="font-size: 0.75rem;">
                                netto: <x-currency-value :value="$trade->profit + $trade->ibCommission" :currency="$trade->currency" :colored="true" />
                            </small>
                        </td>
                        <td class="text-end">{{ number_format($trade->ROI, 2, ',', '') }}</td>

                        <td class="text-end">{{ number_format($trade->tradePriceOpen, 2, ',', '') }}</td>
                        <td class="text-end">{{ number_format($trade->tradePriceClose, 2, ',', '') }}</td>
                        <td class="text-end">{{ number_format($trade->quantity, 2, ',', '') }}</td>


                        <td class="text-end">{{ number_format($trade->ibCommission, 2, ',', '') }}</td>
                        <td class="text-end">
                            @if($trade->display_strategy)
                                <small>{{ $trade->display_strategy }}</small>
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('trades.closed.stocks.detail', $trade->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15">
                            <x-empty-state
                                icon="inbox"
                                title="Keine geschlossenen Stock-Positionen gefunden"
                                message="Für den gewählten Zeitraum und Filter wurden keine Positionen gefunden." />
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
