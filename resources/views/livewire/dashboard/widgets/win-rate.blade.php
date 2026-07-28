<x-stat-card
    title="Win Rate"
    :value="$winRate . '%'"
    icon="trophy"
    :color="$winRate >= 50 ? 'success' : 'warning'"
    :subtitle="number_format($closedTradesCount) . ' Trades · Ø ' . $avgDaysHeld . ' Tage'"
/>
