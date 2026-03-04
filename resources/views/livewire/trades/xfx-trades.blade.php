<div>
    {{-- 1. BREADCRUMBS
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'speedometer2'],
        ['label' => 'Closed Trades', 'icon' => 'check-circle']
    ]" />--}}

    {{-- 2. PAGE HEADER mit Environment Switcher --}}
    <x-page-header
        title="FX-Trades"
        subtitle="FX-Trades"
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


    <!-- Statistiken -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="text-muted">Anzahl FX Trades</h6>
                    <h3>{{ number_format($totalTrades) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="text-muted">Gesamtes Volumen</h6>
                    <h3>{{ number_format($totalVolume, 2) }} $</h3>
                    <small class="text-muted">Ø {{ $totalTrades > 0 ? number_format($totalVolume / $totalTrades, 2) : 0 }} $ pro Trade</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="text-muted">Gesamte Kommissionen</h6>
                    <h3>{{ number_format($totalCommissions, 2) }} $</h3>
                    <small class="text-muted">Ø {{ $totalTrades > 0 ? number_format($totalCommissions / $totalTrades, 2) : 0 }} $ pro Trade</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Suche</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Symbol, Währung, Trade ID..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Währung</label>
                    <select class="form-select" wire:model.live="filterCurrency">
                        <option value="">Alle</option>
                        @foreach($currencies as $currency)
                            <option value="{{ $currency }}">{{ $currency }}</option>
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
                        <th wire:click="sortBy('dateTime')" style="cursor: pointer;">
                            Datum
                            @if($sortField === 'dateTime')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('symbol')" style="cursor: pointer;">
                            Währungspaar
                            @if($sortField === 'symbol')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th>Base → Quote</th>
                        <th class="text-end" wire:click="sortBy('baseAmount')" style="cursor: pointer;">
                            Base Amount
                            @if($sortField === 'baseAmount')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="text-end" wire:click="sortBy('quoteAmount')" style="cursor: pointer;">
                            Quote Amount
                            @if($sortField === 'quoteAmount')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="text-end" wire:click="sortBy('fxRate')" style="cursor: pointer;">
                            FX Rate
                            @if($sortField === 'fxRate')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="text-end" wire:click="sortBy('commission')" style="cursor: pointer;">
                            Kommission
                            @if($sortField === 'commission')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th>Trade ID</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($trades as $trade)
                        <tr>
                            <td>
                                {{ $trade->dateTime->format('d.m.Y') }}
                                <br><small class="text-muted">{{ $trade->dateTime->diffForHumans() }}</small>
                            </td>
                            <td>
                                <strong class="fs-5">{{ $trade->formatted_symbol }}</strong>
                                <br><small class="text-muted">{{ $trade->symbol }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $trade->baseCurrency }}</span>
                                <i class="bi bi-arrow-right"></i>
                                <span class="badge bg-info">{{ $trade->quoteCurrency }}</span>
                            </td>
                            <td class="text-end">
                                <strong>{{ number_format($trade->baseAmount, 2) }}</strong>
                                <small class="text-muted">{{ $trade->baseCurrency }}</small>
                            </td>
                            <td class="text-end">
                                <strong>{{ number_format($trade->quoteAmount, 2) }}</strong>
                                <small class="text-muted">{{ $trade->quoteCurrency }}</small>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-secondary">{{ number_format($trade->fxRate, 4) }}</span>
                            </td>
                            <td class="text-end">{{ number_format($trade->commission, 2) }} $</td>
                            <td><small>{{ $trade->tradeID }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Keine FX Trades gefunden
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $trades->links() }}
            </div>
        </div>
    </div>
</div>
