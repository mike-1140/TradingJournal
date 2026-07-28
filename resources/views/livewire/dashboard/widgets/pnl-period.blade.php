<x-stat-card
    title="Brutto P&L Zeitraum"
    :value="'$ ' . number_format($totalProfit, 2)"
    icon="graph-up-arrow"
    :color="$totalProfit >= 0 ? 'success' : 'danger'"
    :subtitle="'Netto: $ ' . number_format($periodNetProfit, 2) . ' · ' . $zeitraumLabel . ' · ' . number_format($closedTradesCount) . ' Trades'"
/>
