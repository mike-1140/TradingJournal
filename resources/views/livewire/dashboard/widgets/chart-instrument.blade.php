<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-pie-chart"></i> P&L nach Option/Aktie</h6>
        <small class="text-muted">{{ $zeitraumLabel }}</small>
    </div>
    <div class="card-body p-2">
        @if(count($chartInstrumentValues) > 0)
            <div id="chart-instrument" wire:ignore></div>
        @else
            <div class="d-flex align-items-center justify-content-center text-muted" style="height:260px">
                <div class="text-center">
                    <i class="bi bi-pie-chart fs-1"></i>
                    <p class="mt-2 small">Keine Trades im Zeitraum</p>
                </div>
            </div>
        @endif
    </div>
</div>
