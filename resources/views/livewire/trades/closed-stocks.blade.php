<div>
    {{-- 1. BREADCRUMBS
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'speedometer2'],
        ['label' => 'Closed Trades', 'icon' => 'check-circle']
    ]" />--}}

    {{-- 2. PAGE HEADER mit Environment Switcher --}}
    <x-page-header
        title="Geschlossene STK-Positionen"
        subtitle="geschlossene Positionen"
        icon="check-circle">

        <x-slot:actions>
            {{-- Environment Switcher
            <div class="btn-group me-2" role="group">
                @foreach($environments as $env)
                    <button
                        type="button"
                        class="btn btn-{{ $selectedEnvironment === $env ? 'primary' : 'outline-primary' }}"
                        wire:click="changeEnvironment('{{ $env }}')">
                        {{ $env }}
                    </button>
                @endforeach
            </div>
            --}}
            {{-- Export Button
            <button wire:click="$set('showExportModal', true)" class="btn btn-outline-primary">
                <i class="bi bi-download"></i> Export
            </button>--}}
        </x-slot:actions>
    </x-page-header>

    <x-date-range-info :startDate="$this->startDate" :endDate="$this->endDate" />

    <!-- Statistiken -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Anzahl Stock Positionen</h6>
                    <h3>{{ number_format($totalTrades) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Gebundenes Kapital</h6>
                    <h3>{{ number_format($totalValue ?? 0, 2, ',', '') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Gesamte Kommissionen</h6>
                    <h3>{{ number_format($totalCommissions, 2, ',', '') }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. FILTER BAR --}}
    <x-filter-bar title="Filter Trades" collapsed>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Symbol</label>
                <input type="text"
                       class="form-control"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Symbol, Beschreibung...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Strategie</label>
                <select class="form-select" wire:model.live="filterStrategy">
                    <option value="">Alle</option>
                    @foreach($strategies as $strat)
                        <option value="{{ $strat }}">{{ $strat }}</option>
                    @endforeach
                </select>
            </div>
            {{-- not used at this time
            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date"
                       class="form-control"
                       wire:model.live="filterStartDate">
            </div>

            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date"
                       class="form-control"
                       wire:model.live="filterEndDate">
            </div>

            <div class="col-md-3">
                <label class="form-label">Days Held</label>
                <select class="form-select" wire:model.live="filterDaysHeld">
                    <option value="">All</option>
                    <option value="0-7">0-7 days</option>
                    <option value="8-30">8-30 days</option>
                    <option value="31-90">31-90 days</option>
                    <option value="90+">90+ days</option>
                </select>
            </div>--}}
        </div>
    </x-filter-bar>

    {{-- 4. TRADES TABLE in TradeCard --}}
    <x-trade-card title="geschlossene STK-Positionen" icon="list-ul">
        <x-slot:actions>
            <span class="badge bg-primary">{{ $trades->total() }}</span>
        </x-slot:actions>
        <div class="table-responsive">
            <table class="table table-hover closed-stocks-table">
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
                    <th  rowspan="2"class="text-end" wire:click="sortBy('profit')" style="cursor: pointer;">
                        PnL
                        @if($sortField === 'profit')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
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
                    <th rowspan="2" class="text-end">Tags</th>
                    <th rowspan="2" class="text-end">Notes</th>
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
                        <td class="text-end"><x-currency-value :value="$trade->profit" :currency="$trade->currency" :colored="true" /></td>
                        <td class="text-end">{{ number_format($trade->ROI, 2, ',', '') }}</td>

                        <td class="text-end">{{ number_format($trade->tradePriceOpen, 2, ',', '') }}</td>
                        <td class="text-end">{{ number_format($trade->tradePriceClose, 2, ',', '') }}</td>
                        <td class="text-end">{{ number_format($trade->quantity, 2, ',', '') }}</td>


                        <td class="text-end">{{ number_format($trade->ibCommission, 2, ',', '') }}</td>
                        <td class="text-end">
                            @if($trade->strategy)
                                <small>{{ $trade->strategy }}</small>
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td>
                            {{-- 5. TAG BADGES --}}
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($trade->trade_tags ?? [] as $tag)
                                    <x-tag-badge :tag="$tag" size="sm" />
                                @endforeach
                            </div>
                        </td>
                        <td><!-- Notes Indicator -->
                            @if($trade->notes()->count() > 0)
                                <span class="badge bg-info" title="{{ $trade->notes()->count() }} note(s)">
                                    <i class="bi bi-journal-text"></i> {{ $trade->notes()->count() }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('trades.closed.stocks.detail', $trade->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"> Details</i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            Keine offenen Stock Trades gefunden
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{-- 7. PAGINATION --}}
        <div class="card-footer bg-white">
            {{ $trades->links('components.pagination') }}
        </div>
    </x-trade-card>

</div>
