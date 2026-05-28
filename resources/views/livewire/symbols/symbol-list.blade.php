<div>
    <x-page-header title="Symbole" icon="tag" />

    <x-date-range-info :startDate="$this->startDate" :endDate="$this->endDate" />

    {{-- Statistiken --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Symbole mit Kampagnen</h6>
                    <h3>{{ $totalSymbols }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted">Aktive Symbole (ACTIVE / PENDING)</h6>
                    <h3 class="text-success">{{ $activeSymbols }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Suche --}}
    <div class="card mb-4">
        <div class="card-body py-2">
            <input
                type="text"
                class="form-control"
                placeholder="Symbol suchen..."
                wire:model.live.debounce.300ms="search">
        </div>
    </div>

    {{-- Tabelle --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                    <thead class="table-light">
                        <tr class="text-uppercase text-muted" style="font-size: 0.68rem;">
                            <th wire:click="sortBy('symbol')" style="cursor:pointer;" class="ps-3">
                                Symbol
                                @if($sortField === 'symbol')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('campaign_count')" style="cursor:pointer;" class="text-center">
                                Kampagnen
                                @if($sortField === 'campaign_count')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('last_activity')" style="cursor:pointer;" class="text-center">
                                Letzte Aktivität
                                @if($sortField === 'last_activity')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th class="text-end">Brutto P&L</th>
                            <th class="text-end">Kommission</th>
                            <th wire:click="sortBy('total_net_pnl')" style="cursor:pointer;" class="text-end">
                                Netto P&L
                                @if($sortField === 'total_net_pnl')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th class="text-center">Win Rate</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($symbols as $symbol)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('symbols.detail', $symbol->symbol) }}"
                                       class="text-decoration-none text-dark d-flex align-items-center gap-2">
                                        <strong class="fs-5">{{ $symbol->symbol }}</strong>
                                        @if($symbol->open_count > 0)
                                            <span class="badge bg-success" style="font-size: 0.65rem;">
                                                {{ $symbol->latest_status }}
                                            </span>
                                        @endif
                                    </a>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $symbol->campaign_count }}</span>
                                    @if($symbol->open_count > 0)
                                        <span class="badge bg-success ms-1" title="Laufende Kampagnen">{{ $symbol->open_count }}</span>
                                    @endif
                                </td>
                                <td class="text-center text-muted" style="font-size: 0.8rem;">
                                    @if($symbol->last_activity)
                                        {{ \Carbon\Carbon::parse($symbol->last_activity)->format('d.m.Y') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-currency-value :value="$symbol->total_pnl" :currency="'USD'" :colored="true" />
                                </td>
                                <td class="text-end">
                                    <x-currency-value :value="$symbol->total_commission" :currency="'USD'" />
                                </td>
                                <td class="text-end fw-bold">
                                    <x-currency-value :value="$symbol->total_net_pnl" :currency="'USD'" :colored="true" />
                                </td>
                                <td class="text-center">
                                    @if($symbol->win_rate !== null)
                                        <span class="badge bg-{{ $symbol->win_rate >= 50 ? 'success' : 'danger' }}">
                                            {{ $symbol->win_rate }}%
                                        </span>
                                        <div class="text-muted" style="font-size: 0.7rem;">
                                            {{ $symbol->win_count }}/{{ $symbol->closed_count }}
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('symbols.detail', $symbol->symbol) }}"
                                       class="btn btn-outline-secondary btn-sm py-0 px-2">
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    @if($search)
                                        Kein Symbol mit Kampagnen gefunden für „{{ $search }}"
                                    @else
                                        Keine Symbole mit Kampagnen im aktuellen Environment.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($symbols->count() > 1)
                    <tfoot>
                        <tr class="table-light fw-bold" style="font-size: 0.82rem;">
                            <td class="ps-3 text-muted text-uppercase">Gesamt</td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $symbols->sum('campaign_count') }}</span>
                            </td>
                            <td></td>
                            <td class="text-end">
                                <x-currency-value :value="$symbols->sum('total_pnl')" :currency="'USD'" :colored="true" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$symbols->sum('total_commission')" :currency="'USD'" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$symbols->sum('total_net_pnl')" :currency="'USD'" :colored="true" />
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
