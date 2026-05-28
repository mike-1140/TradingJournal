<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div class="btn-group btn-group-sm">
            <button wire:click="setViewType('day')" class="btn {{ $viewType == 'day' ? 'btn-primary' : 'btn-outline-primary' }}">
                21 Tage
            </button>
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
        @if($viewType !== 'day')
        <div class="d-flex gap-2">
            <button wire:click="changeOffset(-1)" class="btn btn-sm btn-light border">
                <i class="bi bi-chevron-double-left"></i>
                1 {{ $this->getViewTypeLabel() }} zurück
            </button>
            <button wire:click="resetOffset" class="btn btn-sm btn-outline-secondary">Aktuell</button>
            <button wire:click="changeOffset(1)" class="btn btn-sm btn-light border">Vor <i class="bi bi-chevron-double-right"></i></button>
        </div>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr style="font-size: 0.75rem;" class="text-uppercase text-muted">
                    @if($viewType === 'day')
                        <th style="width: 20%;">Datum</th>
                        <th class="text-end">PnL</th>
                        <th class="text-end">Diff</th>
                        <th class="text-center">Buchungen</th>
                        <th class="text-center">Win / Loss</th>
                        <th class="text-end">Ø Trade</th>
                        <th class="text-end">Kommission</th>
                        <th class="text-end">Kumuliert (Monat)</th>
                    @else
                        <th style="width: 20%;">Zeitraum</th>
                        <th class="text-end">PnL</th>
                        <th class="text-end">Profit Factor</th>
                        <th class="text-end">Proceeds</th>
                        <th class="text-end">Commissions</th>
                        <th class="text-end">Net Cash</th>
                        <th class="text-center">Buchungen</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @forelse($periods as $p)
                @if($viewType === 'day')
                    @if($p['isWeekend'])
                        <tr style="--bs-table-bg: #e7dbad; font-size: 0.72rem; color: #5a8a5e; line-height: 1.2;">
                            <td colspan="8" class="py-1 px-3">
                                <i class="bi bi-moon-stars-fill me-1" style="opacity: 0.6;"></i>
                                {{ $p['label'] }}
                                <span class="ms-2" style="opacity: 0.65;">Wochenende</span>
                            </td>
                        </tr>
                    @elseif($p['hasNoTrades'])
                        <tr style="--bs-table-bg: #dddddd; font-size: 0.72rem; color: #9e9e9e; line-height: 1.2;">
                            <td colspan="8" class="py-1 px-3">
                                {{ $p['label'] }}
                                <span class="ms-2">— kein Handel</span>
                            </td>
                        </tr>
                    @else
                    <tr class="{{ $p['isCurrent'] ? 'table-primary-subtle fw-bold' : '' }}">
                        <td>
                            <span class="{{ $p['isCurrent'] ? 'text-primary' : '' }}">
                                {{ $p['label'] }}
                                @if($p['isCurrent']) <span class="badge bg-primary ms-1">Heute</span> @endif
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <x-currency-value :value="$p['metrics']->pnl" :colored="true" />
                            <br><small class="text-muted" style="font-size: 0.7rem;">netto: <x-currency-value :value="$p['metrics']->net_pnl ?? 0" :colored="true" /></small>
                        </td>
                        <td class="text-end">
                            @if($p['pnl_diff'] !== null)
                                <span style="font-size: 0.72rem;" class="{{ $p['pnl_diff'] > 0 ? 'text-success' : ($p['pnl_diff'] < 0 ? 'text-danger' : 'text-muted') }}">
                                    ({{ $p['pnl_diff'] > 0 ? '+' : '' }}{{ number_format($p['pnl_diff'], 2, ',', '.') }})
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">{{ $p['metrics']->trade_count }}</span>
                        </td>
                        <td class="text-center">
                            @php
                                $w = (int)($p['metrics']->win_count ?? 0);
                                $l = (int)($p['metrics']->loss_count ?? 0);
                            @endphp
                            @if($w > 0 || $l > 0)
                                <span class="text-success fw-bold">{{ $w }}W</span>
                                <span class="text-muted mx-1">/</span>
                                <span class="text-danger fw-bold">{{ $l }}L</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$p['avg_trade']" :colored="true" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$p['metrics']->commission" class="text-muted fw-normal" />
                        </td>
                        <td class="text-end text-nowrap">
                            <x-currency-value :value="$p['cumulative']" :colored="true" />
                        </td>
                    </tr>
                    @endif
                @else
                    <tr class="{{ $p['isCurrent'] ? 'table-primary-subtle fw-bold' : '' }}">
                        <td>
                            <span class="{{ $p['isCurrent'] ? 'text-primary' : '' }}">
                                {{ $p['label'] }}
                                @if($p['isCurrent']) <span class="badge bg-primary ms-1">Aktuell</span> @endif
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <x-currency-value :value="$p['metrics']->pnl" :colored="true" />
                            <br><small class="text-muted" style="font-size: 0.7rem;">netto: <x-currency-value :value="$p['metrics']->net_pnl ?? 0" :colored="true" /></small>
                            @if($p['pnl_diff'] != 0)
                                <small class="{{ $p['pnl_diff'] > 0 ? 'text-success' : 'text-danger' }} ms-1">
                                    ({{ $p['pnl_diff'] > 0 ? '+' : '' }}{{ number_format($p['pnl_diff'], 2, ',', '.') }})
                                </small>
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
                @endif
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                        Keine Aktivität in diesem Zeitraum
                    </td>
                </tr>
            @endforelse
            </tbody>
            <tfoot class="table-dark fw-bold shadow-sm" style="font-family: 'Courier New', Courier, monospace;">
            <tr>
                @if($viewType === 'day')
                    <td>GESAMT ({{ $totals['active_day_count'] }} aktive Tage)</td>
                    <td class="text-end">
                        <x-currency-value :value="$totals['pnl']" />
                        <br><small style="font-size: 0.7rem; opacity: 0.8;">netto: <x-currency-value :value="$totals['net_pnl']" /></small>
                    </td>
                    <td></td>
                    <td class="text-center">{{ $totals['trade_count'] }}</td>
                    <td class="text-center">
                        <span >{{ $totals['win_count'] }}W</span>
                        <span class="mx-1">/</span>
                        <span >{{ $totals['loss_count'] }}L</span>
                    </td>
                    <td class="text-end">
                        <x-currency-value :value="$totals['trade_count'] > 0 ? $totals['pnl'] / $totals['trade_count'] : 0" />
                    </td>
                    <td class="text-end">
                        <x-currency-value :value="$totals['commission']" />
                    </td>
                    <td class="text-end text-muted small">—</td>
                @else
                    <td>GESAMT ({{ $limit }} {{ $this->getViewTypeLabel() }}e)</td>
                    <td class="text-end">
                        <x-currency-value :value="$totals['pnl']" />
                        <br><small style="font-size: 0.7rem; opacity: 0.8;">netto: <x-currency-value :value="$totals['net_pnl']" /></small>
                    </td>
                    <td class="text-end">
                        <span class="text-muted small">---</span>
                    </td>
                    <td class="text-end">
                        <x-currency-value :value="$totals['proceeds']" />
                    </td>
                    <td class="text-end">
                        <x-currency-value :value="$totals['commission']" />
                    </td>
                    <td class="text-end">
                        <x-currency-value :value="$totals['turnover']" />
                    </td>
                    <td class="text-center">
                        {{ $totals['trade_count'] }}
                    </td>
                @endif
            </tr>
            </tfoot>
        </table>
    </div>
    @if($viewType === 'day')
    <div class="card-footer bg-white text-center py-2">
        <button wire:click="loadMore" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-chevron-double-down me-1"></i>
            7 weitere Tage laden
        </button>
    </div>
    @endif
</div>
