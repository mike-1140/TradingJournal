{{-- Kommentar zu einer Eröffnung: Zahlen aus dem Ledger, Eingabe nur für das, was das Ledger nicht weiß. --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-layers"></i> {{ $underlying }} · Eröffnung</span>
        <a href="{{ route('campaigns.campaign.detail', $campaignId) }}" class="btn btn-sm btn-outline-secondary py-0">
            <i class="bi bi-link-45deg"></i> Kampagne
        </a>
    </div>
    <div class="card-body">
        @include('livewire.log.partials.opening-summary', ['opening' => $opening, 'details' => $details])
    </div>
</div>

<form wire:submit="savePlan">
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-chat-left-text"></i> Kommentar</div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Strategie</label>
                    <input type="text" class="form-control @error('strategy') is-invalid @enderror" wire:model="strategy"
                           placeholder="Iron Condor, BuPS...">
                    @error('strategy') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Exit-Plan / Regeln</label>
                    <textarea class="form-control" wire:model="exit_plan" rows="2"></textarea>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Rationale</label>
                <textarea wire:model="rationale" class="form-control" rows="3"
                          placeholder="Warum dieser Trade? Welche Marktbedingung? Was erwartest du?"></textarea>
            </div>
            <div>
                <label class="form-label">Notizen</label>
                <textarea wire:model="notes" class="form-control" rows="2"
                          placeholder="Verlauf, Zuteilung, was du gelernt hast..."></textarea>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-cpu"></i> Kurs und IV</div>
        <div class="card-body">
            @php
                $ivFields = [
                    ['price_open', 'Kurs bei Open', 'number', 'step=0.01'],
                    ['ivp_open', 'IVP Open', 'number', 'min=0 max=100'],
                    ['ivr_open', 'IVR Open', 'number', 'min=0 max=100'],
                ];
                if ($opening['closed_at']) {
                    $ivFields = array_merge($ivFields, [
                        ['price_close', 'Kurs bei Close', 'number', 'step=0.01'],
                        ['ivp_close', 'IVP Close', 'number', 'min=0 max=100'],
                        ['ivr_close', 'IVR Close', 'number', 'min=0 max=100'],
                    ]);
                }
            @endphp
            <div class="row g-3">
                @foreach($ivFields as [$field, $label])
                    <div class="col-md-2">
                        <label class="form-label">{{ $label }}</label>
                        <input type="number" step="any" class="form-control @error($field) is-invalid @enderror"
                               wire:model="{{ $field }}" placeholder="{{ $details[$field] ?? '' }}">
                        @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if(($this->{$field} === null || $this->{$field} === '') && ($details[$field] ?? null) !== null)
                            <div class="form-text text-info small" title="Vom Weaver-Schritt enrich aus IBKR-Daten berechnet"><i class="bi bi-cpu"></i> aus IBKR berechnet</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy"></i> Speichern
        </button>
        <a href="{{ route('log.list') }}" class="btn btn-outline-secondary">Abbrechen</a>
    </div>
</form>
