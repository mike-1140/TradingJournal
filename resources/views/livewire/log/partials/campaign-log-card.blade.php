{{--
  Trade-Log-Karte für die Kampagnen-Detail-Seite.
  Variablen: $linkedPlan (TradePlan|null), $logCreateUrl (string)
--}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-journal-plus"></i> Trade Log</h6>
        @if ($linkedPlan)
        <a href="{{ route('log.edit', $linkedPlan->id) }}"
           class="btn btn-sm btn-outline-secondary py-0">
            <i class="bi bi-pencil"></i> Bearbeiten
        </a>
        @endif
    </div>

    <div class="card-body">
        @if (!$linkedPlan)
        {{-- Kein Eintrag --}}
        <div class="text-center py-3">
            <i class="bi bi-journal-plus fs-3 text-muted d-block mb-2"></i>
            <p class="text-muted small mb-3">Noch kein Log-Eintrag für diese Kampagne.</p>
            <a href="{{ $logCreateUrl }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-circle"></i> Dokumentieren
            </a>
        </div>
        @else
        {{-- Eintrag vorhanden --}}
        <div class="d-flex flex-wrap gap-3 align-items-start mb-2">
            <div>
                <div class="text-muted small mb-1">Status</div>
                <span class="badge {{ $linkedPlan->status === 'closed' ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $linkedPlan->status === 'closed' ? 'Geschlossen' : 'Offen' }}
                </span>
            </div>

            @if ($linkedPlan->strategy)
            <div>
                <div class="text-muted small mb-1">Strategie</div>
                <span class="small">{{ $linkedPlan->strategy }}</span>
            </div>
            @endif

            @if ($linkedPlan->status === 'closed' && $linkedPlan->realized_pnl !== null)
            <div>
                <div class="text-muted small mb-1">G/V</div>
                <span class="{{ $linkedPlan->realized_pnl >= 0 ? 'text-success' : 'text-danger' }} fw-bold small">
                    {{ number_format($linkedPlan->realized_pnl, 2, ',', '') }} $
                </span>
            </div>
            @endif
        </div>

        @if ($linkedPlan->rationale)
        <div class="border-top pt-2 mt-2">
            <div class="text-muted small mb-1">Rationale</div>
            <div class="small">{{ $linkedPlan->rationale }}</div>
        </div>
        @endif

        @if ($linkedPlan->exit_plan)
        <div class="border-top pt-2 mt-2">
            <div class="text-muted small mb-1">Exit-Plan</div>
            <div class="small">{{ $linkedPlan->exit_plan }}</div>
        </div>
        @endif

        @endif
    </div>
</div>
