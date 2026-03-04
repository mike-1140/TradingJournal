<div>
    {{-- 2. PAGE HEADER mit Environment Switcher --}}
    <x-page-header
        title="Kampagnen"
        subtitle="Kampagnen"
        icon="check-circle">
    </x-page-header>

    <!-- Filter -->
    <div class="text-muted small mt-1">
        @if($this->startDate)
            <i class="bi bi-calendar3"></i>
            Daten seit dem {{ $this->startDate->format('d.m.Y') }}
        @else
            <i class="bi bi-calendar3"></i>
            Alle historischen Daten
        @endif
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
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
        {{-- Hier rufen wir eine spezialisierte Komponente auf damit wir alles von diesem Symbol darstellen können --}}
        <livewire:campaigns.global-symbol-detail
            :symbol="$searchTerm"
            :key="'global-'.$searchTerm"
        />
    @else
        <div class="container-fluid py-4">
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


            <div class="row">
                @forelse($campaigns as $campaign)

                    <div class="col-md-4 mb-3">
                        <div class="card h-100 shadow-sm {{ $selectedCampaignId == $campaign->id ? 'border-primary' : '' }}">
                            <div class="card-header d-flex justify-content-between">
                                <span class="badge bg-dark">{{ $campaign->symbol }}</span>
                                <small class="text-muted">{{ $campaign->start_date->format('d.M.Y') }}</small>
                                @if($campaign->status === 'PENDING')
                                    <span class="badge rounded-pill bg-warning text-dark italic" style="font-size: 0.7rem;">
                                        <i class="bi bi-clock-history"></i> Pending
                                    </span>
                                @elseif($campaign->status === 'ACTIVE')
                                    <span class="badge rounded-pill bg-success" style="font-size: 0.7rem;">
                                        Aktiv
                                    </span>
                                @endif
                            </div>
                            <div class="card-body" wire:click="selectCampaign({{ $campaign->id }})" style="cursor: pointer;">
                                <h5 class="card-title">{{ $campaign->name }}</h5>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <label class="small text-muted d-block">PnL</label>
                                        <x-currency-value :value="$campaign->total_pnl" :currency="'USD'" />
                                    </div>
                                    <div class="col-6 text-end">
                                        <label class="small text-muted d-block">Bestand</label>
                                        <span class="fw-bold">{{ number_format($campaign->shares_balance, 0) }}</span>
                                    </div>
                                </div>
                            </div>
                            @if($campaign->status == 'CLOSED')
                                <div class="card-footer bg-light small text-center">
                                    Beendet am: {{ $campaign->closed_at ?? 'unbekannt' }}
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

            <div class="card-footer bg-white">
                {{ $campaigns->links('components.pagination') }}
            </div>

            <div id="ledger-start" style="scroll-margin-top: 100px;"></div>
            @if($selectedCampaignId)
                <div class="mt-5 pt-4 border-top"
                     wire:key="ledger-container-{{ $selectedCampaignId }}"
                     style="min-height: 600px;"> <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Transaktions-Details</h3>
                        <button wire:click="selectCampaign(null)" class="btn btn-sm btn-outline-secondary">Schließen</button>
                    </div>
                    @if($selectedCampaignId)
                        <div id="ledger-start" class="mt-4">
                            <livewire:campaigns.campaign-detail
                                :campaignId="$selectedCampaignId"
                                :wire:key="'detail-'.$selectedCampaignId"
                            />
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('scroll-to-ledger', () => {
            // Wir warten kurz, bis Livewire das DOM-Update abgeschlossen hat
            setTimeout(() => {
                const element = document.getElementById('ledger-start');
                if (element) {
                    const yOffset = -100; // Falls du kein scroll-margin nutzt, hier manueller Offset
                    const y = element.getBoundingClientRect().top + window.pageYOffset + yOffset;

                    window.scrollTo({
                        top: y,
                        behavior: 'smooth'
                    });
                }
            }, 150); // 150ms ist meist der "Sweet Spot" für das Rendering
        });
    });
</script>

