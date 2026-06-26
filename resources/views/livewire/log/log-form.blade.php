<div>
<x-page-header
        :title="$planId ? 'Eintrag bearbeiten' : 'Neuer Eintrag'"
        icon="journal-plus">
        <x-slot:actions>
            <a href="{{ route('log.list') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="savePlan">

        {{-- ── Sektion 1: Kopfdaten ──────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Trade-Kopfdaten
            </div>
            <div class="card-body">

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Datum <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_open') is-invalid @enderror"
                               wire:model="date_open">
                        @error('date_open') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Underlying <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase @error('underlying') is-invalid @enderror"
                               wire:model="underlying"
                               placeholder="SPY, AAPL...">
                        @error('underlying') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kurs bei Open</label>
                        <input type="number" step="0.01" class="form-control @error('price_open') is-invalid @enderror"
                               wire:model="price_open">
                        @error('price_open') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">IVP</label>
                        <input type="number" min="0" max="100" class="form-control @error('ivp_open') is-invalid @enderror"
                               wire:model="ivp_open"
                               placeholder="0–100">
                        @error('ivp_open') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">IVR</label>
                        <input type="number" min="0" max="100" class="form-control @error('ivr_open') is-invalid @enderror"
                               wire:model="ivr_open"
                               placeholder="0–100">
                        @error('ivr_open') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Strategie</label>
                        <input type="text" list="strategy-suggestions"
                               class="form-control @error('strategy') is-invalid @enderror"
                               wire:model="strategy"
                               placeholder="Iron Condor, BuPS...">
                        <datalist id="strategy-suggestions">
                            <option value="Iron Condor">
                            <option value="BuPS">
                            <option value="BCS">
                            <option value="PCS">
                            <option value="Strangle">
                            <option value="Straddle">
                            <option value="Covered Call">
                            <option value="Cash Secured Put">
                        </datalist>
                        @error('strategy') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Exit-Plan / Regeln</label>
                        <textarea class="form-control @error('exit_plan') is-invalid @enderror"
                                  wire:model="exit_plan"
                                  rows="2"
                                  placeholder="TP bei 50%, SL bei 2x Prämie..."></textarea>
                        @error('exit_plan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-12">
                        <label class="form-label">Rationale</label>
                        <textarea wire:model="rationale" class="form-control form-control-sm" rows="2"
                                  placeholder="Warum dieser Trade? Welche Marktbedingung? Was erwartest du?"></textarea>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Sektion 2: Legs ──────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-layers"></i> Legs</span>
                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addLeg">
                    <i class="bi bi-plus-lg"></i> Leg hinzufügen
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 90px;">Leg</th>
                            <th style="width: 70px;">DTE</th>
                            <th style="width: 100px;">Strike</th>
                            <th style="width: 80px;">Delta</th>
                            <th style="width: 100px;">Prämie</th>
                            <th style="width: 100px;">Gebühren</th>
                            <th style="width: 110px;">Max. Risiko</th>
                            <th style="width: 40px;"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($legs as $index => $leg)
                            <tr>
                                <td>
                                    <input type="text"
                                           class="form-control form-control-sm @error('legs.'.$index.'.leg_type') is-invalid @enderror"
                                           wire:model="legs.{{ $index }}.leg_type"
                                           placeholder="LC, SC, SP, LP...">
                                    @error('legs.'.$index.'.leg_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           wire:model="legs.{{ $index }}.dte"
                                           placeholder="39">
                                </td>
                                <td>
                                    <input type="number" step="0.5"
                                           class="form-control form-control-sm"
                                           wire:model="legs.{{ $index }}.strike"
                                           placeholder="450">
                                </td>
                                <td>
                                    <input type="number" step="1"
                                           class="form-control form-control-sm"
                                           wire:model="legs.{{ $index }}.delta"
                                           placeholder="16">
                                </td>
                                <td>
                                    <input type="number" step="0.01"
                                           class="form-control form-control-sm"
                                           wire:model="legs.{{ $index }}.premium"
                                           placeholder="1.20">
                                </td>
                                <td>
                                    <input type="number" step="0.01"
                                           class="form-control form-control-sm"
                                           wire:model="legs.{{ $index }}.fees"
                                           placeholder="1.30">
                                </td>
                                <td>
                                    <input type="number" step="0.01"
                                           class="form-control form-control-sm"
                                           wire:model="legs.{{ $index }}.max_risk"
                                           placeholder="300">
                                </td>
                                <td>
                                    @if(count($legs) > 1)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="removeLeg({{ $index }})">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">
                                    Noch keine Legs — klicke "Leg hinzufügen"
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Status-Toggle ─────────────────────────────────────────────────── --}}
        <div class="mb-4">
            @if($status === 'open')
                <button type="button" class="btn btn-success" wire:click="toggleClose">
                    <i class="bi bi-check-circle"></i> Trade schließen
                </button>
            @else
                <button type="button" class="btn btn-outline-warning" wire:click="toggleClose">
                    <i class="bi bi-arrow-counterclockwise"></i> Wieder öffnen
                </button>
                <span class="badge bg-success ms-2">Geschlossen</span>
            @endif
        </div>

        {{-- ── Sektion 3: Closing (nur wenn showClosing) ────────────────────── --}}
        @if($showClosing)
        <div class="card mb-4 border-success">
            <div class="card-header bg-success bg-opacity-10">
                <i class="bi bi-flag-fill text-success"></i> Closing-Daten
            </div>
            <div class="card-body">

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Datum Close</label>
                        <input type="date" class="form-control @error('date_close') is-invalid @enderror"
                               wire:model="date_close">
                        @error('date_close') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kurs bei Close</label>
                        <input type="number" step="0.01" class="form-control"
                               wire:model="price_close">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">IVP Close</label>
                        <input type="number" min="0" max="100" class="form-control"
                               wire:model="ivp_close" placeholder="0–100">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">IVR Close</label>
                        <input type="number" min="0" max="100" class="form-control"
                               wire:model="ivr_close" placeholder="0–100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tatsächliche DTE</label>
                        <input type="number" class="form-control"
                               wire:model="actual_dte">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Prämie Close</label>
                        <input type="number" step="0.01" class="form-control"
                               wire:model="premium_close">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gebühren Close</label>
                        <input type="number" step="0.01" class="form-control"
                               wire:model="fees_close">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Realisierter G/V</label>
                        <div class="input-group">
                            <input type="number" step="0.01"
                                   class="form-control @error('realized_pnl') is-invalid @enderror"
                                   wire:model="realized_pnl">
                            <span class="input-group-text">$</span>
                            @error('realized_pnl') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label">Allgemeine Notizen</label>
                    <textarea class="form-control" wire:model="notes" rows="2"
                              placeholder="Sonstige Bemerkungen..."></textarea>
                </div>

            </div>
        </div>
        @endif

        {{-- ── Speichern ─────────────────────────────────────────────────────── --}}
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-floppy"></i> Speichern
            </button>
            <a href="{{ route('log.list') }}" class="btn btn-outline-secondary">
                Abbrechen
            </a>
        </div>

    </form>
</div>
