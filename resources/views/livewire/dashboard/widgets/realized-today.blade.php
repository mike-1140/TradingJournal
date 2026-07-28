<x-stat-card
    title="Realisiert (Tag)"
    :value="($navRealizedPnl >= 0 ? '+' : '') . '€ ' . number_format($navRealizedPnl ?? 0, 2, ',', '.')"
    icon="check-circle"
    :color="($navRealizedPnl ?? 0) >= 0 ? 'success' : 'danger'"
/>
