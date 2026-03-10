<div>
    {{-- Header --}}
    <x-page-header
        title="Trade Details - {{ $trade->symbol }}"
        subtitle="{{ $trade->assetCategory }} • Closed {{ $trade->dateTimeClose->format('d.m.Y') }}"
        icon="check-circle"
        back-route="trades.closed.options">
    </x-page-header>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <x-stat-card
                title="Profit/Loss"
                :value="number_format($trade->profit, 2) . ' $'"
                icon="wallet2"
                :color="$trade->profit >= 0 ? 'success' : 'danger'"
                :trend="$trade->profit >= 0 ? 'up' : 'down'"
                :trend-value="number_format($trade->ROI, 2) . '%'" />
        </div>
        <div class="col-md-3">
            <x-stat-card
                title="Days Held"
                :value="$trade->daysHeld"
                icon="calendar3"
                color="primary" />
        </div>
        {{-- Mehr KPIs... --}}
    </div>

    {{-- Main Content --}}
    <div class="row">
        <div class="col-lg-8">
            {{-- Trade Info --}}
            <x-trade-card title="Trade Information" icon="info-circle" class="mb-4">
                <x-data-row label="Symbol" :value="$trade->symbol" icon="currency-dollar" />
                <x-data-row label="Type" :value="$trade->assetCategory" badge badge-color="primary" />
                <x-data-row label="Quantity" :value="$trade->quantity" />
                <x-data-row label="Open Price" :value="number_format($trade->openPrice, 2) . ' $'" />
                <x-data-row label="Close Price" :value="number_format($trade->closePrice, 2) . ' $'" />
            </x-trade-card>

            {{-- Price Chart
            @include('livewire.trades.partials.price-chart')--}}
        </div>

        <div class="col-lg-4">
            {{-- Tags --}}
            <x-trade-card title="Trade Tags" icon="tags" class="mb-4">
                @livewire('tags.trade-tagger', [
                    'tradeId' => $trade->tradeIDOpen,
                    'tradeType' => 'closed'
                ])
            </x-trade-card>

            {{-- Notes --}}
            <x-trade-card title="Trade Notes" icon="journal-text">
                @livewire('notes.note-manager', [
                    'tradeId' => $trade->tradeIDOpen,
                    'tradeType' => 'closed'
                ])
            </x-trade-card>
        </div>
    </div>
</div>
