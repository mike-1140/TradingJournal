<x-stat-card
    title="Brutto P&L {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}"
    :value="'$ ' . number_format($monthProfit, 2)"
    icon="calendar-check"
    :color="$monthProfit >= 0 ? 'success' : 'danger'"
    :trend="$monthTrend['direction']"
    :trend-value="$monthTrend['label']"
    :subtitle="'Netto: $ ' . number_format($monthNetProfit, 2)"
/>
