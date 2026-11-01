{{-- Reiter Psychologie im Log-Fenster: nichts ist Pflicht, ohne Angaben entsteht kein Eintrag. --}}
@if(!$psychologyAvailable)
    <div class="alert alert-secondary small mb-0">
        <i class="bi bi-database-exclamation"></i>
        Das Psychologie-Modul ist für dieses Environment nicht eingerichtet.
    </div>
@else
    <label class="form-label small mb-1">Emotionaler Zustand <span class="text-muted">1 = ängstlich · 3 = neutral · 5 = euphorisch</span></label>
    <div class="row g-3 mb-3">
        @foreach(['pre' => ['Vor dem Trade', $emotionPre], 'post' => ['Nach dem Trade', $emotionPost]] as $type => [$label, $value])
            <div class="col-md-6">
                <div class="text-muted small mb-1">{{ $label }}</div>
                <div class="btn-group d-flex" role="group">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" class="btn btn-sm flex-fill {{ $value == $i ? 'btn-secondary' : 'btn-outline-secondary' }}"
                                wire:click="setEmotion('{{ $type }}', {{ $i }})">{{ $i }}</button>
                    @endfor
                </div>
            </div>
        @endforeach
    </div>

    <label class="form-label small mb-1">Motivation</label>
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach([
            'SYSTEM'  => ['System', 'success'],
            'FOMO'    => ['FOMO', 'warning'],
            'REVENGE' => ['Revenge', 'danger'],
            'BOREDOM' => ['Langeweile', 'secondary'],
            'IMPULSE' => ['Impuls', 'dark'],
        ] as $key => [$label, $color])
            <button type="button" class="btn btn-sm rounded-pill {{ $motivation === $key ? 'btn-' . $color : 'btn-outline-' . $color }}"
                    wire:click="setMotivation('{{ $key }}')">{{ $label }}</button>
        @endforeach
    </div>

    <label class="form-label small mb-1">Marktkontext</label>
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['trending' => 'Trending', 'choppy' => 'Choppy', 'high_iv' => 'Hohe IV', 'low_iv' => 'Niedrige IV', 'news' => 'News-Event'] as $key => $label)
            <button type="button" class="btn btn-sm rounded-pill {{ $marketContext === $key ? 'btn-primary' : 'btn-outline-primary' }}"
                    wire:click="setMarketContext('{{ $key }}')">{{ $label }}</button>
        @endforeach
    </div>

    <label class="form-label small mb-1">Regelwerk</label>
    <select class="form-select form-select-sm w-auto mb-3" wire:model.live="selectedRulesetId">
        <option value="">— kein Regelwerk —</option>
        @foreach($rulesets as $ruleset)
            <option value="{{ $ruleset['id'] }}">{{ $ruleset['name'] }}</option>
        @endforeach
    </select>

    @if(count($rulesList) > 0)
        <div class="text-muted small mb-1">Regeln eingehalten <span class="text-success">(grün = ja)</span></div>
        <div class="row g-2 mb-3">
            @foreach($rulesList as $rule)
                @php $complied = $complianceValues[$rule['id']] ?? false; @endphp
                <div class="col-md-6" wire:key="comply-{{ $rule['id'] }}">
                    <div class="p-2 rounded border h-100 {{ $complied ? 'border-success bg-success bg-opacity-10' : 'border-secondary-subtle' }}"
                         style="cursor: pointer;" wire:click="toggleCompliance({{ $rule['id'] }})">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi {{ $complied ? 'bi-check-circle-fill text-success' : 'bi-circle text-secondary' }} mt-1 flex-shrink-0"></i>
                            <div>
                                <div class="small fw-medium {{ $complied ? 'text-success' : '' }}">{{ $rule['label'] }}</div>
                                @if($rule['description'])
                                    <div class="text-muted" style="font-size: 11px;">{{ $rule['description'] }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-muted small mb-1">Regelversuchungen <span class="text-warning">(Impuls war da, aber nicht ausgeführt)</span></div>
        <div class="border rounded px-3 py-1">
            @foreach($rulesList as $rule)
                @php $tempted = $temptationValues[$rule['id']] ?? false; @endphp
                <div class="d-flex align-items-center gap-3 py-1 {{ !$loop->last ? 'border-bottom' : '' }}" wire:key="tempt-{{ $rule['id'] }}"
                     style="cursor: pointer;" wire:click="toggleTemptation({{ $rule['id'] }})">
                    <i class="bi {{ $tempted ? 'bi-check-square-fill text-warning' : 'bi-square text-secondary' }} flex-shrink-0"></i>
                    <span class="small {{ $tempted ? 'text-warning fw-medium' : 'text-muted' }}">Wollte: {{ $rule['label'] }}</span>
                </div>
            @endforeach
        </div>
    @elseif($selectedRulesetId)
        <div class="text-muted small fst-italic">Keine Regeln in diesem Regelwerk.</div>
    @endif
@endif
