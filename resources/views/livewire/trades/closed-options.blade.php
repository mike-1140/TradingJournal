<div>
    <x-page-header
        title="Geschlossene OPT-Positionen"
        subtitle="geschlossene Positionen"
        icon="check-circle">
    </x-page-header>

    <x-date-range-info :startDate="$this->startDate" :endDate="$this->endDate" />

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
    <x-trade-card title="geschlossene OPT-Positionen" icon="list-ul">
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
                    <th class="text-end">Put/Call</th>
                    <th class="text-end">Buy/Sell</th>
                    <th class="text-end" wire:click="sortBy('strike')" style="cursor: pointer;">
                        Strike
                        @if($sortField === 'strike')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th class="text-center">Contracts</th>
                    <th wire:click="sortBy('dateTimeOpen')" style="cursor: pointer;">
                        Eröffnet
                        @if($sortField === 'dateTimeOpen')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('dateTimeClose')" style="cursor: pointer;">
                        Geschlossen
                        @if($sortField === 'dateTimeClose')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th class="text-center">Daysheld</th>
                    <th class="text-end">Preis</th>
                    <th class="text-end">Status</th>
                    <th class="text-end" wire:click="sortBy('profit')" style="cursor: pointer;">
                        Brutto P&L
                        @if($sortField === 'profit')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                        <br><small class="text-muted fw-normal" style="font-size: 0.65rem;">/ Netto</small>
                    </th>
                    <th class="text-end">Kommission</th>
                    <th>Strategie</th>
                    <th>Tags</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($trades as $trade)
                    <tr>
                        <td>
                            <strong class="fs-5">{{ $trade->underlyingSymbol }}</strong>
                            <br><small class="text-muted font-monospace">{{ $trade->formatted_option }}</small>
                        </td>
                        <td class="text-end">
                            <span class="badge bg-{{ $trade->putCall === 'P' ? 'success' : 'danger' }}">
                                {{ $trade->putCall === 'P' ? 'PUT' : 'CALL' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="badge bg-{{ $trade->buySellOpen === 'BUY' ? 'success' : 'danger' }}">
                                {{ $trade->buySellOpen }}
                            </span>
                        </td>
                        <td class="text-end">
                            <strong>{{ number_format($trade->strike, 2) }}</strong>
                        </td>
                        <td class="text-center">{{ number_format(abs($trade->quantity), 0) }}</td>
                        <td>
                            {{ $trade->dateTimeOpen?->format('d.m.Y') }}
                            <br><small class="text-muted">{{ $trade->dateTimeOpen?->diffForHumans() }}</small>
                        </td>
                        <td>
                            {{ $trade->dateTimeClose?->format('d.m.Y') }}
                            <br><small class="text-muted">{{ $trade->dateTimeClose?->diffForHumans() }}</small>
                        </td>
                        <td class="text-center">{{ number_format($trade->daysHeld, 0) }}</td>
                        <td class="text-end">{{ number_format($trade->tradePriceOpen, 2, ',', '') }}</td>
                        <td class="text-end">{{ $trade->status }}</td>
                        <td class="text-end">
                            <x-currency-value :value="$trade->profit" currency="USD" :colored="true" />
                            <br><small class="text-muted" style="font-size: 0.75rem;">
                                netto: <x-currency-value :value="$trade->profit + $trade->ibCommission" currency="USD" :colored="true" />
                            </small>
                        </td>
                        <td class="text-end"><x-currency-value :value="$trade->ibCommission" currency="USD" /></td>
                        <td>
                            @if($trade->strategy)
                                <small>{{ $trade->strategy }}</small>
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($trade->trade_tags ?? [] as $tag)
                                    <x-tag-badge :tag="$tag" size="sm" />
                                @endforeach
                            </div>
                        </td>
                        <td>
                            @if($trade->notes()->count() > 0)
                                <span class="badge bg-info" title="{{ $trade->notes()->count() }} note(s)">
                                    <i class="bi bi-journal-text"></i> {{ $trade->notes()->count() }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('trades.closed.options.detail', $trade->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16">
                            <x-empty-state
                                icon="inbox"
                                title="Keine geschlossenen Options-Positionen gefunden"
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
