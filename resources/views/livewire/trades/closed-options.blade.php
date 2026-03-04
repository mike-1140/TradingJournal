<div>
    {{-- 1. BREADCRUMBS
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'speedometer2'],
        ['label' => 'Closed Trades', 'icon' => 'check-circle']
    ]" />--}}

    {{-- 2. PAGE HEADER mit Environment Switcher --}}
    <x-page-header
        title="Geschlossene OPT-Positionen"
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

    <div class="text-muted small mt-1">
        @if($this->startDate)
            <i class="bi bi-calendar3"></i>
            Daten seit dem {{ $this->startDate->format('d.m.Y') }}
        @else
            <i class="bi bi-calendar3"></i>
            Alle historischen Daten
        @endif
    </div>

    <!-- Statistiken -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Anzahl Options Positionen</h6>
                    <h3>{{ number_format($totalTrades) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Premium</h6>
                    <h3 class="text-{{ $totalPremium >= 0 ? 'success' : 'danger' }}">
                        {{ number_format($totalPremium, 2) }} $
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

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Suche</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Symbol, Underlying..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Put/Call</label>
                    <select class="form-select" wire:model.live="filterPutCall">
                        <option value="">Alle</option>
                        <option value="CALL">CALL</option>
                        <option value="PUT">PUT</option>
                    </select>
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
                <table class="table table-hover table-sm">
                    <thead>
                    <tr>
                        <th wire:click="sortBy('underlyingSymbol')" style="cursor: pointer;" >
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
                        <th class="text-end">Premium</th>
                        <th class="text-end">Kommision</th>
                        <th >Strategie</th>
                        <th >Tags</th>
                        <th >Notes</th>
                        <th ></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($trades as $trade)
                        <tr class="{{ $trade->expiry && $trade->expiry->isPast() ? 'table-warning' : '' }}">
                            <td>
                                <strong class="fs-5">{{ $trade->underlyingSymbol }}</strong>
                                <br class="text-muted font-monospace">{{ $trade->formatted_option }}
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
                            <td class="text-end">{{ number_format($trade->profit, 2, ',', '') }} $</td>
                            <td class="text-end">{{ number_format($trade->ibCommission, 2, ',', '') }} $</td>
                            <td>
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
                                <a href="{{ route('trades.closed.detail', $trade->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"> Details</i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Keine geschlossenen Options Trades gefunden
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white">
                {{ $trades->links('components.pagination') }}
            </div>
        </div>
    </div>
</div>
