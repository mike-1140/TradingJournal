<div>
    <x-page-header title="Trade Log" icon="journal-plus">
        <x-slot:actions>
            <a href="{{ route('log.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Neuer Eintrag
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Nicht dokumentiert: Kampagnen ohne Log-Eintrag --}}
    @if($unloggedCampaigns->isNotEmpty())
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning bg-opacity-25">
                <i class="bi bi-exclamation-circle"></i>
                {{ $unloggedCampaigns->count() }} Kampagne(n) ohne Log-Eintrag
                <span class="text-muted small">(letzte {{ $unloggedWindowDays }} Tage, inkl. offene)</span>
            </div>
            <div class="list-group list-group-flush">
                @foreach($unloggedCampaigns as $c)
                    @php $candidates = $candidatesByCampaign[$c->id] ?? collect(); @endphp
                    <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <strong class="fs-6">{{ $c->symbol }}</strong>
                            <span class="text-muted small">{{ $c->strategy }}</span>
                            <span class="badge bg-{{ $c->status === 'ACTIVE' ? 'primary' : ($c->status === 'PENDING' ? 'secondary' : 'success') }} ms-1">
                                {{ $c->status }}
                            </span>
                            <div class="text-muted small">
                                {{ $c->start_date?->format('d.m.Y') }}
                                @if($c->end_date) &rarr; {{ $c->end_date->format('d.m.Y') }} @endif
                            </div>
                        </div>

                        @if($candidates->isNotEmpty())
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($candidates as $plan)
                                    <button wire:click="linkPlanToCampaign({{ $plan->id }}, {{ $c->id }})"
                                            wire:confirm="Vorab-Plan vom {{ $plan->date_open->format('d.m.Y') }} mit dieser Kampagne verknüpfen?"
                                            class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-link-45deg"></i> Plan vom {{ $plan->date_open->format('d.m.Y') }} verknüpfen
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <a href="{{ route('log.create', ['campaign_id' => $c->id]) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-journal-plus"></i> Dokumentieren
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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
                title="Realisierter G/V"
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
    <x-trade-card title="Log-Einträge" icon="list-ul">
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
                    <th>Exit-Plan</th>
                    <th class="text-center">Legs</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">G/V</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap">{{ $log->date_open->format('d.m.Y') }}</td>
                        <td><strong class="fs-5">{{ $log->underlying }}</strong></td>
                        <td>{{ $log->strategy ?? '—' }}</td>
                        <td class="text-muted small" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $log->exit_plan ?? '—' }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $log->legs_count }}</span>
                        </td>
                        <td class="text-center">
                            @if($log->status === 'open')
                                <span class="badge bg-warning text-dark">Offen</span>
                            @else
                                <span class="badge bg-success">Geschlossen</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($log->status === 'closed' && $log->realized_pnl !== null)
                                <span class="{{ $log->realized_pnl >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                    {{ number_format($log->realized_pnl, 2, ',', '') }} $
                                </span>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('log.edit', $log->id) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button wire:click="deleteEntry({{ $log->id }})"
                                    wire:confirm="Eintrag wirklich löschen?"
                                    class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-empty-state
                                icon="journal-plus"
                                title="Keine Einträge vorhanden"
                                message="Erstelle deinen ersten Log-Eintrag." />
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
</div>
