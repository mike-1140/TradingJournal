{{-- Knopf in der Buchungszeile: einmal je Eröffnung, weitere Legs zeigen nur die Zugehörigkeit. Variable: $marker --}}
@if($marker)
    @if($marker['first'])
        <button type="button"
                class="btn btn-sm py-0 px-1 position-relative {{ $marker['documented'] ? 'btn-success' : 'btn-outline-warning' }}"
                title="{{ $marker['documented'] ? 'Dokumentiert – öffnen' : 'Noch nicht dokumentiert' }}{{ $marker['psychology'] ? ' · Psychologie ausgefüllt' : '' }}"
                wire:click="$dispatch('open-opening-log', { campaignId: {{ $marker['campaign_id'] }}, openedAt: '{{ $marker['opened_at'] }}' })">
            <i class="bi bi-{{ $marker['documented'] ? 'journal-check' : 'journal-plus' }}"></i>
            @if($marker['psychology'])
                <span class="psychology-dot position-absolute top-0 start-100 translate-middle p-1 bg-info border border-light rounded-circle"></span>
            @endif
        </button>
    @else
        <i class="bi bi-arrow-return-left {{ $marker['documented'] ? 'text-success' : 'text-warning' }}"
           title="Gehört zur Eröffnung mit dem Knopf darüber"></i>
    @endif
@endif
