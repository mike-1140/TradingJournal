<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div class="btn-group btn-group-sm">
            <button wire:click="setViewType('week')" class="btn {{ $viewType == 'week' ? 'btn-primary' : 'btn-outline-primary' }}">
                13 Wochen
            </button>
            <button wire:click="setViewType('month')" class="btn {{ $viewType == 'month' ? 'btn-primary' : 'btn-outline-primary' }}">
                6 Monate
            </button>
            <button wire:click="setViewType('year')" class="btn {{ $viewType == 'year' ? 'btn-primary' : 'btn-outline-primary' }}">
                3 Jahre
            </button>
        </div>
        <div class="d-flex gap-2">
            <button wire:click="changeOffset(-1)" class="btn btn-sm btn-light border">
                <i class="bi bi-chevron-double-left"></i>
                1 {{ $this->getViewTypeLabel() }} zurück
            </button>
            <button wire:click="resetOffset" class="btn btn-sm btn-outline-secondary">Aktuell</button>
            <button wire:click="changeOffset(1)" class="btn btn-sm btn-light border">Vor <i class="bi bi-chevron-double-right"></i></button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr style="font-size: 0.75rem;" class="text-uppercase text-muted">
                    <th style="width: 20%;">Zeitraum</th>
                    <th class="text-end">PnL</th>
                    <th class="text-end">Profit Factor</th>
                    <th class="text-end">Proceeds</th>
                    <th class="text-end">Commissions</th>
                    <th class="text-end">Net Cash</th>
                    <th class="text-center">Trades</th>
                </tr>
            </thead>
            <tbody>
            @foreach($periods as $p)
                <tr class="{{ $p['isCurrent'] ? 'table-primary-subtle fw-bold' : '' }}">
                    <td>
                        <span class="{{ $p['isCurrent'] ? 'text-primary' : '' }}">
                            {{ $p['label'] }}
                            @if($p['isCurrent']) <span class="badge bg-primary ms-1">Aktuell</span> @endif
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        <x-currency-value :value="$p['metrics']->pnl" :colored="true" />

                        @if($p['pnl_trend'] > 0)
                            <i class="bi bi-arrow-up-right text-success ms-1"  style="width: 0.9rem;" title="Besser als Vorperiode"></i>
                        @elseif($p['pnl_trend'] < 0)
                            <i class="bi bi-arrow-down-right text-danger ms-1"  style="width: 0.9rem;" title="Schlechter als Vorperiode"></i>
                        @else
                            &nbsp;
                        @endif
                    </td>
                    <td class="text-end">
                        <span class="badge {{ $p['pf'] >= 1.5 ? 'bg-success' : ($p['pf'] >= 1.0 ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ number_format($p['pf'], 2) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <x-currency-value :value="$p['metrics']->proceeds" class="text-muted fw-normal" />
                    </td>
                    <td class="text-end">
                        <x-currency-value :value="$p['metrics']->commission" class="text-muted fw-normal" />
                    </td>
                    <td class="text-end fw-normal">
                        <x-currency-value :value="$p['metrics']->turnover" />
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">{{ $p['metrics']->trade_count }}</span>
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="table-dark fw-bold shadow-sm" style="font-family: 'Courier New', Courier, monospace;">
            <tr>
                <td>GESAMT ({{ $limit }} {{ $this->getViewTypeLabel() }}e)</td>
                <td class="text-end">
                    <x-currency-value :value="$totals['pnl']" :colored="true" />
                    <span class="ms-1 d-inline-block" style="width: 0.7rem;"></span>
                </td>
                <td class="text-end">
                    <span class="text-muted small">---</span>
                </td>
                <td class="text-end">
                    <x-currency-value :value="$totals['proceeds']" />
                </td>
                <td class="text-end text-warning">
                    <x-currency-value :value="$totals['commission']" />
                </td>
                <td class="text-end">
                    <x-currency-value :value="$totals['turnover']" />
                </td>
                <td class="text-center">
                    {{ $totals['trade_count'] }}
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>
