@php
    $pfDisplay = $closedTradesCount === 0
        ? '–'
        : ($profitFactor === null ? '∞' : number_format($profitFactor, 2, ',', '.'));
    $pfColor = $closedTradesCount === 0
        ? 'secondary'
        : ($profitFactor === null || $profitFactor >= 1.5 ? 'success' : ($profitFactor >= 1 ? 'warning' : 'danger'));
@endphp
<x-stat-card
    title="Profit Factor"
    :value="$pfDisplay"
    icon="speedometer2"
    :color="$pfColor"
    :subtitle="'Bruttogewinn: $ ' . number_format($grossProfit, 2) . ' · Bruttoverlust: $ ' . number_format($grossLoss, 2)"
/>
