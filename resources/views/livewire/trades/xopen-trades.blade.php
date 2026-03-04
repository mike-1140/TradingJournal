<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-hourglass-split"></i> Offene Trades</h1>

        <div class="d-flex gap-3 align-items-center">
            <!-- Asset Type Filter (prominent) -->
            <div>
                <label class="me-2 small fw-bold">Asset Type:</label>
                <div class="btn-group" role="group">
                    <button
                        type="button"
                        class="btn btn-sm btn-{{ $filterAssetCategory === '' ? 'info' : 'outline-info' }}"
                        wire:click="$set('filterAssetCategory', '')">
                        <i class="bi bi-list"></i> Alle
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-{{ $filterAssetCategory === 'STK' ? 'info' : 'outline-info' }}"
                        wire:click="$set('filterAssetCategory', 'STK')">
                        <i class="bi bi-graph-up"></i> STK
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-{{ $filterAssetCategory === 'OPT' ? 'info' : 'outline-info' }}"
                        wire:click="$set('filterAssetCategory', 'OPT')">
                        <i class="bi bi-diagram-3"></i> OPT
                    </button>
                </div>
            </div>

            <!-- Quick Link zu Spezialansichten -->
            @if($filterAssetCategory === 'STK')
                <a href="{{ route('trades.open.stocks') }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-box-arrow-up-right"></i> Stock Details
                </a>
            @elseif($filterAssetCategory === 'OPT')
                <a href="{{ route('trades.open.options') }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-box-arrow-up-right"></i> Options Details
                </a>
            @endif

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

    <!-- Statistiken -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Anzahl offener Trades</h6>
                    <h3>{{ number_format($totalTrades) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Gebundenes Kapital</h6>
                    <h3 class="text-{{ $totalNetCash >= 0 ? 'success' : 'danger' }}">
                        {{ number_format(abs($totalNetCash), 2) }} $
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Gesamte Kommissionen</h6>
                    <h3>{{ number_format($totalCommissions, 2) }} $</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter (OHNE Asset Category Dropdown - ist jetzt oben) -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Suche</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Symbol, Beschreibung, Trade ID..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Strategie</label>
                    <select class="form-select" wire:model.live="filterStrategy">
                        <option value="">Alle</option>
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

    <!-- Tabelle -->
    <div class="card">
        <div class="card-body">
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
                        <th>Details</th>
                        <th wire:click="sortBy('assetCategory')" style="cursor: pointer;">
                            Typ
                            @if($sortField === 'assetCategory')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('dateTime')" style="cursor: pointer;">
                            Eröffnet
                            @if($sortField === 'dateTime')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="text-end">Qty</th>
                        <th class="text-end" wire:click="sortBy('tradePrice')" style="cursor: pointer;">
                            Preis
                            @if($sortField === 'tradePrice')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="text-end">NetCash</th>
                        <th>Strategie</th>
                        <th>Status</th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($trades as $trade)
                        <tr>
                            <td>
                                <strong>{{ $trade->symbol }}</strong>
                                @if($trade->underlyingSymbol)
                                    <br><small class="text-muted">{{ $trade->underlyingSymbol }}</small>
                                @endif
                            </td>
                            <td>
                                @if($trade->putCall)
                                    <span class="badge bg-{{ $trade->putCall === 'CALL' ? 'success' : 'danger' }}">
                                        {{ $trade->putCall }}
                                    </span>
                                    <br>
                                @endif
                                @if($trade->strike)
                                    <small>Strike: {{ number_format($trade->strike, 2) }}</small><br>
                                @endif
                                @if($trade->expiry)
                                    <small class="text-muted">Exp: {{ $trade->expiry->format('d.m.Y') }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $trade->assetCategory }}</span>
                            </td>
                            <td>
                                {{ $trade->dateTime?->format('d.m.Y') }}
                                @if($trade->tradeTime)
                                    <br><small class="text-muted">{{ substr($trade->tradeTime, 0, 5) }}</small>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($trade->quantity, 0) }}</td>
                            <td class="text-end">{{ number_format($trade->tradePrice, 2) }}</td>
                            <td class="text-end">{{ number_format($trade->netCash, 2) }}</td>
                            <td>
                                @if($trade->strategy)
                                    <small>{{ $trade->strategy }}</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    {{ $trade->status ?: 'OPEN' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('trades.open.detail', $trade->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Details
                                </a>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <!-- Notes Indicator -->
                                    @if($trade->notes()->count() > 0)
                                        <span class="badge bg-info" title="{{ $trade->notes()->count() }} note(s)">
                <i class="bi bi-journal-text"></i> {{ $trade->notes()->count() }}
            </span>
                                    @endif

                                    <!-- Detail Button -->
                                    <a href="{{ route('trades.open.detail', $trade->id) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Keine offenen Trades gefunden
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $trades->links() }}
            </div>
        </div>
    </div>
</div>
