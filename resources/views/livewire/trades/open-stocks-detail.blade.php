<div>
    {{-- Header --}}
    <x-page-header
        title="Offene Position - {{ $trade->symbol }}"
        subtitle="eröffnet am {{ $trade->tradeDate->format('d.m.Y H:i') }}"
        icon="box-arrow-in-right"
        back-route="trades.open.stocks">
    </x-page-header>

    {{-- Flash Messages
    @if(session('message'))
        <x-alert-message type="success">{{ session('message') }}</x-alert-message>
    @endif

    @if(session('error'))
        <x-alert-message type="danger">{{ session('error') }}</x-alert-message>
    @endif--}}

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <x-stat-card
                title="Unrealized P/L"
                :value="number_format($trade->unrealizedPL ?? 0, 2) . ' $'"
                icon="graph-up-arrow"
                :color="($trade->unrealizedPL ?? 0) >= 0 ? 'success' : 'danger'"
                :subtitle="'Current value'"
                :trend="($trade->unrealizedPL ?? 0) >= 0 ? 'up' : 'down'" />
        </div>

        <div class="col-md-3">
            <x-stat-card
                title="Days Open"
                :value="$trade->tradeDate ? (int)abs(now()->diffInDays($trade->tradeDate)) : '-'"
                subtitle="Days in position"
                icon="calendar3"
                color="primary" />
        </div>

        <div class="col-md-3">
            <x-stat-card
                title="Position Size"
                :value="number_format(abs($trade->quantity), 0)"
                subtitle="Shares/Contracts"
                icon="box"
                color="info" />
        </div>

        <div class="col-md-3">
            <x-stat-card
                title="Cost Basis"
                :value="number_format($trade->costBasis ?? 0, 2) . ' $'"
                subtitle="Initial investment"
                icon="wallet2"
                color="secondary" />
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row">
        <div class="col-lg-8">
            {{-- Trade Information --}}
            <x-trade-card title="Position Details" icon="info-circle" class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <x-data-row label="Symbol" :value="$trade->symbol" icon="currency-dollar" />
                        <x-data-row label="Asset Type" :value="$trade->assetCategory" badge badge-color="primary" />
                        <x-data-row label="Underlying" :value="$trade->underlyingSymbol ?? '-'" />
                    </div>

                    <div class="col-md-6">
                        <x-data-row label="Trade Date" :value="$trade->tradeDate?->format('d.m.Y H:i')" icon="calendar" />
                        <x-data-row label="Quantity" :value="$trade->quantity" />
                        <x-data-row label="Open Price" :value="number_format($trade->tradePrice, 2) . ' $'" />
                        <x-data-row label="Direction">
                            <span class="badge bg-{{ $trade->quantity > 0 ? 'success' : 'danger' }}">
                                {{ $trade->quantity > 0 ? 'LONG' : 'SHORT' }}
                            </span>
                        </x-data-row>
                    </div>
                </div>
            </x-trade-card>

            {{-- Financial Details --}}
            <x-trade-card title="Financial Details" icon="calculator" class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <x-data-row label="Cost Basis" :value="number_format($trade->costBasis ?? 0, 2) . ' $'" />
                        <x-data-row label="Commission" :value="number_format($trade->commission ?? 0, 2) . ' $'" />
                        <x-data-row label="Unrealized P/L">
                            <strong class="text-{{ ($trade->unrealizedPL ?? 0) >= 0 ? 'success' : 'danger' }}">
                                {{ number_format($trade->unrealizedPL ?? 0, 2) }} $
                            </strong>
                        </x-data-row>
                    </div>

                    <div class="col-md-6">
                        <x-data-row label="Current Value">
                            <span class="text-muted">{{ number_format($trade->marketValue ?? 0, 2) }} $</span>
                        </x-data-row>
                        <x-data-row label="Unrealized ROI">
                            <span class="badge bg-{{ ($trade->unrealizedROI ?? 0) >= 0 ? 'success' : 'danger' }}">
                                {{ number_format($trade->unrealizedROI ?? 0, 2) }}%
                            </span>
                        </x-data-row>
                        <x-data-row label="Days Open" :value="($trade->tradeDate ? (int)abs(now()->diffInDays($trade->tradeDate)) : '-') . ' days'" />
                    </div>
                </div>
            </x-trade-card>

            {{-- Risk Management (Collapsible) -}}
            <x-trade-card title="Risk Management" icon="shield-exclamation" collapsible collapsed class="mb-4">
                <x-alert-message type="info" :dismissible="false">
                    <strong>Risk Analysis:</strong> Set your stop loss and take profit targets.
                </x-alert-message>

                <div class="row">
                    <div class="col-md-6">
                        <x-data-row label="Stop Loss">
                            <span class="text-muted">Not set</span>
                            <button class="btn btn-sm btn-outline-danger ms-2">Set Stop</button>
                        </x-data-row>
                    </div>
                    <div class="col-md-6">
                        <x-data-row label="Take Profit">
                            <span class="text-muted">Not set</span>
                            <button class="btn btn-sm btn-outline-success ms-2">Set Target</button>
                        </x-data-row>
                    </div>
                </div>
            </x-trade-card> --}}

            {{-- IDs (Collapsible) -}}
            <x-trade-card title="Trade IDs" icon="hash" collapsible collapsed class="mb-4">
                <x-data-row label="Trade ID">
                    <code>{{ $trade->tradeID }}</code>
                </x-data-row>
                <x-data-row label="Order ID">
                    <code>{{ $trade->orderID ?? '-' }}</code>
                </x-data-row>
                <x-data-row label="Environment" :value="$trade->environment" badge badge-color="info" />
            </x-trade-card> --}}
        </div>

        {{-- Tags --}}
        <x-trade-card title="Trade Tags" icon="tags" class="mb-4">
            @livewire('tags.trade-tagger', [
                'tradeId' => $trade->tradeID,
                'tradeType' => 'open'
            ])
        </x-trade-card>

        {{-- Notes --}}
        <x-trade-card title="Trade Notes" icon="journal-text">
            @livewire('notes.note-manager', [
                'tradeId' => $trade->tradeID,
                'tradeType' => 'open'
            ])
        </x-trade-card>
    </div>
</div>
