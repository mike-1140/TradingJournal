<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Aktuelle offene Positionen</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
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
