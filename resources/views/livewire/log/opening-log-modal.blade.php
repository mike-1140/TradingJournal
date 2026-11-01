<div>
    @if($show && $opening)
        <div style="position: fixed; inset: 0; z-index: 1055; background: rgba(0,0,0,0.4);" wire:keydown.escape.window="close">
            {{-- Kopfzeile zum Verschieben, rechte untere Ecke zum Ändern der Größe; Inhalt scrollt im Mittelteil.
                 wire:ignore.self: Position und Größe überstehen das Neuzeichnen (z. B. bei Eingabefehlern). --}}
            <div class="log-window bg-body rounded shadow-lg d-flex flex-column" wire:ignore.self
                 x-data="{ x: 0, y: 0, dragging: false, startX: 0, startY: 0 }"
                 :style="`transform: translate(${x}px, ${y}px)`"
                 @pointermove.window="if (dragging) { x = $event.clientX - startX; y = Math.max($event.clientY - startY, -$el.offsetTop); }"
                 @pointerup.window="dragging = false">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom"
                     style="cursor: move; touch-action: none; user-select: none;"
                     @pointerdown="if (!$event.target.closest('button')) { dragging = true; startX = $event.clientX - x; startY = $event.clientY - y; }">
                    <h6 class="mb-0">
                        <i class="bi bi-arrows-move text-muted me-1"></i>
                        <i class="bi bi-journal-text"></i> {{ $underlying }} · Eröffnung dokumentieren
                    </h6>
                    <button type="button" class="btn-close" wire:click="close" aria-label="Schließen"></button>
                </div>

                <ul class="nav nav-tabs px-3 pt-2">
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $tab === 'trade' ? 'active' : '' }}" wire:click="setTab('trade')">
                            <i class="bi bi-journal-text"></i> Trade
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $tab === 'psychology' ? 'active' : '' }}" wire:click="setTab('psychology')">
                            <i class="bi bi-emoji-smile"></i> Psychologie
                        </button>
                    </li>
                </ul>

                <form wire:submit="save" class="d-flex flex-column flex-grow-1" style="min-height: 0;">
                    <div class="flex-grow-1 p-3 {{ $tab === 'psychology' ? '' : 'd-none' }}" style="overflow-y: auto; min-height: 0;">
                        @include('livewire.log.partials.psychology-tab')
                    </div>
                    <div class="flex-grow-1 p-3 {{ $tab === 'trade' ? '' : 'd-none' }}" style="overflow-y: auto; min-height: 0;">
                        @include('livewire.log.partials.opening-summary', ['opening' => $opening, 'details' => $details])

                        <hr>

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
                            <textarea wire:model="rationale" class="form-control" rows="4"
                                      placeholder="Warum dieser Trade? Welche Marktbedingung? Was erwartest du?"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notizen</label>
                            <textarea wire:model="notes" class="form-control" rows="3"></textarea>
                        </div>
                        @if($opening['state'] === 'closed')
                            <div class="mb-3">
                                <label class="form-label">Abschluss / Lessons Learned</label>
                                <textarea wire:model="lessons_learned" class="form-control" rows="4"
                                          placeholder="Wie ist der Trade ausgegangen? Was nimmst du mit?"></textarea>
                            </div>
                        @endif

                        @php
                            $ivFields = [['price_open', 'Kurs bei Open'], ['ivp_open', 'IVP Open'], ['ivr_open', 'IVR Open']];
                            if ($opening['closed_at']) {
                                $ivFields = array_merge($ivFields, [['price_close', 'Kurs bei Close'], ['ivp_close', 'IVP Close'], ['ivr_close', 'IVR Close']]);
                            }
                        @endphp
                        <div class="row g-3">
                            @foreach($ivFields as [$field, $label])
                                <div class="col-md-2 col-4">
                                    <label class="form-label small">{{ $label }}</label>
                                    <input type="number" step="any" class="form-control form-control-sm @error($field) is-invalid @enderror"
                                           wire:model="{{ $field }}" placeholder="{{ $details[$field] ?? '' }}">
                                    @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @if(($this->{$field} === null || $this->{$field} === '') && ($details[$field] ?? null) !== null)
                                        <div class="form-text text-info small" title="Vom Weaver-Schritt enrich aus IBKR-Daten berechnet"><i class="bi bi-cpu"></i> berechnet</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 px-3 py-2 border-top">
                        <a href="{{ route('campaigns.campaign.detail', $campaignId) }}" class="btn btn-sm btn-link me-auto">
                            <i class="bi bi-link-45deg"></i> Kampagne
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="close">Abbrechen</button>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-floppy"></i> Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <style>
            .log-window {
                position: absolute;
                top: 4vh;
                left: 50%;
                margin-left: min(-550px, -48vw);
                width: min(1100px, 96vw);
                height: 90vh;
                min-width: 420px;
                min-height: 320px;
                resize: both;
                overflow: hidden;
            }
        </style>
    @endif
</div>
