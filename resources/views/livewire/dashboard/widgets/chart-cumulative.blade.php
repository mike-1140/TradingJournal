<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Kumulativer P&L {{ now()->year }}</h6>
        <small class="text-muted">monatlich</small>
    </div>
    <div class="card-body p-2">
        <div id="chart-cumulative" wire:ignore></div>
    </div>
</div>
