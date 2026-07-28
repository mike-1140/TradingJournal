<x-stat-card
    title="Ø Brutto P&L / Trade"
    :value="'$ ' . number_format($avgProfitPerTrade, 2)"
    icon="bar-chart-line"
    :color="$avgProfitPerTrade >= 0 ? 'success' : 'danger'"
    :subtitle="'Ø Netto: $ ' . number_format($avgNetProfitPerTrade, 2) . ' · Komm.: $ ' . number_format($totalCommissions, 2)"
/>
