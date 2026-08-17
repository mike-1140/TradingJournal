<div>
    <x-page-header
        title="FX-Trades"
        subtitle="Währungsumtausch (z.B. USD ↔ EUR)"
        icon="currency-exchange">
    </x-page-header>

    <!-- Statistiken -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Anzahl FX-Trades</h6>
                    <h3>{{ number_format($totalCount) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Kommission gesamt</h6>
                    <h3>{{ number_format($totalCommission, 2, ',', '') }}</h3>
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
                        placeholder="Währungspaar, Trade ID..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Zielwährung</label>
                    <select class="form-select" wire:model.live="filterQuoteCurrency">
                        <option value="">Alle</option>
                        @foreach($quoteCurrencies as $currency)
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
                <table class="table table-hover table-sm">
                    <thead>
                    <tr>
                        <th wire:click="sortBy('dateTime')" style="cursor: pointer;">
                            Datum
                            @if($sortField === 'dateTime')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('symbol')" style="cursor: pointer;">
                            Paar
                            @if($sortField === 'symbol')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="text-end">Von</th>
                        <th class="text-end">Nach</th>
                        <th wire:click="sortBy('fxRate')" style="cursor: pointer;" class="text-end">
                            Kurs
                            @if($sortField === 'fxRate')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="text-end">Kommission</th>
                        <th>Trade ID</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($trades as $group)
                        <tr @if($group->isMulti)
                                data-bs-toggle="collapse"
                                data-bs-target="#fills-{{ $group->ibOrderID }}"
                                role="button"
                                class="fw-semibold"
                            @endif>
                            <td>
                                {{ $group->dateTime?->format('d.m.Y') }}
                                @if($group->dateTime)
                                    <br><small class="text-muted">{{ $group->dateTime?->format('H:i:s') }}</small>
                                @endif
                            </td>
                            <td><strong>{{ $group->symbol }}</strong></td>
                            <td class="text-end">
                                {{ number_format($group->baseAmount, 2, ',', '') }} {{ $group->baseCurrency }}
                            </td>
                            <td class="text-end">
                                {{ number_format($group->quoteAmount, 2, ',', '') }} {{ $group->quoteCurrency }}
                            </td>
                            <td class="text-end">{{ number_format($group->fxRate, 4, ',', '') }}</td>
                            <td class="text-end">{{ number_format($group->commission, 2, ',', '') }}</td>
                            <td>
                                @if($group->isMulti)
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-chevron-down"></i> {{ $group->fills->count() }} Fills
                                    </span>
                                @else
                                    <small>{{ $group->fills->first()->tradeID }}</small>
                                @endif
                            </td>
                        </tr>
                        @if($group->isMulti)
                            <tr>
                                <td colspan="7" class="p-0 border-0">
                                    <div class="collapse" id="fills-{{ $group->ibOrderID }}">
                                        <table class="table table-sm mb-0">
                                            <tbody>
                                            @foreach($group->fills as $fill)
                                                <tr class="text-muted small">
                                                    <td style="width: 17%;">{{ $fill->dateTime?->format('d.m.Y H:i:s') }}</td>
                                                    <td style="width: 15%;">{{ $fill->symbol }}</td>
                                                    <td class="text-end" style="width: 17%;">
                                                        {{ number_format($fill->baseAmount, 2, ',', '') }} {{ $fill->baseCurrency }}
                                                    </td>
                                                    <td class="text-end" style="width: 17%;">
                                                        {{ number_format($fill->quoteAmount, 2, ',', '') }} {{ $fill->quoteCurrency }}
                                                    </td>
                                                    <td class="text-end" style="width: 12%;">{{ number_format($fill->fxRate, 4, ',', '') }}</td>
                                                    <td class="text-end" style="width: 12%;">{{ number_format($fill->commission, 2, ',', '') }}</td>
                                                    <td><small>{{ $fill->tradeID }}</small></td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Keine FX-Trades gefunden
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
