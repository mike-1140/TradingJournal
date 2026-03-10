<div>
    <x-page-header title="Symbole" icon="tag" />

    <x-date-range-info :startDate="$this->startDate" :endDate="$this->endDate" />

    <!-- Statistiken -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Gesamt Symbole</h6>
                    <h3>{{ number_format($totalSymbols) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted">Aktive Symbole (mit offenen Trades)</h6>
                    <h3 class="text-success">{{ number_format($activeSymbols) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter und Suche -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Suche Symbol</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Symbol suchen..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Asset Category</label>
                    <select class="form-select" wire:model.live="filterAssetCategory">
                        <option value="">Alle</option>
                        @foreach($assetCategories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
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

    <!-- Tabelle -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th wire:click="sortBy('symbol')" style="cursor: pointer;">
                            Symbol
                            @if($sortField === 'symbol')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('assetCategory')" style="cursor: pointer;">
                            Asset Category
                            @if($sortField === 'assetCategory')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="text-end">Offene Trades</th>
                        <th class="text-end">Geschlossene Trades</th>
                        <th class="text-end">Total Profit</th>
                        <th class="text-end">Win Rate</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($symbols as $symbol)
                        <tr>
                            <td><small class="text-muted">{{ $symbol->id }}</small></td>
                            <td>
                                <strong class="fs-5">{{ $symbol->symbol }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $symbol->assetCategory }}</span>
                            </td>
                            <td class="text-end">
                                @if($symbol->open_trades_count > 0)
                                    <span class="badge bg-primary">{{ $symbol->open_trades_count }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($symbol->closed_trades_count > 0)
                                    <span class="badge bg-secondary">{{ $symbol->closed_trades_count }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($symbol->closed_trades_count > 0)
                                    <strong class="text-{{ $symbol->total_profit >= 0 ? 'success' : 'danger' }}">
                                        {{ number_format($symbol->total_profit, 2) }} $
                                    </strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($symbol->closed_trades_count > 0)
                                    <span class="badge bg-{{ $symbol->win_rate >= 50 ? 'success' : 'warning' }}">
                                        {{ $symbol->win_rate }}%
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Keine Symbole gefunden
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $symbols->links() }}
            </div>
        </div>
    </div>
</div>
