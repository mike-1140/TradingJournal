<div>
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="mb-0 fw-bold">
                <i class="bi bi-activity me-2"></i>Aktivität
            </h2>
            <small class="text-muted">
                <i class="bi bi-calendar3"></i>
                Letzte {{ $days }} Tage &nbsp;({{ $periodStart->format('d.m.Y') }} – {{ $periodEnd->format('d.m.Y') }})
                &nbsp;·&nbsp; alle Ticker
            </small>
        </div>
        @if($days > 7)
            <button wire:click="resetDays" class="btn btn-sm btn-outline-secondary">Zurücksetzen auf 7 Tage</button>
        @endif
    </div>

    {{-- Metriken --}}
    <div class="card mb-4 border-start border-primary border-4 border-top-0 border-end-0 border-bottom-0">
        <div class="card-body bg-light">
            <div class="row g-3 text-center row-cols-2 row-cols-md-6">
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Buchungen</small>
                    <span class="fs-5 fw-bold">{{ number_format($metrics->total_entries ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Aktive Ticker</small>
                    <span class="fs-5 fw-bold">{{ number_format($metrics->symbol_count ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Cashflow</small>
                    <x-currency-value :value="$metrics->total_cashflow ?? 0" :currency="'USD'" class="fs-5" />
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Commission</small>
                    <x-currency-value :value="$metrics->total_commission ?? 0" :currency="'USD'" class="fs-5" />
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Brutto PnL</small>
                    <x-currency-value :value="$metrics->total_pnl ?? 0" :currency="'USD'" class="fs-5" :colored="true" />
                </div>
                <div class="col">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Netto PnL</small>
                    <x-currency-value :value="$metrics->total_net_pnl ?? 0" :currency="'USD'" class="fs-5 fw-bold" :colored="true" />
                </div>
            </div>
        </div>
    </div>

    {{-- Ledger-Tabelle --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">
                <i class="bi bi-clock-history me-2"></i>Transaktions-Historie
            </h5>
            @if($totalEntries > 0)
                <span class="badge bg-secondary">{{ $totalEntries }} Buchungen</span>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light sticky-top" style="z-index: 1;">
                    <tr class="text-uppercase text-muted" style="font-size: 0.7rem;">
                        <th style="width: 55px;">Zeit</th>
                        <th style="width: 90px;">Symbol</th>
                        <th style="width: 140px;">Ereignis</th>
                        <th>Beschreibung</th>
                        <th class="text-end" style="width: 80px;">Δ Menge</th>
                        <th class="text-end" style="width: 110px;">Cashflow</th>
                        <th class="text-end" style="width: 100px;">Commission</th>
                        <th class="text-end" style="width: 95px;">Brutto PnL</th>
                        <th class="text-end" style="width: 95px;">Netto PnL</th>
                    </tr>
                </thead>
                <tbody>

                    @php
                        $linkIdColors  = [];
                        $colorPalette  = ['#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#17a2b8'];
                        $colorIdx      = 0;
                        foreach ($ledgerEntries->groupBy('linkID') as $lid => $group) {
                            if ($lid && $group->count() > 1) {
                                $linkIdColors[$lid] = $colorPalette[$colorIdx % count($colorPalette)];
                                $colorIdx++;
                            }
                        }

                        $groupedEntries = $ledgerEntries->groupBy(
                            fn($e) => \Carbon\Carbon::parse($e->dateTime)->format('Y-m-d')
                        );
                    @endphp

                    @if($groupedEntries->isEmpty())
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                Keine Buchungen für diesen Zeitraum.
                            </td>
                        </tr>
                    @else
                        @foreach($groupedEntries as $dateKey => $dayEntries)
                            @php
                                $firstEntryDt  = \Carbon\Carbon::parse($dayEntries->first()->dateTime);
                                $dayCashflow   = 0.0;
                                $dayCommission = 0.0;
                                $dayPnl        = 0.0;
                                $dayNetPnl     = 0.0;
                            @endphp

                            {{-- Tages-Trennzeile --}}
                            <tr wire:key="sep-{{ $dateKey }}" class="date-separator">
                                <td colspan="9">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    {{ $firstEntryDt->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
                                </td>
                            </tr>

                            @foreach($dayEntries as $entry)
                                @php
                                    $linkColor  = ($entry->linkID && isset($linkIdColors[$entry->linkID]))
                                                    ? $linkIdColors[$entry->linkID]
                                                    : null;
                                    $narrative  = $this->buildNarrative($entry);
                                    $deltaPos   = (float) $entry->inventory_delta > 0;
                                    $deltaNeg   = (float) $entry->inventory_delta < 0;

                                    // Effektiver Cashflow: bei CASH_SETTLEMENT proceeds nehmen, sonst netcash
                                    $effectiveCashflow = $entry->action === 'CASH_SETTLEMENT'
                                        ? $entry->proceeds
                                        : $entry->netcash;

                                    $dayCashflow   += (float) $effectiveCashflow;
                                    $dayCommission += (float) $entry->commission;
                                    $dayPnl        += (float) $entry->realized_pnl;
                                    $dayNetPnl     += (float) $entry->realized_pnl + (float) $entry->commission;
                                @endphp

                                {{-- Buchungszeile --}}
                                <tr wire:key="entry-{{ $entry->id }}"
                                    class="ledger-row {{ $deltaPos ? 'row-buy' : ($deltaNeg ? 'row-sell' : '') }}"
                                    style="border-left: 3px solid {{ $linkColor ?? 'transparent' }};">
                                    <td class="text-muted text-nowrap" style="font-size: 0.75rem;">
                                        {{ \Carbon\Carbon::parse($entry->dateTime)->format('H:i') }}
                                        @if($linkColor)
                                            <span class="link-dot" style="background: {{ $linkColor }};"></span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($entry->campaign_id)
                                            <a href="{{ route('campaigns.campaign.detail', $entry->campaign_id) }}" class="fw-bold text-decoration-none">
                                                {{ $entry->symbol }}
                                            </a>
                                        @else
                                            <span class="fw-bold">{{ $entry->symbol }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $this->getActionBadgeClass($entry->action) }}"
                                              style="font-size: 0.7rem;">
                                            {{ $entry->action }}
                                        </span>
                                    </td>
                                    <td class="narrative-text">{{ $narrative }}</td>
                                    <td class="text-end fw-bold {{ $deltaPos ? 'text-primary' : ($deltaNeg ? 'text-warning' : 'text-muted') }}">
                                        {{ $deltaPos ? '+' : '' }}{{ number_format((float) $entry->inventory_delta, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <x-currency-value :value="$effectiveCashflow" :currency="$entry->currency" />
                                        @if($entry->action === 'CASH_SETTLEMENT')
                                            <br><small class="text-muted" style="font-size: 0.7rem;">Settlement</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <x-currency-value :value="$entry->commission" :currency="$entry->currency" />
                                    </td>
                                    <td class="text-end fw-bold">
                                        @if((float) $entry->realized_pnl != 0)
                                            <x-currency-value :value="$entry->realized_pnl" :currency="$entry->currency" :colored="true" />
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">
                                        @php $netPnlEntry = (float) $entry->realized_pnl + (float) $entry->commission; @endphp
                                        @if($netPnlEntry != 0)
                                            <x-currency-value :value="$netPnlEntry" :currency="$entry->currency" :colored="true" />
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>

                            @endforeach

                            {{-- Tagessumme --}}
                            <tr wire:key="subtotal-{{ $dateKey }}" class="day-subtotal-row">
                                <td colspan="4" class="text-end text-muted pe-2" style="font-size: 0.75rem; font-style: italic;">
                                    Tagessumme
                                </td>
                                <td></td>
                                <td class="text-end">
                                    <x-currency-value :value="$dayCashflow" :currency="'USD'" :colored="true" />
                                </td>
                                <td class="text-end">
                                    <x-currency-value :value="$dayCommission" :currency="'USD'" />
                                </td>
                                <td class="text-end">
                                    @if($dayPnl != 0)
                                        <x-currency-value :value="$dayPnl" :currency="'USD'" :colored="true" />
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">
                                    @if($dayNetPnl != 0)
                                        <x-currency-value :value="$dayNetPnl" :currency="'USD'" :colored="true" />
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>

                        @endforeach
                    @endif

                </tbody>

                @if($ledgerEntries->isNotEmpty())
                    <tfoot>
                        <tr class="table-dark" style="font-size: 0.8rem;">
                            <td colspan="4" class="text-uppercase">Gesamtergebnis Zeitraum:</td>
                            <td></td>
                            <td class="text-end">
                                <x-currency-value :value="$metrics->total_cashflow ?? 0" :currency="'USD'" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$metrics->total_commission ?? 0" :currency="'USD'" />
                            </td>
                            <td class="text-end">
                                <x-currency-value :value="$metrics->total_pnl ?? 0" :currency="'USD'" :colored="true" />
                            </td>
                            <td class="text-end fw-bold">
                                <x-currency-value :value="$metrics->total_net_pnl ?? 0" :currency="'USD'" :colored="true" />
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="card-footer bg-white text-center py-2">
            <button wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore"
                    class="btn btn-sm btn-outline-secondary">
                <span wire:loading.remove wire:target="loadMore">
                    <i class="bi bi-chevron-double-down me-1"></i>
                    7 weitere Tage laden
                </span>
                <span wire:loading wire:target="loadMore">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Lädt...
                </span>
            </button>
            @if($emptyWeekLabel)
                <div class="text-muted small mt-2">
                    <i class="bi bi-info-circle"></i>
                    Keine Aktivität in der Woche {{ $emptyWeekLabel }} — jetzt {{ $days }} Tage geladen
                </div>
            @endif
        </div>
    </div>

    <style>
        /* Tages-Trennzeile */
        tr.date-separator td {
            background-color: #d5dde6;
            color: #495057;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 6px 12px;
            border-top: 2px solid #dee2e6;
            letter-spacing: 0.02em;
        }

        /* Zeileneinfärbung kaufen/verkaufen */
        .table tbody tr.row-buy td  { background-color: rgba(13, 110, 253, 0.04) !important; }
        .table tbody tr.row-sell td { background-color: rgba(255, 193, 7, 0.05) !important; }
        .table-hover tbody tr.row-buy:hover td,
        .table-hover tbody tr.row-sell:hover td {
            background-color: rgba(0, 0, 0, 0.06) !important;
        }

        /* Narrativ-Text */
        .narrative-text {
            font-size: 0.85rem;
            color: #212529;
        }

        /* linkID Punkt-Indikator */
        .link-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-left: 3px;
            vertical-align: middle;
        }

        /* Tagessumme-Zeile */
        tr.day-subtotal-row td {
            background-color: #f0f4f8;
            font-size: 0.78rem;
            border-top: 1px dashed #ced4da;
            border-bottom: 2px solid #dee2e6;
            padding-top: 4px;
            padding-bottom: 4px;
        }
    </style>
</div>
