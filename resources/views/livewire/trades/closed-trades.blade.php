<div>
    {{-- 1. BREADCRUMBS
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'speedometer2'],
        ['label' => 'Closed Trades', 'icon' => 'check-circle']
    ]" />--}}

    {{-- 2. PAGE HEADER mit Environment Switcher --}}
    <x-page-header
        title="Closed Trades"
        subtitle="Review and analyze your completed positions"
        icon="check-circle">

        <x-slot:actions>
            {{-- Environment Switcher --}}
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

            {{-- Export Button
            <button wire:click="$set('showExportModal', true)" class="btn btn-outline-primary">
                <i class="bi bi-download"></i> Export
            </button>--}}
        </x-slot:actions>
    </x-page-header>

    <div class="text-muted small mt-1">
        @if($this->startDate)
            <i class="bi bi-calendar3"></i>
            Daten seit dem {{ $this->startDate->format('d.m.Y') }}
        @else
            <i class="bi bi-calendar3"></i>
            Alle historischen Daten
        @endif
    </div>

    {{-- Flash Messages
    @if(session('message'))
        <x-alert-message type="success">
            {{ session('message') }}
        </x-alert-message>
    @endif

    @if(session('error'))
        <x-alert-message type="danger">
            {{ session('error') }}
        </x-alert-message>
    @endif--}}

    {{-- 3. FILTER BAR --}}
    <x-filter-bar title="Filter Trades" collapsed>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Symbol</label>
                <input type="text"
                       class="form-control"
                       wire:model.live.debounce.300ms="filterSymbol"
                       placeholder="e.g. AAPL">
            </div>

            <div class="col-md-3">
                <label class="form-label">Asset Type</label>
                <select class="form-select" wire:model.live="filterAssetCategory">
                    <option value="">All</option>
                    <option value="STK">Stocks</option>
                    <option value="OPT">Options</option>
                    <option value="FUT">Futures</option>
                    <option value="CASH">Cash/FX</option>
                </select>
            </div>

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
                <label class="form-label">Min Profit ($)</label>
                <input type="number"
                       class="form-control"
                       wire:model.live="filterMinProfit"
                       placeholder="0.00"
                       step="0.01">
            </div>

            <div class="col-md-3">
                <label class="form-label">Max Profit ($)</label>
                <input type="number"
                       class="form-control"
                       wire:model.live="filterMaxProfit"
                       placeholder="1000.00"
                       step="0.01">
            </div>

            <div class="col-md-3">
                <label class="form-label">Profit/Loss</label>
                <select class="form-select" wire:model.live="filterProfitLoss">
                    <option value="">All</option>
                    <option value="profit">Only Profits</option>
                    <option value="loss">Only Losses</option>
                </select>
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
            </div>
        </div>

        {{-- Footer mit Summary --}}
        <x-slot:footer>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted">
                        <i class="bi bi-info-circle"></i>
                        Showing <strong>{{ $trades->count() }}</strong> of <strong>{{ $trades->total() }}</strong> trades
                    </span>
                    @if($this->hasActiveFilters)
                        <span class="badge bg-primary ms-2">
                            {{ $this->activeFilterCount }} filter(s) active
                        </span>
                    @endif
                </div>

                @if($this->hasActiveFilters)
                    <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </button>
                @endif
            </div>
        </x-slot:footer>
    </x-filter-bar>

    {{-- 4. TRADES TABLE in TradeCard --}}
    <x-trade-card title="Trades" icon="list-ul">
        <x-slot:actions>
            <span class="badge bg-primary">{{ $trades->total() }}</span>
        </x-slot:actions>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th wire:click="sortBy('symbol')" style="cursor: pointer;">
                        Symbol
                        @if($sortField === 'symbol')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th>Type</th>
                    <th wire:click="sortBy('tradeDateOpen')" style="cursor: pointer;">
                        Open Date
                        @if($sortField === 'tradeDateOpen')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('tradeDateClose')" style="cursor: pointer;">
                        Close Date
                        @if($sortField === 'tradeDateClose')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th class="text-end">Qty</th>
                    <th class="text-end" wire:click="sortBy('profit')" style="cursor: pointer;">
                        Profit
                        @if($sortField === 'profit')
                            <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th class="text-end">ROI</th>
                    <th class="text-center">Days</th>
                    <th>Tags</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($trades as $trade)
                    <tr>
                        <td>
                            <strong>{{ $trade->symbol }}</strong>
                            @if($trade->putCall)
                                <br><small class="text-muted">
                                    {{ $trade->strike }} {{ $trade->putCall }}
                                </small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $trade->assetCategory }}</span>
                        </td>
                        <td>{{ $trade->tradeDateOpen?->format('d.m.Y') }}</td>
                        <td>{{ $trade->tradeDateClose?->format('d.m.Y') }}</td>
                        <td class="text-end">{{ number_format(abs($trade->quantity), 0) }}</td>
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
                        <td class="text-center">{{ $trade->daysHeld }}</td>
                        <td>
                            {{-- 5. TAG BADGES --}}
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($trade->trade_tags ?? [] as $tag)
                                    <x-tag-badge :tag="$tag" size="sm" />
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('trades.closed.detail', $trade->id) }}"
                                   class="btn btn-outline-primary"
                                   title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            {{-- 6. EMPTY STATE --}}
                            <x-empty-state
                                icon="inbox"
                                title="No closed trades found"
                                message="Try adjusting your filters or check a different environment." />
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

    {{-- 8. DELETE CONFIRMATION MODAL
    <x-modal name="delete-confirm" title="Confirm Delete" size="sm" wire:model="showDeleteModal">
        <div class="text-center py-3">
            <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
            <h5 class="mt-3">Delete Trade?</h5>
            <p class="text-muted">
                Are you sure you want to delete this trade?<br>
                <strong>This action cannot be undone.</strong>
            </p>
        </div>

        <x-slot:footer>
            <button class="btn btn-secondary" wire:click="$set('showDeleteModal', false)">
                Cancel
            </button>
            <button class="btn btn-danger" wire:click="deleteTrade" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="deleteTrade">
                    <i class="bi bi-trash"></i> Delete
                </span>
                <span wire:loading wire:target="deleteTrade">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Deleting...
                </span>
            </button>
        </x-slot:footer>
    </x-modal>--}}

    {{-- 9. EXPORT MODAL
    <x-modal name="export" title="Export Trades" wire:model="showExportModal">
        <div class="mb-3">
            <label class="form-label">Export Format</label>
            <select class="form-select" wire:model="exportFormat">
                <option value="csv">CSV (Excel compatible)</option>
                <option value="excel">Excel (.xlsx)</option>
                <option value="pdf">PDF Report</option>
                <option value="json">JSON</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Date Range</label>
            <select class="form-select" wire:model="exportDateRange">
                <option value="all">All Trades</option>
                <option value="current">Current Filters</option>
                <option value="year">This Year</option>
                <option value="month">This Month</option>
                <option value="custom">Custom Range</option>
            </select>
        </div>

        @if($exportDateRange === 'custom')
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label">From</label>
                    <input type="date" class="form-control" wire:model="exportFromDate">
                </div>
                <div class="col-6">
                    <label class="form-label">To</label>
                    <input type="date" class="form-control" wire:model="exportToDate">
                </div>
            </div>
        @endif

        <x-alert-message type="info" :dismissible="false">
            <small>
                <strong>{{ $this->exportTradeCount }}</strong> trades will be exported.
            </small>
        </x-alert-message>

        <x-slot:footer>
            <button class="btn btn-secondary" wire:click="$set('showExportModal', false)">
                Cancel
            </button>
            <button class="btn btn-primary" wire:click="exportTrades" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="exportTrades">
                    <i class="bi bi-download"></i> Export
                </span>
                <span wire:loading wire:target="exportTrades">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Exporting...
                </span>
            </button>
        </x-slot:footer>
    </x-modal> --}}
</div>
