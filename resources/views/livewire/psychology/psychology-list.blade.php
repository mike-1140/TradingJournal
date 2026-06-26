<div>
    <x-page-header title="Trade-Psychologie" icon="journal-check">
        <x-slot:actions>
            <a href="{{ route('psychology.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Neuer Eintrag
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Statistiken --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <x-stat-card title="Einträge" :value="$stats['total']" color="secondary" icon="journal-check" />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Gesamt P&L"
                :value="number_format($stats['total_pnl'], 2, ',', '') . ' $'"
                :color="$stats['total_pnl'] >= 0 ? 'success' : 'danger'"
                icon="graph-up" />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Win-Rate"
                :value="$stats['total'] > 0 ? round($stats['wins'] / $stats['total'] * 100) . '%' : '–'"
                color="primary"
                icon="trophy" />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Mit Versuchung"
                :value="$stats['total'] > 0 ? round($stats['with_tempt'] / $stats['total'] * 100) . '%' : '–'"
                color="warning"
                icon="lightning-charge" />
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-end gap-3">
                <div>
                    <label class="form-label form-label-sm mb-1">Symbol</label>
                    <input type="text" class="form-control form-control-sm"
                           placeholder="SPY, AAPL..."
                           wire:model.live.debounce.300ms="search">
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1">Motivation</label>
                    <select class="form-select form-select-sm" wire:model.live="filterMotivation">
                        <option value="">Alle</option>
                        <option value="SYSTEM">System</option>
                        <option value="FOMO">FOMO</option>
                        <option value="REVENGE">Revenge</option>
                        <option value="BOREDOM">Langeweile</option>
                        <option value="IMPULSE">Impuls</option>
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

    {{-- Einträge --}}
    @if ($entries->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
            Noch keine Einträge.
            <a href="{{ route('psychology.create') }}" class="d-block mt-2">Ersten Eintrag erfassen</a>
        </div>
    @else
        <div class="d-flex flex-column gap-2 mb-3">
            @foreach ($entries as $entry)
            @php
                $complied  = $entry->complianceResults->where('complied', true)->count();
                $total     = $entry->complianceResults->count();
                $tempted   = $entry->complianceResults->where('tempted', true)->count();
                $pnlColor  = $entry->pnl === null ? 'text-muted' : ($entry->pnl >= 0 ? 'text-success' : 'text-danger');
                $motivationStyles = [
                    'SYSTEM'  => ['bg-success bg-opacity-10 text-success border-success',   'System'],
                    'FOMO'    => ['bg-warning bg-opacity-10 text-warning border-warning',   'FOMO'],
                    'REVENGE' => ['bg-danger bg-opacity-10 text-danger border-danger',      'Revenge'],
                    'BOREDOM' => ['bg-secondary bg-opacity-10 text-secondary border-secondary', 'Langeweile'],
                    'IMPULSE' => ['bg-dark bg-opacity-10 text-dark border-dark',            'Impuls'],
                ];
                $motStyle = $motivationStyles[$entry->motivation] ?? null;
            @endphp
            <div class="card">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap">

                        {{-- Datum --}}
                        <span class="text-muted small" style="min-width:80px">
                            {{ \Carbon\Carbon::parse($entry->trade_date)->format('d.m.Y') }}
                        </span>

                        {{-- Symbol --}}
                        <span class="fw-medium">{{ $entry->symbol }}</span>

                        {{-- Motivation-Badge --}}
                        @if ($motStyle)
                        <span class="badge border {{ $motStyle[0] }} fw-normal">{{ $motStyle[1] }}</span>
                        @endif

                        {{-- Versuchungs-Badge --}}
                        @if ($tempted > 0)
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning fw-normal">
                            <i class="bi bi-lightning-charge-fill"></i> {{ $tempted }}
                        </span>
                        @endif

                        {{-- Compliance-Dots --}}
                        @if ($total > 0)
                        <div class="d-flex gap-1 ms-1">
                            @foreach ($entry->complianceResults as $result)
                            <span class="rounded-circle d-inline-block"
                                  style="width:8px;height:8px;background:{{ $result->complied ? '#198754' : '#dc3545' }}"
                                  title="{{ $result->complied ? 'Eingehalten' : 'Verletzt' }}"></span>
                            @endforeach
                        </div>
                        @endif

                        {{-- P&L --}}
                        <span class="ms-auto fw-medium {{ $pnlColor }}">
                            @if ($entry->pnl !== null)
                                {{ $entry->pnl >= 0 ? '+' : '' }}{{ number_format($entry->pnl, 2, ',', '') }} $
                            @else
                                –
                            @endif
                        </span>

                        {{-- Aktionen --}}
                        <a href="{{ route('psychology.edit', $entry->id) }}"
                           class="btn btn-sm btn-outline-secondary py-0">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button wire:click="delete({{ $entry->id }})"
                                wire:confirm="Eintrag wirklich löschen?"
                                class="btn btn-sm btn-outline-danger py-0">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    {{-- Lektion --}}
                    @if ($entry->lesson)
                    <div class="text-muted small fst-italic mt-1 ps-1">{{ $entry->lesson }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{ $entries->links() }}
    @endif
</div>
