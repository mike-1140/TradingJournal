<div>
    {{-- Header --}}
    <x-page-header
        title="Trade Details - {{ $trade->symbol }}"
        subtitle="{{ $trade->assetCategory }} • Closed {{ $trade->dateTimeClose->format('d.m.Y H:i:s') }}"
        icon="check-circle"
        back-route="trades.closed.stocks">
    </x-page-header>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <x-stat-card
                title="Profit/Loss (USD)"
                :value="number_format($trade->profit, 2) . ' $'"
                icon="wallet2"
                :color="$trade->profit >= 0 ? 'success' : 'danger'"
                :trend="$trade->profit >= 0 ? 'up' : 'down'"
                :trend-value="number_format($trade->ROI, 2) . '%'" />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Profit/Loss (EUR)"
                :value="number_format($trade->profit_EUR, 2) . ' €'"
                icon="currency-euro"
                :color="$trade->profit_EUR >= 0 ? 'success' : 'danger'" />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Days Held"
                :value="$trade->daysHeld"
                icon="calendar3"
                color="primary" />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Kommission"
                :value="number_format($trade->ibCommission, 2) . ' $'"
                icon="receipt"
                color="secondary" />
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row g-4">
        <div class="col-lg-8">

            {{-- 1. Identifikation --}}
            <x-trade-card title="Identifikation" icon="fingerprint" class="mb-4">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th style="width:40%">ID</th><td><code>{{ $trade->id }}</code></td></tr>
                        <tr><th>Symbol</th><td><strong>{{ $trade->symbol }}</strong></td></tr>
                        <tr><th>Underlying Symbol</th><td>{{ $trade->underlyingSymbol ?? '—' }}</td></tr>
                        <tr><th>Description</th><td>{{ $trade->description ?? '—' }}</td></tr>
                        <tr><th>Asset Category</th><td><span class="badge bg-primary">{{ $trade->assetCategory }}</span></td></tr>
                        <tr><th>Sub Category</th><td>{{ $trade->subCategory ?? '—' }}</td></tr>
                        <tr><th>Conid</th><td><code>{{ $trade->conid ?? '—' }}</code></td></tr>
                        <tr><th>Underlying Conid</th><td><code>{{ $trade->underlyingConid ?? '—' }}</code></td></tr>
                        <tr><th>Sym ID</th><td><code>{{ $trade->symID ?? '—' }}</code></td></tr>
                        <tr><th>Environment</th><td><span class="badge bg-secondary">{{ $trade->environment }}</span></td></tr>
                    </tbody>
                </table>
            </x-trade-card>

            {{-- 2. Trade Information --}}
            <x-trade-card title="Trade Information" icon="info-circle" class="mb-4">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th style="width:40%">Buy/Sell (Open)</th><td>
                            <span class="badge bg-{{ $trade->buySellOpen === 'BUY' ? 'success' : 'danger' }}">{{ $trade->buySellOpen }}</span>
                        </td></tr>
                        <tr><th>Buy/Sell (Close)</th><td>
                            <span class="badge bg-{{ $trade->buySellClose === 'BUY' ? 'success' : 'danger' }}">{{ $trade->buySellClose }}</span>
                        </td></tr>
                        <tr><th>Quantity</th><td>{{ number_format(abs($trade->quantity), 0) }}</td></tr>
                        <tr><th>Status</th><td><span class="badge bg-info">{{ $trade->status ?? '—' }}</span></td></tr>
                        <tr><th>Closed By</th><td>{{ $trade->closedBy ?? '—' }}</td></tr>
                        <tr><th>Strategie</th><td>{{ $trade->strategy ?? '—' }}</td></tr>
                        <tr><th>Opt. Strategie</th><td>{{ $trade->opt_strategy ?? '—' }}</td></tr>
                        <tr><th>Opt. Strategie Name</th><td>{{ $trade->opt_strategyName ?? '—' }}</td></tr>
                        <tr><th>Opt. Strategie ID</th><td><code>{{ $trade->opt_strategyID ?? '—' }}</code></td></tr>
                        <tr><th>Link ID</th><td><code>{{ $trade->linkID ?? '—' }}</code></td></tr>
                        <tr><th>Multiplier</th><td>{{ $trade->multiplier ?? '—' }}</td></tr>
                    </tbody>
                </table>
            </x-trade-card>

            {{-- 3. Zeitinformation --}}
            <x-trade-card title="Zeitinformation" icon="clock-history" class="mb-4">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th style="width:40%">Eröffnet</th><td>
                            {{ $trade->dateTimeOpen?->format('d.m.Y H:i:s') }}
                            <small class="text-muted ms-2">{{ $trade->dateTimeOpen?->diffForHumans() }}</small>
                        </td></tr>
                        <tr><th>Geschlossen</th><td>
                            {{ $trade->dateTimeClose?->format('d.m.Y H:i:s') }}
                            <small class="text-muted ms-2">{{ $trade->dateTimeClose?->diffForHumans() }}</small>
                        </td></tr>
                        <tr><th>Days Held</th><td>{{ $trade->daysHeld }}</td></tr>
                        <tr><th>Total Seconds</th><td>{{ number_format($trade->totalSeconds) }}</td></tr>
                        <tr><th>Duration</th><td><code>{{ $trade->durationStr ?? '—' }}</code></td></tr>
                    </tbody>
                </table>
            </x-trade-card>

            {{-- 4. Preise --}}
            <x-trade-card title="Preise" icon="currency-dollar" class="mb-4">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th style="width:40%">Preis Open</th><td>{{ number_format($trade->tradePriceOpen, 2, ',', '.') }} $</td></tr>
                        <tr><th>Preis Close</th><td>{{ number_format($trade->tradePriceClose, 2, ',', '.') }} $</td></tr>
                    </tbody>
                </table>
            </x-trade-card>

            {{-- 5. Cashflows USD --}}
            <x-trade-card title="Cashflows (USD)" icon="cash-coin" class="mb-4">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th style="width:40%">Net Cash Open</th><td><x-currency-value :value="$trade->netCashOpen" currency="USD" :colored="true" /></td></tr>
                        <tr><th>Net Cash Close</th><td><x-currency-value :value="$trade->netCashClose" currency="USD" :colored="true" /></td></tr>
                        <tr><th>Proceeds Open</th><td><x-currency-value :value="$trade->prceedsOpen" currency="USD" /></td></tr>
                        <tr><th>Proceeds Close</th><td><x-currency-value :value="$trade->prceedsClose" currency="USD" /></td></tr>
                        <tr><th>Kommission</th><td><x-currency-value :value="$trade->ibCommission" currency="USD" /></td></tr>
                        <tr><th>Profit/Loss</th><td><x-currency-value :value="$trade->profit" currency="USD" :colored="true" /></td></tr>
                        <tr><th>ROI</th><td>
                            <span class="{{ $trade->ROI >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($trade->ROI, 2) }} %
                            </span>
                        </td></tr>
                    </tbody>
                </table>
            </x-trade-card>

            {{-- 6. Cashflows EUR --}}
            <x-trade-card title="Cashflows (EUR)" icon="currency-euro" class="mb-4">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th style="width:40%">FX Rate (Open)</th><td><code>{{ $trade->fxRateToBase_open ?? '—' }}</code></td></tr>
                        <tr><th>FX Rate (Close)</th><td><code>{{ $trade->fxRateToBase_close ?? '—' }}</code></td></tr>
                        <tr><th>Net Cash Open (EUR)</th><td><x-currency-value :value="$trade->netCashOpen_EUR" currency="EUR" :colored="true" /></td></tr>
                        <tr><th>Net Cash Close (EUR)</th><td><x-currency-value :value="$trade->netCashClose_EUR" currency="EUR" :colored="true" /></td></tr>
                        <tr><th>Kommission (EUR)</th><td><x-currency-value :value="$trade->ibCommission_EUR" currency="EUR" /></td></tr>
                        <tr><th>Profit/Loss (EUR)</th><td><x-currency-value :value="$trade->profit_EUR" currency="EUR" :colored="true" /></td></tr>
                    </tbody>
                </table>
            </x-trade-card>

            {{-- 7. Trade IDs --}}
            <x-trade-card title="Trade IDs" icon="hash" class="mb-4">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th style="width:40%">Trade ID Open</th><td><code>{{ $trade->tradeIDOpen ?? '—' }}</code></td></tr>
                        <tr><th>Trade ID Close</th><td><code>{{ $trade->tradeIDClose ?? '—' }}</code></td></tr>
                        <tr><th>IB Order ID Open</th><td><code>{{ $trade->ibOrderIDOpen ?? '—' }}</code></td></tr>
                        <tr><th>IB Order ID Close</th><td><code>{{ $trade->ibOrderIDClose ?? '—' }}</code></td></tr>
                    </tbody>
                </table>
            </x-trade-card>

        </div>

    </div>
</div>
