<div>
    {{-- Header --}}
    <x-page-header
        title="Offene Option - {{ $trade->symbol }}"
        subtitle="OPT • Eröffnet am {{ $trade->dateTime?->format('d.m.Y H:i') }}"
        icon="box-arrow-in-right"
        back-route="trades.open.options">
    </x-page-header>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <x-stat-card
                title="Eröffnungspreis"
                :value="number_format($trade->tradePrice, 2, ',', '.') . ' $'"
                icon="currency-dollar"
                color="primary" />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Contracts"
                :value="number_format(abs($trade->quantity), 0)"
                icon="box"
                color="info" />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Net Cash"
                :value="number_format($trade->netCash, 2, ',', '.') . ' $'"
                icon="wallet2"
                :color="$trade->netCash >= 0 ? 'success' : 'danger'" />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Days Open"
                :value="$trade->dateTime ? (int)abs(now()->diffInDays($trade->dateTime)) : '—'"
                icon="calendar3"
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
                        <tr><th>Underlying Symbol</th><td><strong>{{ $trade->underlyingSymbol ?? '—' }}</strong></td></tr>
                        <tr><th>Description</th><td>{{ $trade->description ?? '—' }}</td></tr>
                        <tr><th>Asset Category</th><td><span class="badge bg-primary">{{ $trade->assetCategory }}</span></td></tr>
                        <tr><th>Sub Category</th><td>{{ $trade->subCategory ?? '—' }}</td></tr>
                        <tr><th>Environment</th><td><span class="badge bg-secondary">{{ $trade->environment }}</span></td></tr>
                    </tbody>
                </table>
            </x-trade-card>

            {{-- 2. Option Details --}}
            <x-trade-card title="Option Details" icon="graph-up-arrow" class="mb-4">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th style="width:40%">Put/Call</th><td>
                            @if($trade->putCall)
                                <span class="badge bg-{{ $trade->putCall === 'C' ? 'success' : 'danger' }}">
                                    {{ $trade->putCall === 'P' ? 'PUT' : 'CALL' }}
                                </span>
                            @else
                                —
                            @endif
                        </td></tr>
                        <tr><th>Strike</th><td><strong>{{ $trade->strike ? number_format($trade->strike, 2) : '—' }}</strong></td></tr>
                        <tr><th>Expiry</th><td>{{ $trade->expiry ? $trade->expiry->format('d.m.Y') : '—' }}</td></tr>
                        <tr><th>Multiplier</th><td>{{ $trade->multiplier ?? '—' }}</td></tr>
                    </tbody>
                </table>
            </x-trade-card>

            {{-- 3. Trade Information --}}
            <x-trade-card title="Trade Information" icon="info-circle" class="mb-4">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th style="width:40%">Buy/Sell</th><td>
                            <span class="badge bg-{{ $trade->buySell === 'BUY' ? 'success' : 'danger' }}">{{ $trade->buySell ?? '—' }}</span>
                        </td></tr>
                        <tr><th>Quantity</th><td>{{ number_format(abs($trade->quantity), 0) }}</td></tr>
                        <tr><th>Status</th><td><span class="badge bg-info">{{ $trade->status ?? '—' }}</span></td></tr>
                        <tr><th>Strategie</th><td>{{ $trade->display_strategy ?? '—' }}</td></tr>
                        <tr><th>Opt. Strategie</th><td>{{ $trade->opt_strategy ?? '—' }}</td></tr>
                        <tr><th>Opt. Strategie Name</th><td>{{ $trade->opt_strategyName ?? '—' }}</td></tr>
                        <tr><th>Opt. Strategie ID</th><td><code>{{ $trade->opt_strategyID ?? '—' }}</code></td></tr>
                    </tbody>
                </table>
            </x-trade-card>

            {{-- 4. Zeitinformation --}}
            <x-trade-card title="Zeitinformation" icon="clock-history" class="mb-4">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th style="width:40%">Eröffnet</th><td>
                            {{ $trade->dateTime?->format('d.m.Y H:i:s') }}
                            <small class="text-muted ms-2">{{ $trade->dateTime?->diffForHumans() }}</small>
                        </td></tr>
                        <tr><th>Days Open</th><td>{{ $trade->dateTime ? (int)abs(now()->diffInDays($trade->dateTime)) : '—' }}</td></tr>
                    </tbody>
                </table>
            </x-trade-card>

            {{-- 5. Preise & Cashflows --}}
            <x-trade-card title="Preise & Cashflows" icon="cash-coin" class="mb-4">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th style="width:40%">Trade Price</th><td>{{ number_format($trade->tradePrice, 2, ',', '.') }} $</td></tr>
                        <tr><th>Net Cash</th><td><x-currency-value :value="$trade->netCash" currency="USD" :colored="true" /></td></tr>
                        <tr><th>Proceeds</th><td><x-currency-value :value="$trade->proceeds" currency="USD" /></td></tr>
                        <tr><th>Kommission</th><td><x-currency-value :value="$trade->ibCommission" currency="USD" /></td></tr>
                    </tbody>
                </table>
            </x-trade-card>

            @include('livewire.psychology.partials.entry-summary-card', [
                'psychEntry'     => $psychEntry,
                'psychCreateUrl' => route('psychology.create') . '?trade_id=' . $trade->id . '&trade_type=open&symbol=' . $trade->symbol,
            ])

        </div>

    </div>
</div>
