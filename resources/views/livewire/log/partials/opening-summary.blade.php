{{--
  Zahlen einer Eröffnung aus dem Ledger (nur Anzeige).
  Variablen: $opening (CampaignOpenings), $details (GreeksLookup::detailsFor)
--}}
@php($fmt = fn($v) => number_format((float) $v, 2, ',', '.'))
<div class="d-flex flex-wrap gap-3 align-items-center small mb-2">
    <strong>{{ \Carbon\Carbon::parse($opening['opened_at'])->format('d.m.Y') }}</strong>
    <span class="badge {{ $opening['state'] === 'closed' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $opening['outcome'] }}</span>
    @if($opening['closed_at'])
        <span class="text-muted">bis {{ \Carbon\Carbon::parse($opening['closed_at'])->format('d.m.Y') }}
            ({{ \Carbon\Carbon::parse($opening['opened_at'])->startOfDay()->diffInDays(\Carbon\Carbon::parse($opening['closed_at'])->startOfDay()) }} Tage)</span>
    @endif
    @if($opening['pnl'] !== null)
        <span>P&amp;L <strong class="{{ $opening['pnl'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $fmt($opening['pnl']) }} $</strong></span>
    @endif
</div>

@if($opening['type'] === 'option')
    <div class="table-responsive">
        <table class="table table-sm mb-2">
            <thead class="table-light">
            <tr>
                <th>Leg</th>
                <th class="text-end">Strike</th>
                <th class="text-end">Prämie</th>
                <th class="text-end">Delta</th>
                <th class="text-end">DTE</th>
            </tr>
            </thead>
            <tbody>
            @foreach($opening['legs'] as $i => $leg)
                <tr>
                    <td>{{ $leg['leg_type'] }}</td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $leg['strike'], 2, ',', ''), '0'), ',') }}</td>
                    <td class="text-end">{{ $fmt($leg['price']) }}</td>
                    <td class="text-end">{{ $details['deltas'][$i] ?? '—' }}</td>
                    <td class="text-end">{{ $leg['dte'] ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap gap-3 small">
        <span>Prämie Eröffnung: <strong class="{{ $opening['premium'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $fmt(abs($opening['premium'])) }} $ {{ $opening['premium'] >= 0 ? 'Kredit' : 'Debit' }}</strong></span>
        <span class="text-muted">{{ $opening['contracts'] }} {{ $opening['contracts'] === 1 ? 'Kontrakt' : 'Kontrakte' }}</span>
        @if($opening['closing_cost'] !== null)
            <span>Prämie Close: <strong>{{ $fmt(abs($opening['closing_cost'])) }} $</strong></span>
        @endif
        <span>Gebühren: {{ $fmt($opening['fees_open']) }} / {{ $fmt($opening['fees_close']) }} $</span>
        @if($opening['max_risk'] !== null)
            <span>Max. Risiko: {{ $fmt($opening['max_risk']) }} $</span>
        @endif
    </div>
@else
    <div class="small">
        {{ $opening['is_short'] ? 'Short' : 'Kauf' }} {{ rtrim(rtrim(number_format($opening['shares'], 4, ',', ''), '0'), ',') }} Aktien zu {{ $fmt($opening['stock_price']) }} $
        <span class="text-muted ms-2">Gebühren: {{ $fmt($opening['fees_open']) }} $</span>
    </div>
@endif
