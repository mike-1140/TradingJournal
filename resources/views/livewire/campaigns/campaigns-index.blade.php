<div>
    <x-page-header
        title="Kampagnen"
        subtitle="Kampagnen"
        icon="check-circle">
    </x-page-header>

    <x-date-range-info :startDate="$this->startDate" :endDate="$this->endDate" />

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Suche über alle Kampagnen hinweg</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Symbol, Underlying..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-4">
                    <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($globalView)
        <livewire:campaigns.global-symbol-detail
            :symbol="$searchTerm"
            :key="'global-'.$searchTerm"
        />
    @else
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div class="btn-group shadow-sm">
                    <button wire:click="setStatus('OPEN')"
                            class="btn btn-{{ $filterStatus == 'OPEN' ? 'primary' : 'outline-primary' }}">
                        Offen (Aktiv & Pending)
                    </button>
                    <button wire:click="setStatus('CLOSED')"
                            class="btn btn-{{ $filterStatus == 'CLOSED' ? 'primary' : 'outline-primary' }}">
                        Geschlossen
                    </button>
                    <button wire:click="setStatus('ALL')"
                            class="btn btn-{{ $filterStatus == 'ALL' ? 'primary' : 'outline-primary' }}">
                        Alle
                    </button>
                </div>
                @if($summary && $summary->campaign_count > 0)
                    <div class="d-flex align-items-center gap-3 bg-light rounded px-3 py-2">
                        <span class="text-muted small">
                            <i class="bi bi-collection me-1"></i>{{ $summary->campaign_count }} Kampagnen
                        </span>
                        <span class="text-muted small">Brutto PnL:</span>
                        <x-currency-value :value="$summary->total_pnl ?? 0" :currency="'USD'" :colored="true" class="fw-bold" />
                        <span class="text-muted small ms-2">Netto PnL:</span>
                        <x-currency-value :value="$summary->total_net_pnl ?? 0" :currency="'USD'" :colored="true" class="fw-bold" />
                    </div>
                @endif
            </div>

            <div class="row">
                @forelse($campaigns as $campaign)
                    <div class="col-md-4 mb-3">
                            @php
                                $borderColor = match($campaign->status) {
                                    'ACTIVE'  => 'success',
                                    'PENDING' => 'warning',
                                    default   => 'secondary',
                                };
                            @endphp
                            <div class="card h-100 shadow-sm border-0 campaign-card border-start border-4 border-{{ $borderColor }}"
                                 onclick="window.location='{{ route('campaigns.campaign.detail', $campaign->id) }}'"
                                 style="cursor: pointer;">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <a href="{{ route('symbols.detail', $campaign->symbol) }}"
                                       class="badge bg-dark fs-6 text-decoration-none campaign-symbol-link"
                                       onclick="event.stopPropagation()"
                                       title="Symbol-Übersicht öffnen (statt Kampagnen-Detail)">
                                        {{ $campaign->symbol }}
                                        <i class="bi bi-box-arrow-up-right" style="font-size: 0.65em;"></i>
                                    </a>
                                    <small class="text-muted">{{ $campaign->start_date->format('d.M.Y') }}</small>
                                    @if($loggedCampaignIds->contains($campaign->id))
                                        <span class="badge rounded-pill bg-success bg-opacity-25 text-success border border-success" style="font-size: 0.7rem;" title="Dokumentiert">Log</span>
                                    @else
                                        <span class="badge rounded-pill bg-light text-muted border" style="font-size: 0.7rem;" title="Nicht dokumentiert">Log</span>
                                    @endif
                                    @if($campaign->status === 'PENDING')
                                        <span class="badge rounded-pill bg-warning text-dark" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock-history"></i> Pending
                                        </span>
                                    @elseif($campaign->status === 'ACTIVE')
                                        <span class="badge rounded-pill bg-success" style="font-size: 0.7rem;">
                                            <i class="bi bi-activity"></i> Aktiv
                                        </span>
                                    @elseif($campaign->status === 'CLOSED')
                                        <span class="badge rounded-pill bg-secondary" style="font-size: 0.7rem;">
                                            <i class="bi bi-check-circle"></i> Closed
                                        </span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title text-dark">{{ $campaign->name }}</h5>
                                    <div class="row mt-3 g-2">
                                        <div class="col-6">
                                            <label class="small text-muted d-block">Brutto PnL</label>
                                            <x-currency-value :value="$campaign->total_pnl" :currency="'USD'" :colored="true" />
                                            @php $cardNetPnl = (float)($campaign->total_pnl ?? 0) + (float)($campaign->total_commission ?? 0); @endphp
                                            <small class="d-block text-muted" style="font-size: 0.75rem;">
                                                Netto: <x-currency-value :value="$cardNetPnl" :currency="'USD'" :colored="true" />
                                            </small>
                                        </div>
                                        <div class="col-6 text-end">
                                            <label class="small text-muted d-block">Bestand</label>
                                            <span class="fw-bold text-dark">{{ number_format($campaign->shares_balance, 0) }}</span>
                                        </div>
                                        <div class="col-6">
                                            <label class="small text-muted d-block">Proceeds</label>
                                            <x-currency-value :value="$campaign->total_proceeds" :currency="'USD'" />
                                        </div>
                                        <div class="col-6 text-end">
                                            <label class="small text-muted d-block">Commission</label>
                                            <x-currency-value :value="$campaign->total_commission" :currency="'USD'" />
                                        </div>
                                    </div>
                                </div>
                                @if($campaign->status == 'CLOSED')
                                    <div class="card-footer bg-light small text-center text-muted">
                                        <i class="bi bi-check-circle"></i>
                                        Beendet am: {{ $campaign->closed_at ? \Carbon\Carbon::parse($campaign->closed_at)->format('d.m.Y') : 'unbekannt' }}
                                    </div>
                                @endif
                            </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">Keine Kampagnen für diesen Filter gefunden.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-3">
                {{ $campaigns->links('components.pagination') }}
            </div>
        </div>
    @endif

    <style>
        .campaign-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .campaign-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
        }
        .campaign-symbol-link:hover {
            background-color: var(--bs-primary) !important;
        }
    </style>
</div>
