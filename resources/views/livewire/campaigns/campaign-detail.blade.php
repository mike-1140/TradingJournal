<div class="card border-0 shadow-sm mt-4">
    <div class="d-flex align-items-center justify-content-between bg-light p-3 rounded-top border-start border-primary border-4 mb-0">
        <div>
            <h2 class="mb-0 fw-bold">{{ $campaign->symbol }}</h2>
            <span class="text-muted">{{ $campaign->name }}</span>
        </div>
        <div class="text-end">
        <span class="badge bg-{{ $campaign->status === 'ACTIVE' ? 'success' : 'secondary' }} mb-1">
            {{ $campaign->status }}
        </span>
            <div class="small text-muted">Eröffnet am {{ $campaign->start_date->format('d.m.Y') }}</div>
        </div>
    </div>

    <div class="card border-0 rounded-0 rounded-bottom shadow-sm">
        <div class="card-body bg-light border-start border-primary border-4">
            <div class="row g-3 text-center row-cols-2 row-cols-md-6">
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Aktien-Bestand</small>
                    <span class="fs-5 fw-bold">{{ number_format($metrics->shares_balance, 0, ',', '.') }}</span>
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Optionen-Bestand</small>
                    <span class="fs-5 fw-bold">{{ number_format($metrics->options_balance, 0, ',', '.') }}</span>
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Net Cash</small>
                    <x-currency-value :value="$metrics->total_netcash" :currency="'USD'" class="fs-5" />
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Proceeds</small>
                    <x-currency-value :value="$metrics->total_proceeds" :currency="'USD'" class="fs-5" />
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Commission</small>
                    <x-currency-value :value="$metrics->total_commission" :currency="'USD'" class="fs-5" />
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Gesamt PnL</small>
                    <x-currency-value :value="$metrics->total_pnl" :currency="'$'" class="fs-5" :colored="true" />
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
        <table class=" table table-hover align-middle mb-0">
            <thead class="table-light sticky-top" style="z-index: 1020;top: 0;
           left: 0;
           right: 0;
           padding: 4px 3px;
           background-color: #d3d3d3;
           border-width: 5px;">
            <tr style="font-size: 0.75rem;" class="text-uppercase text-muted">
                    <th>Datum & Zeit</th>
                    <th>Aktion</th>
                    <th class="text-center">Asset</th>
                    <th class="text-end">Menge (Delta)</th>
                    <th class="text-end">Net Cash</th>
                    <th class="text-end">Proceeds</th>
                    <th class="text-end">Commission</th>
                    <th class="text-end">PnL</th>
                    <th class="text-center">Source</th>
                    <th class="text-end">ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ledgerEntries as $entry)
                    <tr class="{{ $entry->inventory_delta > 0 ? 'bg-buy-light' : ($entry->inventory_delta < 0 ? 'bg-sell-light' : '') }}">
                        <td class="text-nowrap small text-muted">
                            {{ \Carbon\Carbon::parse($entry->dateTime)->format('d.m.Y H:i') }}
                        </td>
                        <td>
                            {{-- Badge für die Action --}}
                            <span class="badge {{ $this->getActionBadgeClass($entry->action) }} shadow-sm">
                                {{ $entry->action }}
                            </span>
                        </td>
                        <td class="text-center fw-bold">{{ $entry->assetCategory }}</td>

                        {{-- Menge: Blau/Grün für Zuwachs, Orange/Rot für Abgang --}}
                        <td class="text-end fw-bold {{ $entry->inventory_delta > 0 ? 'text-primary' : 'text-warning' }}">
                            {{ $entry->inventory_delta > 0 ? '+' : '' }}{{ number_format($entry->inventory_delta, 2, ',', '.') }}
                        </td>

                        <td class="text-end">
                            <x-currency-value :value="$entry->netcash" :currency="$entry->currency" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$entry->proceeds" :currency="$entry->currency" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$entry->commission" :currency="$entry->currency" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$entry->realized_pnl" :currency="$entry->currency" />
                        </td>
                        <td class="text-center">
                            <span class="badge border text-secondary small" title="Source: {{ $entry->source_table }}">
                                {{ substr($entry->source_table, 7) }}
                            </span>
                        </td>
                        <td class="text-end text-muted small">#{{ $entry->source_id }}</td>
                    </tr>
                @endforeach
            </tbody>
            @if($ledgerEntries && $ledgerEntries->count() > 0)
                <tfoot class="table-light fw-bold border-top-2">
                    @if($ledgerEntries->lastPage() > 1)
                        <tr>
                            <td colspan="4">Summe (diese Seite):</td>
                            <td class="text-end">
                                <x-currency-value :value="$ledgerEntries->sum('netcash')" :currency="$entry->currency" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$ledgerEntries->sum('proceeds')" :currency="$entry->currency" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$ledgerEntries->sum('commission')" :currency="$entry->currency" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$ledgerEntries->sum('realized_pnl')" :currency="$entry->currency" />
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    @endif
                    <tr class="table-dark">
                        <td colspan="4" class="text-uppercase" style="font-size: 0.8rem;">Gesamtergebnis Kampagne:</td>
                        <td class="text-end">
                            <x-currency-value :value="$metrics['total_netcash']" :currency="$entry->currency" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$metrics['total_proceeds']" :currency="$entry->currency" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$metrics['total_commission']" :currency="$entry->currency" />
                        </td>
                        <td class="text-end">
                            <x-currency-value :value="$metrics['total_pnl']" :currency="$entry->currency" />
                        </td>

                        <td colspan="2" class="text-end">
                            <small class="fw-normal opacity-75">{{ $ledgerEntries->total() }} Transaktionen</small>
                        </td>
                    </tr>
                </tfoot>
            @endif

        </table>
    </div>

    <div class="card-footer bg-white">
        {{ $ledgerEntries->links('components.pagination') }}
    </div>

    <style>
        /* Wir nutzen den Selektor 'table tr.klasse td', um Priorität zu gewinnen */
        .table tbody tr.bg-buy-light td {
            background-color: rgba(13, 110, 253, 0.06) !important;
            box-shadow: inset 0 0 0 9999px rgba(13, 110, 253, 0.06); /* Fix für manche Bootstrap-Filter */
        }

        .table tbody tr.bg-sell-light td {
            background-color: rgba(255, 193, 7, 0.06) !important;
            box-shadow: inset 0 0 0 9999px rgba(255, 193, 7, 0.06);
        }

        /* Damit der Hover-Effekt trotzdem noch sichtbar ist */
        .table-hover tbody tr.bg-buy-light:hover td,
        .table-hover tbody tr.bg-sell-light:hover td {
            background-color: rgba(0, 0, 0, 0.07) !important;
            box-shadow: inset 0 0 0 9999px rgba(0, 0, 0, 0.07);
        }

        /* Bonus: Ein kleiner farbiger vertikaler Strich am Anfang der Zeile */
        tr.bg-buy-light td:first-child { border-left: 4px solid #0d6efd; }
        tr.bg-sell-light td:first-child { border-left: 4px solid #ffc107; }
    </style>
</div>
