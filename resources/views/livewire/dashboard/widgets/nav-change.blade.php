<x-stat-card
    title="NAV Änderung (Tag)"
    :value="($navChange >= 0 ? '+' : '') . '€ ' . number_format($navChange, 2, ',', '.')"
    icon="arrow-up-down"
    :color="$navChange >= 0 ? 'success' : 'danger'"
/>
