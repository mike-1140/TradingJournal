<div>
    <x-page-header
        :title="$entryId ? 'Psychologie bearbeiten' : 'Trade-Psychologie erfassen'"
        icon="journal-check">
        <x-slot:actions>
            <a href="{{ route('psychology.list') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </x-slot:actions>
    </x-page-header>

    @if($prefillTradeId || $prefillStrategyId)
    <div class="alert alert-info py-2 mb-3 small d-flex align-items-center gap-2">
        <i class="bi bi-link-45deg"></i>
        <span>
            Verknüpft mit
            @if(in_array($prefillTradeType, ['spread', 'open_spread']))
                Spread <strong>{{ $prefillStrategyId }}</strong>
            @else
                Trade <strong>#{{ $prefillTradeId }}</strong>
            @endif
        </span>
        @if(in_array($prefillTradeType, ['open', 'open_spread']))
            <span class="badge bg-primary">Eröffnung</span>
        @else
            <span class="badge bg-secondary">Abschluss</span>
        @endif
        <button type="button" wire:click="unlinkTrade" class="btn btn-sm btn-link text-danger ms-auto py-0">
            <i class="bi bi-x-circle"></i> Verknüpfung entfernen
        </button>
    </div>
    @elseif(count($linkCandidates) > 0)
    <div class="alert alert-warning py-2 mb-3 small">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-search"></i>
            Trades zu <strong>{{ strtoupper($symbol) }}</strong> gefunden — gezielt nachträglich verknüpfen?
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($linkCandidates as $c)
                @php $isOpenSide = in_array($c['type'], ['open', 'open_spread'], true); @endphp
                <button type="button" wire:click="linkToTrade('{{ $c['type'] }}', '{{ $c['id'] }}')"
                        class="btn btn-sm btn-outline-{{ $isOpenSide ? 'primary' : 'secondary' }}">
                    <i class="bi {{ in_array($c['type'], ['open_spread', 'spread'], true) ? 'bi-diagram-3' : 'bi-link-45deg' }}"></i>
                    {{ $c['label'] }}
                </button>
            @endforeach
        </div>
    </div>
    @endif

    <form wire:submit="save">

        {{-- ── Basisdaten ────────────────────────────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle"></i> Trade-Daten</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Symbol <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase @error('symbol') is-invalid @enderror"
                               wire:model="symbol" placeholder="SPY, AAPL...">
                        @error('symbol') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Trade-Datum <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tradeDate') is-invalid @enderror"
                               wire:model="tradeDate">
                        @error('tradeDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">P&amp;L</label>
                        <div class="input-group">
                            <input type="number" step="0.01"
                                   class="form-control @error('pnl') is-invalid @enderror"
                                   wire:model="pnl" placeholder="85.00">
                            <span class="input-group-text">$</span>
                        </div>
                        @error('pnl') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Emotionen ─────────────────────────────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-emoji-smile"></i> Emotionaler Zustand <small class="text-muted fw-normal">1 = ängstlich · 3 = neutral · 5 = euphorisch</small></div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-2">Vor dem Trade</label>
                        <div class="btn-group d-flex" role="group">
                            @for ($i = 1; $i <= 5; $i++)
                            <button type="button"
                                    class="btn btn-sm flex-fill {{ $emotionPre == $i ? 'btn-secondary' : 'btn-outline-secondary' }}"
                                    wire:click="setEmotion('pre', {{ $i }})">{{ $i }}</button>
                            @endfor
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-2">Nach dem Trade</label>
                        <div class="btn-group d-flex" role="group">
                            @for ($i = 1; $i <= 5; $i++)
                            <button type="button"
                                    class="btn btn-sm flex-fill {{ $emotionPost == $i ? 'btn-secondary' : 'btn-outline-secondary' }}"
                                    wire:click="setEmotion('post', {{ $i }})">{{ $i }}</button>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Motivation + Marktkontext ─────────────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-lightning-charge"></i> Motivation &amp; Kontext</div>
            <div class="card-body">

                <label class="form-label text-muted small">Motivation</label>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    @php
                    $motivations = [
                        'SYSTEM'  => ['label' => 'System',     'active' => 'btn-success',  'inactive' => 'btn-outline-success'],
                        'FOMO'    => ['label' => 'FOMO',       'active' => 'btn-warning',  'inactive' => 'btn-outline-warning'],
                        'REVENGE' => ['label' => 'Revenge',    'active' => 'btn-danger',   'inactive' => 'btn-outline-danger'],
                        'BOREDOM' => ['label' => 'Langeweile', 'active' => 'btn-secondary','inactive' => 'btn-outline-secondary'],
                        'IMPULSE' => ['label' => 'Impuls',     'active' => 'btn-dark',     'inactive' => 'btn-outline-dark'],
                    ];
                    @endphp
                    @foreach ($motivations as $key => $m)
                    <button type="button"
                            class="btn btn-sm rounded-pill {{ $motivation === $key ? $m['active'] : $m['inactive'] }}"
                            wire:click="setMotivation('{{ $key }}')">
                        {{ $m['label'] }}
                    </button>
                    @endforeach
                </div>

                <label class="form-label text-muted small">Marktkontext</label>
                <div class="d-flex flex-wrap gap-2">
                    @php
                    $contexts = [
                        'trending' => 'Trending',
                        'choppy'   => 'Choppy',
                        'high_iv'  => 'Hohe IV',
                        'low_iv'   => 'Niedrige IV',
                        'news'     => 'News-Event',
                    ];
                    @endphp
                    @foreach ($contexts as $key => $label)
                    <button type="button"
                            class="btn btn-sm rounded-pill {{ $marketContext === $key ? 'btn-primary' : 'btn-outline-primary' }}"
                            wire:click="setMarketContext('{{ $key }}')">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>

            </div>
        </div>

        {{-- ── Regelwerk ─────────────────────────────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-shield-check"></i> Regelwerk &amp; Compliance</div>
            <div class="card-body">

                {{-- Regelwerk-Auswahl --}}
                <div class="mb-4">
                    <label class="form-label">Regelwerk</label>
                    <select class="form-select form-select-sm w-auto" wire:model.live="selectedRulesetId">
                        <option value="">— kein Regelwerk —</option>
                        @foreach ($rulesets as $rs)
                        <option value="{{ $rs['id'] }}">{{ $rs['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                @if (count($rulesList) > 0)

                {{-- Compliance-Grid --}}
                <label class="form-label text-muted small mb-2">Regeln eingehalten <span class="text-success">(grün = ja)</span></label>
                <div class="row g-2 mb-4">
                    @foreach ($rulesList as $rule)
                    @php $complied = $complianceValues[$rule['id']] ?? false; @endphp
                    <div class="col-md-6">
                        <div class="p-3 rounded border h-100 {{ $complied ? 'border-success bg-success bg-opacity-10' : 'border-secondary-subtle bg-light' }}"
                             style="cursor:pointer"
                             wire:click="toggleCompliance({{ $rule['id'] }})">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi {{ $complied ? 'bi-check-circle-fill text-success' : 'bi-circle text-secondary' }} mt-1 flex-shrink-0"></i>
                                <div>
                                    <div class="small fw-medium {{ $complied ? 'text-success' : '' }}">{{ $rule['label'] }}</div>
                                    @if($rule['description'])
                                    <div class="text-muted" style="font-size:11px">{{ $rule['description'] }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Regelversuchungen --}}
                <label class="form-label text-muted small mb-2">Regelversuchungen <span class="text-warning">(Impuls war da, aber nicht ausgeführt)</span></label>
                <div class="border rounded p-3">
                    @foreach ($rulesList as $index => $rule)
                    @php $tempted = $temptationValues[$rule['id']] ?? false; @endphp
                    <div class="d-flex align-items-center gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}"
                         style="cursor:pointer"
                         wire:click="toggleTemptation({{ $rule['id'] }})">
                        <i class="bi {{ $tempted ? 'bi-check-square-fill text-warning' : 'bi-square text-secondary' }} flex-shrink-0"></i>
                        <span class="small {{ $tempted ? 'text-warning fw-medium' : 'text-muted' }}">
                            Wollte: {{ $rule['label'] }}
                        </span>
                    </div>
                    @endforeach
                </div>

                @elseif ($selectedRulesetId)
                <div class="text-muted small fst-italic">Keine Regeln in diesem Regelwerk.</div>
                @endif

            </div>
        </div>

        {{-- ── Lektion ───────────────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-pencil"></i> Lektion / Muster <small class="text-muted fw-normal">1 Satz</small></div>
            <div class="card-body">
                <textarea class="form-control @error('lesson') is-invalid @enderror"
                          wire:model="lesson"
                          rows="2"
                          placeholder="z.B. Impuls den TP zu erhöhen war stark – aber System eingehalten."></textarea>
                @error('lesson') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- ── Speichern ─────────────────────────────────────────────────────── --}}
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-floppy"></i> Speichern
            </button>
            <a href="{{ route('psychology.list') }}" class="btn btn-outline-secondary">
                Abbrechen
            </a>
        </div>

    </form>
</div>
