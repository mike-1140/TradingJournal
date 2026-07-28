<x-stat-card
    title="Brutto P&L YTD {{ \Carbon\Carbon::now()->year }}"
    :value="'$ ' . number_format($ytdProfit, 2)"
    icon="calendar-range"
    :color="$ytdProfit >= 0 ? 'success' : 'danger'"
    :subtitle="'Netto: $ ' . number_format($ytdNetProfit, 2)"
/>
