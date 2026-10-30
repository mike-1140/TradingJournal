{{--
  Eröffnungen einer Kampagne mit ihren Kommentaren (Kampagnen-Detail-Seite).
  Variablen: $view (CampaignDetail::openingsView), $campaign
--}}
@php($fmt = fn($v) => number_format((float) $v, 2, ',', '.'))
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h6 class="mb-0"><i class="bi bi-journal-text"></i> Eröffnungen &amp; Kommentare</h6>
        <span class="small text-muted">
            {{ $view['commented'] }} von {{ count($view['rows']) }} kommentiert
            @if((float) $view['stock_pnl'] != 0 || abs((float) $view['shares_open']) > 0.0001)
                · Optionen {{ $fmt($view['option_pnl']) }} $ · Aktien {{ $fmt($view['stock_pnl']) }} $ · Gesamt {{ $fmt($view['total_pnl']) }} $
                @if(abs((float) $view['shares_open']) > 0.0001)
                    · {{ rtrim(rtrim(number_format(abs((float) $view['shares_open']), 4, ',', ''), '0'), ',') }} Aktien offen
                @endif
            @endif
        </span>
    </div>
    <div class="list-group list-group-flush">
        @forelse($view['rows'] as $row)
            <div class="list-group-item">
                @include('livewire.log.partials.opening-summary', ['opening' => $row['opening'], 'details' => $row['details']])

                @if($row['plan'])
                    <div class="border-top pt-2 mt-2">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="small">
                                @if($row['plan']->strategy)
                                    <div><span class="text-muted">Strategie:</span> {{ $row['plan']->strategy }}</div>
                                @endif
                                @if($row['plan']->rationale)
                                    <div><span class="text-muted">Rationale:</span> {{ $row['plan']->rationale }}</div>
                                @endif
                                @if($row['plan']->exit_plan)
                                    <div><span class="text-muted">Exit-Plan:</span> {{ $row['plan']->exit_plan }}</div>
                                @endif
                                @if($row['plan']->notes)
                                    <div><span class="text-muted">Notizen:</span> {{ $row['plan']->notes }}</div>
                                @endif
                            </div>
                            <a href="{{ route('log.edit', $row['plan']->id) }}" class="btn btn-sm btn-outline-secondary py-0 text-nowrap">
                                <i class="bi bi-pencil"></i> Bearbeiten
                            </a>
                        </div>
                    </div>
                @else
                    <div class="text-end mt-2">
                        <a href="{{ route('log.create', ['campaign_id' => $campaign->id, 'opened_at' => $row['opening']['opened_at']]) }}"
                           class="btn btn-sm btn-outline-primary py-0">
                            <i class="bi bi-plus-circle"></i> Kommentar
                        </a>
                    </div>
                @endif
            </div>
        @empty
            <div class="list-group-item text-muted small text-center py-3">Keine Eröffnung in den Buchungen dieser Kampagne.</div>
        @endforelse

        @foreach($view['orphans'] as $plan)
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="small">
                        <i class="bi bi-exclamation-triangle text-warning"></i>
                        Eintrag vom {{ $plan->date_open->format('d.m.Y') }} ohne passende Eröffnung
                        @if($plan->rationale)
                            <div><span class="text-muted">Rationale:</span> {{ $plan->rationale }}</div>
                        @endif
                    </div>
                    <a href="{{ route('log.edit', $plan->id) }}" class="btn btn-sm btn-outline-secondary py-0 text-nowrap">
                        <i class="bi bi-pencil"></i> Bearbeiten
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
