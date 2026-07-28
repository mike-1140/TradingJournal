<x-stat-card
    title="Unrealisiert (Tag)"
    :value="($navUnrealizedPnl >= 0 ? '+' : '') . '€ ' . number_format($navUnrealizedPnl ?? 0, 2, ',', '.')"
    icon="hourglass-split"
    :color="($navUnrealizedPnl ?? 0) >= 0 ? 'success' : 'danger'"
/>
