<div>
    {{-- 1. BREADCRUMBS
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'speedometer2'],
        ['label' => 'Closed Trades', 'icon' => 'check-circle']
    ]" />--}}

    {{-- 2. PAGE HEADER mit Environment Switcher --}}
    <x-page-header
        title="Raw Trade Data"
        subtitle="Raw Trade Data"
        icon="database">

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
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Gesamt Raw Trades</h6>
                    <h3>{{ number_format($totalRaw) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted">Verarbeitet</h6>
                    <h3 class="text-success">{{ number_format($processedCount) }}</h3>
                    <small>{{ $totalRaw > 0 ? round(($processedCount / $totalRaw) * 100, 1) : 0 }}%</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted">Nicht verarbeitet</h6>
                    <h3 class="text-warning">{{ number_format($unprocessedCount) }}</h3>
                    <small>{{ $totalRaw > 0 ? round(($unprocessedCount / $totalRaw) * 100, 1) : 0 }}%</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total NetCash</h6>
                    <h3>{{ number_format($totalNetCash, 2, ',', '') }} $</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter und Suche -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Suche</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Symbol, Trade ID, Order ID..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Processing Status</label>
                    <select class="form-select" wire:model.live="filterProcessed">
                        <option value="">Alle</option>
                        <option value="processed">Verarbeitet</option>
                        <option value="unprocessed">Nicht verarbeitet</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Asset Category</label>
                    <select class="form-select" wire:model.live="filterAssetCategory">
                        <option value="">Alle</option>
                        @foreach($assetCategories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Buy/Sell</label>
                    <select class="form-select" wire:model.live="filterBuySell">
                        <option value="">Alle</option>
                        <option value="BUY">BUY</option>
                        <option value="SELL">SELL</option>
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

    <!-- Tabelle -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th wire:click="sortBy('symbol')" style="cursor: pointer;">
                            Symbol
                            @if($sortField === 'symbol')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th>Typ</th>
                        <th wire:click="sortBy('buySell')" style="cursor: pointer;">
                            Buy/Sell
                            @if($sortField === 'buySell')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('dateTime')" style="cursor: pointer;">
                            Datum
                            @if($sortField === 'dateTime')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Preis</th>
                        <th class="text-end">NetCash</th>
                        <th>Trade ID</th>
                        <th>Order ID</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Processed</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($trades as $trade)
                        <tr class="{{ $trade->processed ? '' : 'table-warning' }}">
                            <td><small>{{ $trade->id }}</small></td>
                            <td>
                                <strong>{{ $trade->symbol }}</strong>
                                @if($trade->putCall)
                                    <br><small class="badge bg-{{ $trade->putCall === 'CALL' ? 'success' : 'danger' }}">
                                        {{ $trade->putCall }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $trade->assetCategory }}</span>
                                @if($trade->subCategory)
                                    <br><small>{{ $trade->subCategory }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $trade->buySell === 'BUY' ? 'success' : 'danger' }}">
                                    {{ $trade->buySell }}
                                </span>
                            </td>
                            <td>
                                {{ $trade->dateTime?->format('d.m.Y') }}
                                @if($trade->dateTime)
                                    <br><small class="text-muted">{{ $trade->dateTime?->format('H:i:s') }}</small>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($trade->quantity, 2, ',', '') }}</td>
                            <td class="text-end">{{ number_format($trade->tradePrice, 2, ',', '') }}</td>
                            <td class="text-end">{{ number_format($trade->netCash, 2, ',', '') }}</td>
                            <td><small>{{ $trade->tradeID }}</small></td>
                            <td><small>{{ $trade->ibOrderID }}</small></td>
                            <td><small>{{ $trade->source }}</small></td>
                            <td><small>{{ $trade->status }}</small></td>
                            <td>
                                @if($trade->processed)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Ja
                                    </span>
                                @else
                                    <span class="badge bg-warning">
                                        <i class="bi bi-clock"></i> Nein
                                    </span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('trades.raw.detail', $trade->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-4">
                                Keine Raw Trades gefunden
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $trades->links('components.pagination') }}
            </div>
        </div>
    </div>
</div>
