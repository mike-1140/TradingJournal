<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">
            <i class="bi bi-list-ul"></i> Aktuelle offene Positionen
            <span class="badge bg-secondary ms-1" title="Anzahl offener Positionen">{{ $currentOpenTrades->count() }}</span>
        </h5>
        <div class="small">
            <a href="{{ route('trades.open.stocks') }}" class="text-decoration-none me-3"><i class="bi bi-box-arrow-up-right"></i> Offene Aktien</a>
            <a href="{{ route('trades.open.options') }}" class="text-decoration-none"><i class="bi bi-box-arrow-up-right"></i> Offene Optionen</a>
        </div>
    </div>
    <div class="card-body">
        {{-- Feste Höhe (~10 Zeilen), danach scrollen; Kopfzeile bleibt stehen --}}
        <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
            <table class="table table-hover mb-0">
                <thead class="table-light sticky-top" style="z-index: 1;">
                <tr>
                    <th>Symbol</th>
                    <th>Typ</th>
                    <th>Einstieg</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Preis</th>
                    <th class="text-end">NetCash</th>
                    <th>Strategie</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse($currentOpenTrades as $trade)
                    <tr>
                        <td>
                            <strong>{{ $trade->symbol }}</strong>
                            @if($trade->putCall)
                                <br><small class="text-muted">
                                    {{ $trade->strike }} {{ $trade->putCall }}
                                    @if($trade->expiry)
                                        | {{ $trade->expiry->format('d.m.Y') }}
                                    @endif
                                </small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $trade->assetCategory }}</span>
                        </td>
                        <td>{{ $trade->dateTime?->format('d.m.Y') }}</td>
                        <td class="text-end">{{ number_format($trade->quantity, 0) }}</td>
                        <td class="text-end">{{ number_format($trade->tradePrice, 2) }}</td>
                        <td class="text-end">{{ number_format($trade->netCash, 2) }}</td>
                        <td><small>{{ $trade->display_strategy ?: '-' }}</small></td>
                        <td>
                            <span class="badge bg-primary">{{ $trade->status ?: 'OPEN' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Keine offenen Trades</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
