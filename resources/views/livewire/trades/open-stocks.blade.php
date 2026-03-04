<div>
    {{-- 1. BREADCRUMBS
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'speedometer2'],
        ['label' => 'Closed Trades', 'icon' => 'check-circle']
    ]" />--}}

    {{-- 2. PAGE HEADER mit Environment Switcher --}}
    <x-page-header
        title="Offene STK-Positionen"
        subtitle="Offene Positionen"
        icon="graph-up">

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

        <!-- Statistiken -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Anzahl STK Positionen</h6>
                    <h3>{{ number_format($totalTrades) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Gebundenes Kapital</h6>
                    <h3>{{ number_format($totalValue, 2, ',', '') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Summe Kommissionen</h6>
                    <h3>{{ number_format($totalCommissions, 2, ',', '') }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. FILTER BAR --}}
    <x-filter-bar title="Filter Trades">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Symbol</label>
                <input type="text"
                       class="form-control"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Symbol, Beschreibung...">
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
    <x-trade-card title="offene STK-Positionen" icon="list-ul">
        <x-slot:actions>
            <span class="badge bg-primary">{{ $trades->total() }}</span>
        </x-slot:actions>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th wire:click="sortBy('symbol')" style="cursor: pointer;">
                        Symbol
                        @if($sortField === 'symbol')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('dateTime')" style="cursor: pointer;">
                        Eröffnet
                        @if($sortField === 'dateTime')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th class="text-end">Stücke</th>
                    <th class="text-end">akt. Preis</th>
                    <th class="text-end" wire:click="sortBy('tradePrice')" style="cursor: pointer;">
                        Einstiegspreis
                        @if($sortField === 'tradePrice')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th class="text-end">Gesamtwert</th>
                    <th class="text-end">NetCash</th>
                    <th>Buy/Sell</th>
                    <th>Tags</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($trades as $trade)
                    <tr>
                        <td><strong class="fs-5">{{ $trade->symbol }}</strong>
                        <br><small>{{ $trade->description }}</small>
                        <td>
                            {{ $trade->dateTime?->format('d.m.Y') }}
                            <br><small class="text-muted">{{ $trade->dateTime?->diffForHumans() }}</small>
                        </td>
                        <td class="text-end">
                            {{ number_format($trade->quantity, 2, ',', '') }}
                        </td>
                        <td class="text-end">---
                            {{--
                            <strong>{{ $trade->current_price }} </strong>
                                <br><small class="text-muted">{{ $trade->last_trade_day }}</small>
                            --}}
                        </td>
                        <td class="text-end">{{ number_format($trade->tradePrice, 2, ',', '') }}</td>
                        <td class="text-end">
                            <strong>{{ number_format($trade->quantity * $trade->tradePrice,  2, ',', '') }}</strong>
                        </td>
                        <td class="text-end">{{ number_format($trade->netCash,  2, ',', '') }}</td>
                        <td>
                            <span class="badge bg-{{ $trade->buySell === 'BUY' ? 'success' : 'danger' }}">
                                {{ $trade->buySell }}
                            </span>
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
                            <a href="{{ route('trades.open.stocks.detail', $trade->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"> Details</i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            {{-- 6. EMPTY STATE --}}
                            <x-empty-state
                                icon="inbox"
                                title="keine offenen Positionen gefunden"
                                message="" />
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

