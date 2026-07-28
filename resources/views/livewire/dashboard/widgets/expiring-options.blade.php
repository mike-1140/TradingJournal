<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-alarm"></i> Optionen verfallen diese Woche</h5>
        @if(count($expiringOptions) > 0)
            <span class="badge bg-warning text-dark">{{ count($expiringOptions) }}</span>
        @endif
    </div>
    <div class="card-body">
        @if(count($expiringOptions) > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Strike/Typ</th>
                        <th class="text-end">Qty</th>
                        <th>Verfall</th>
                        <th class="text-end">DTE</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($expiringOptions as $trade)
                        @php $dte = \Carbon\Carbon::today()->diffInDays($trade->expiry); @endphp
                        <tr>
                            <td><strong>{{ $trade->symbol }}</strong></td>
                            <td>{{ $trade->strike }} {{ $trade->putCall }}</td>
                            <td class="text-end">{{ number_format($trade->quantity, 0) }}</td>
                            <td>{{ $trade->expiry->format('d.m.Y') }}</td>
                            <td class="text-end">
                                <span class="badge bg-{{ $dte <= 2 ? 'danger' : ($dte <= 5 ? 'warning' : 'secondary') }}">{{ $dte }} {{ $dte === 1 ? 'Tag' : 'Tage' }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-muted small">Keine Optionen verfallen in den nächsten 7 Tagen.</div>
        @endif
    </div>
</div>
