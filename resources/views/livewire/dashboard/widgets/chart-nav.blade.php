<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-graph-up"></i> NAV-Verlauf</h6>
        <small class="text-muted">
            @if(count($chartNav) > 0)
                Seit {{ $chartNav[0]['x'] }}
            @else
                Account-Wert täglich
            @endif
        </small>
    </div>
    <div class="card-body p-2">
        <div id="chart-nav" wire:ignore></div>
    </div>
</div>
