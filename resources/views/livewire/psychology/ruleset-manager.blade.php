<div>
    <x-page-header title="Regelwerke verwalten" icon="shield-check">
        <x-slot:actions>
            <button wire:click="startNewRuleset" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Neues Regelwerk
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- ── Ruleset-Formular ─────────────────────────────────────────────────── --}}
    @if ($showRulesetForm)
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary bg-opacity-10">
            <h6 class="mb-0">
                <i class="bi bi-{{ $editingRulesetId ? 'pencil' : 'plus-circle' }}"></i>
                {{ $editingRulesetId ? 'Regelwerk bearbeiten' : 'Neues Regelwerk' }}
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm @error('rsName') is-invalid @enderror"
                           wire:model="rsName" placeholder="Credit Spread Basis">
                    @error('rsName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">Strategie-Typ</label>
                    <input type="text" class="form-control form-control-sm"
                           wire:model="rsStrategyType" placeholder="Credit Spread, Iron Condor...">
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Beschreibung</label>
                    <input type="text" class="form-control form-control-sm"
                           wire:model="rsDescription" placeholder="Kurzbeschreibung...">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" wire:model="rsIsActive" id="rs-active">
                        <label class="form-check-label form-label-sm" for="rs-active">Aktiv</label>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button wire:click="saveRuleset" class="btn btn-sm btn-primary">
                    <i class="bi bi-floppy"></i> Speichern
                </button>
                <button wire:click="cancelRulesetForm" class="btn btn-sm btn-outline-secondary">
                    Abbrechen
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Regelwerk-Liste ──────────────────────────────────────────────────── --}}
    @if ($rulesets->isEmpty())
    <div class="text-center text-muted py-5">
        <i class="bi bi-shield-x fs-2 d-block mb-2"></i>
        Noch keine Regelwerke.
        <button wire:click="startNewRuleset" class="btn btn-sm btn-outline-primary d-block mx-auto mt-2">
            Erstes Regelwerk anlegen
        </button>
    </div>
    @else

    <div class="d-flex flex-column gap-2">
        @foreach ($rulesets as $rs)
        @php $isSelected = $selectedRulesetId === $rs->id; @endphp

        <div class="card {{ $isSelected ? 'border-primary' : '' }}">
            {{-- Ruleset-Header --}}
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">

                    {{-- Aktiv-Toggle --}}
                    <div class="form-check form-switch mb-0" title="{{ $rs->is_active ? 'Aktiv' : 'Inaktiv' }}">
                        <input class="form-check-input" type="checkbox"
                               wire:click="toggleActive({{ $rs->id }})"
                               {{ $rs->is_active ? 'checked' : '' }}>
                    </div>

                    {{-- Name + Typ --}}
                    <div class="flex-grow-1" style="cursor:pointer" wire:click="selectRuleset({{ $rs->id }})">
                        <span class="fw-medium {{ $rs->is_active ? '' : 'text-muted text-decoration-line-through' }}">
                            {{ $rs->name }}
                        </span>
                        @if ($rs->strategy_type)
                        <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1 fw-normal">{{ $rs->strategy_type }}</span>
                        @endif
                        <small class="text-muted ms-2">{{ $rs->rules_count }} {{ $rs->rules_count === 1 ? 'Regel' : 'Regeln' }}</small>
                    </div>

                    {{-- Aktionen --}}
                    <button wire:click="startEditRuleset({{ $rs->id }})"
                            class="btn btn-sm btn-outline-secondary py-0" title="Bearbeiten">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button wire:click="deleteRuleset({{ $rs->id }})"
                            wire:confirm="Regelwerk '{{ $rs->name }}' und alle zugehörigen Regeln löschen?"
                            class="btn btn-sm btn-outline-danger py-0" title="Löschen">
                        <i class="bi bi-trash"></i>
                    </button>
                    <button wire:click="selectRuleset({{ $rs->id }})"
                            class="btn btn-sm btn-outline-{{ $isSelected ? 'primary' : 'secondary' }} py-0">
                        <i class="bi bi-chevron-{{ $isSelected ? 'up' : 'down' }}"></i>
                    </button>
                </div>

                @if ($rs->description)
                <div class="text-muted small ps-5 mt-1">{{ $rs->description }}</div>
                @endif
            </div>

            {{-- Regeln (ausgeklappt) --}}
            @if ($isSelected)
            <div class="card-footer bg-light p-0">

                {{-- Regel-Formular --}}
                @if ($showRuleForm)
                <div class="p-3 border-bottom">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('rKey') is-invalid @enderror"
                                   wire:model="rKey" placeholder="tp_50">
                            @error('rKey') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('rLabel') is-invalid @enderror"
                                   wire:model="rLabel" placeholder="TP bei 50% nehmen">
                            @error('rLabel') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Beschreibung</label>
                            <input type="text" class="form-control form-control-sm"
                                   wire:model="rDescription" placeholder="Optional...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Fehlertyp</label>
                            <select class="form-select form-select-sm" wire:model="rErrorType">
                                <option value="">— keiner —</option>
                                <option value="greed">Gier</option>
                                <option value="fear">Angst</option>
                                <option value="hope">Hoffnung</option>
                                <option value="impulse">Impuls</option>
                                <option value="discipline">Disziplin</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label form-label-sm">Reihenfolge</label>
                            <input type="number" class="form-control form-control-sm"
                                   wire:model="rSortOrder" min="0">
                        </div>
                        <div class="col-md-1 d-flex gap-1 align-items-end">
                            <button wire:click="saveRule" class="btn btn-sm btn-primary py-0">
                                <i class="bi bi-floppy"></i>
                            </button>
                            <button wire:click="cancelRuleForm" class="btn btn-sm btn-outline-secondary py-0">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Regeln-Tabelle --}}
                @if ($selectedRules && $selectedRules->isNotEmpty())
                <table class="table table-sm table-hover mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th style="width:80px">Reihenf.</th>
                            <th style="width:120px">Key</th>
                            <th>Label</th>
                            <th style="width:100px">Fehlertyp</th>
                            <th style="width:80px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($selectedRules as $rule)
                        <tr>
                            <td class="text-muted">{{ $rule->sort_order }}</td>
                            <td><code class="small">{{ $rule->rule_key }}</code></td>
                            <td>
                                <span>{{ $rule->label }}</span>
                                @if ($rule->description)
                                <div class="text-muted" style="font-size:11px">{{ $rule->description }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($rule->error_type)
                                @php
                                $errorColors = [
                                    'greed'      => 'warning',
                                    'fear'       => 'info',
                                    'hope'       => 'primary',
                                    'impulse'    => 'danger',
                                    'discipline' => 'success',
                                ];
                                $errorLabels = [
                                    'greed'      => 'Gier',
                                    'fear'       => 'Angst',
                                    'hope'       => 'Hoffnung',
                                    'impulse'    => 'Impuls',
                                    'discipline' => 'Disziplin',
                                ];
                                @endphp
                                <span class="badge bg-{{ $errorColors[$rule->error_type] ?? 'secondary' }} bg-opacity-25 text-{{ $errorColors[$rule->error_type] ?? 'secondary' }} fw-normal">
                                    {{ $errorLabels[$rule->error_type] ?? $rule->error_type }}
                                </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button wire:click="startEditRule({{ $rule->id }})"
                                        class="btn btn-xs btn-outline-secondary py-0 px-1">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button wire:click="deleteRule({{ $rule->id }})"
                                        wire:confirm="Regel '{{ $rule->label }}' löschen?"
                                        class="btn btn-xs btn-outline-danger py-0 px-1">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @elseif (!$showRuleForm)
                <div class="text-muted small p-3 fst-italic">Noch keine Regeln.</div>
                @endif

                {{-- Neue Regel --}}
                @if (!$showRuleForm)
                <div class="p-2">
                    <button wire:click="startNewRule" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-lg"></i> Neue Regel
                    </button>
                </div>
                @endif

            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
