<div>
    <x-page-header title="Trade-Kommentare" icon="journal-text" />

    {{-- Statistiken --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <x-stat-card
                title="Offene Einträge"
                :value="$statistics['open_count']"
                color="warning"
                icon="hourglass-split" />
        </div>
        <div class="col-md-4">
            <x-stat-card
                title="Geschlossene Einträge"
                :value="$statistics['closed_count']"
                color="secondary"
                icon="check-circle" />
        </div>
        <div class="col-md-4">
            <x-stat-card
                title="Realisierter P&L"
                :value="number_format($statistics['realized_pnl'], 2, ',', '') . ' $'"
                :color="$statistics['realized_pnl'] >= 0 ? 'success' : 'danger'"
                icon="graph-up" />
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-end gap-3">
                <div>
                    <label class="form-label form-label-sm mb-1">Underlying</label>
                    <input type="text" class="form-control form-control-sm"
                           placeholder="SPY, AAPL..."
                           wire:model.live.debounce.300ms="search">
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="all">Alle</option>
                        <option value="open">Offen</option>
                        <option value="closed">Geschlossen</option>
                    </select>
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1">Pro Seite</label>
                    <select class="form-select form-select-sm" wire:model.live="perPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabelle --}}
    <x-trade-card title="Kommentare" icon="list-ul">
        <x-slot:actions>
            <span class="badge bg-primary">{{ $logs->total() }}</span>
        </x-slot:actions>

        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                <tr>
                    <th>Datum</th>
                    <th>Underlying</th>
                    <th>Strategie</th>
                    <th>Kommentar</th>
                    <th class="text-center">Legs</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">P&amp;L</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap">{{ $log->date_open->format('d.m.Y') }}</td>
                        <td><strong class="fs-5">{{ $log->underlying }}</strong></td>
                        <td>{{ $log->strategy ?? '—' }}</td>
                        <td class="small" style="min-width: 300px;">
                            @if($log->rationale)<div><span class="text-muted">Rationale:</span> {{ $log->rationale }}</div>@endif
                            @if($log->exit_plan)<div><span class="text-muted">Exit-Plan:</span> {{ $log->exit_plan }}</div>@endif
                            @if($log->notes)<div><span class="text-muted">Notizen:</span> {{ $log->notes }}</div>@endif
                            @if($log->lessons_learned)<div><span class="text-muted">Abschluss:</span> {{ $log->lessons_learned }}</div>@endif
                            @php $psych = $log->opening_at ? ($psychologyByKey[$log->campaign_id . '|' . $log->opening_at] ?? null) : null; @endphp
                            @if($psych)
                                <div class="mt-1 d-flex flex-wrap gap-1">
                                    @if($psych->motivation)<span class="badge bg-info bg-opacity-25 text-body fw-normal">{{ $psych->motivation }}</span>@endif
                                    @if($psych->emotion_pre || $psych->emotion_post)
                                        <span class="badge bg-secondary bg-opacity-25 text-body fw-normal">Emotion {{ $psych->emotion_pre ?? '–' }} → {{ $psych->emotion_post ?? '–' }}</span>
                                    @endif
                                    @if($psych->complianceResults->count() > 0)
                                        <span class="badge bg-secondary bg-opacity-25 text-body fw-normal">Regeln {{ $psych->complianceResults->where('complied', true)->count() }}/{{ $psych->complianceResults->count() }}</span>
                                    @endif
                                    @if($psych->complianceResults->where('tempted', true)->count() > 0)
                                        <span class="badge bg-warning bg-opacity-25 text-body fw-normal"><i class="bi bi-lightning-charge-fill"></i> {{ $psych->complianceResults->where('tempted', true)->count() }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $log->display_legs }}</span>
                        </td>
                        <td class="text-center">
                            @if($log->display_status === 'open')
                                <span class="badge bg-warning text-dark">Offen</span>
                            @else
                                <span class="badge bg-success">Geschlossen</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if($log->display_status === 'closed' && $log->display_pnl !== null)
                                <span class="{{ $log->display_pnl >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                    {{ number_format($log->display_pnl, 2, ',', '') }} $
                                </span>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if($log->opening_at)
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        wire:click="$dispatch('open-opening-log', { campaignId: {{ $log->campaign_id }}, openedAt: '{{ $log->opening_at }}' })">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            @else
                                <a href="{{ route('log.edit', $log->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endif
                            <button wire:click="deleteEntry({{ $log->id }})"
                                    wire:confirm="Eintrag und seine Psychologie wirklich löschen?"
                                    class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-empty-state
                                icon="journal-text"
                                title="Keine Kommentare vorhanden"
                                message="Kommentare entstehen über den Knopf in den Buchungszeilen einer Kampagne." />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            {{ $logs->links('components.pagination') }}
        </x-slot:footer>
    </x-trade-card>

    @if($unassignedPsychology->isNotEmpty())
        <div class="card mt-4 border-warning">
            <div class="card-header bg-warning bg-opacity-25 small">
                <i class="bi bi-exclamation-triangle"></i> Psychologie-Einträge ohne passenden Kommentar
            </div>
            <div class="list-group list-group-flush">
                @foreach($unassignedPsychology as $entry)
                    <a href="{{ route('psychology.edit', $entry->id) }}" class="list-group-item list-group-item-action small">
                        {{ $entry->trade_date->format('d.m.Y') }} · {{ $entry->symbol }}
                        @if($entry->motivation)· {{ $entry->motivation }}@endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <livewire:log.opening-log-modal />
</div>
