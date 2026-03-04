<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">
            <i class="bi bi-list-columns-reverse me-2"></i>Transaktions-Historie
        </h5>
        @if($ledgerEntries)
            <span class="badge bg-info text-dark">{{ $ledgerEntries->total() }} Buchungen</span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
            <thead class="table-light">
            <tr>
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

                    <td class="text-end">{{ number_format($entry->netcash, 2, ',', '.') }} {{ $entry->currency }}</td>
                    <td class="text-end">{{ number_format($entry->proceeds, 2, ',', '.') }} {{ $entry->currency }}</td>
                    <td class="text-end">{{ number_format($entry->commission, 2, ',', '.') }} {{ $entry->currency }}</td>

                    <td class="text-end fw-bold {{ $entry->realized_pnl > 0 ? 'text-success' : ($entry->realized_pnl < 0 ? 'text-danger' : '') }}">
                        {{ $entry->realized_pnl != 0 ? number_format($entry->realized_pnl, 2, ',', '.') : '-' }}
                    </td>

                    <td class="text-center">
                            <span class="badge border text-secondary small" title="Source: {{ $entry->source_table }}">
                                {{ substr($entry->source_table, 7) }}
                            </span>
                    </td>
                    <td class="text-end text-muted small">#{{ $entry->source_id }}</td>
                </tr>
            @endforeach
            @if($ledgerEntries && $ledgerEntries->count() > 0)
                {{--
                <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="4">Summen dieser Seite</td>
                    <td class="text-end">{{ number_format($ledgerEntries->sum('netcash'), 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($ledgerEntries->sum('proceeds'), 2, ',', '.') }}</td>
                    <td class="text-end {{ $ledgerEntries->sum('realized_pnl') >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($ledgerEntries->sum('realized_pnl'), 2, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    {{-- Richtig (wenn du die Stats aus Lösung 1 übergibst): -- }}
                    <td colspan="9">
                        Gesamt-PnL dieser Kampagne: <strong>{{ number_format($ledgerStats['total_pnl'], 2, ',', '.') }} $</strong>
                        <small class="text-muted">(Über alle Seiten hinweg)</small>
                    </td>
                </tr>
                </tfoot>
                --}}
                <tfoot class="table-light fw-bold border-top-2">
                <tr>
                    <td colspan="4">Summe (diese Seite):</td>
                    <td class="text-end">
                        {{ number_format($ledgerEntries->sum('netcash'), 2, ',', '.') }} {{ $entry->currency }}
                    </td>
                    <td class="text-end">{{ number_format($ledgerEntries->sum('proceeds'), 2, ',', '.') }}{{ $entry->currency }}</td>
                    <td class="text-end">{{ number_format($ledgerEntries->sum('commission'), 2, ',', '.') }}{{ $entry->currency }}</td>
                    <td class="text-end {{ $ledgerEntries->sum('realized_pnl') >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($ledgerEntries->sum('realized_pnl'), 2, ',', '.') }} {{ $entry->currency }}
                    </td>
                    <td colspan="2"></td>
                </tr>
                <tr class="table-dark">
                    <td colspan="4" class="text-uppercase" style="font-size: 0.8rem;">Gesamtergebnis Kampagne:</td>
                    <td class="text-end">
                        {{ number_format($ledgerStats['total_netcash'], 2, ',', '.') }} {{ $entry->currency }}
                    </td>
                    <td class="text-end">
                        {{ number_format($ledgerStats['total_proceeds'], 2, ',', '.') }} {{ $entry->currency }}
                    </td>
                    <td class="text-end">
                        {{ number_format($ledgerStats['total_commission'], 2, ',', '.') }} {{ $entry->currency }}
                    </td>
                    <td class="text-end {{ $ledgerStats['total_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($ledgerStats['total_pnl'], 2, ',', '.') }} {{ $entry->currency }}
                    </td>

                    <td colspan="2" class="text-end">
                        <small class="fw-normal opacity-75">{{ $ledgerEntries->total() }} Transaktionen</small>
                    </td>
                </tr>
                </tfoot>
            @endif
            {{--
            @forelse($ledgerEntries as $entry)
                <tr class="{{ $entry->realized_pnl > 0 ? 'table-success-light' : ($entry->realized_pnl < 0 ? 'table-danger-light' : '') }}">
                    <td class="text-nowrap">
                        {{ \Carbon\Carbon::parse($entry->dateTime)->format('d.m.Y') }}<br>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($entry->dateTime)->format('H:i:s') }}</small>
                    </td>
                    <td>
                            <span class="badge {{ $this->getActionBadgeClass($entry->action) }} fw-medium">
                                {{ $entry->action }}
                            </span>
                    </td>
                    <td class="text-center">
                        <small class="fw-bold">{{ $entry->assetCategory }}</small>
                    </td>
                    <td class="text-end fw-mono">
                        {{ number_format($entry->inventory_delta, 2, ',', '.') }}
                    </td>
                    <td class="text-end fw-mono">
                        {{ number_format($entry->netcash, 2, ',', '.') }} <small class="text-muted">{{ $entry->currency }}</small>
                    </td>
                    <td class="text-end fw-bold {{ $entry->realized_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $entry->realized_pnl != 0 ? number_format($entry->realized_pnl, 2, ',', '.') : '-' }}
                    </td>
                    <td class="text-center">
                            <span class="badge border text-secondary small" title="Source: {{ $entry->source_table }}">
                                {{ substr($entry->source_table, 7) }}
                            </span>
                    </td>
                    <td class="text-end text-muted small">#{{ $entry->source_id }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Keine Ledger-Einträge für diese Kampagne gefunden.
                    </td>
                </tr>
            @endforelse
            </tbody>
            @if($ledgerEntries && $ledgerEntries->count() > 0)
                <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="4">Summen dieser Seite</td>
                    <td class="text-end">{{ number_format($ledgerEntries->sum('netcash'), 2, ',', '.') }}</td>
                    <td class="text-end {{ $ledgerEntries->sum('realized_pnl') >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($ledgerEntries->sum('realized_pnl'), 2, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
                </tfoot>
            @endif --}}
        </table>
    </div>
    @if($ledgerEntries && $ledgerEntries->hasPages())
        {{--
        <div class="card-footer bg-white pt-3">
            {{ $ledgerEntries->links(data: ['scrollTo' => false]) }}
        </div> --}}
        <div class="mt-4">
            {{ $ledgerEntries->links('components.pagination') }}
        </div>
    @endif
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
