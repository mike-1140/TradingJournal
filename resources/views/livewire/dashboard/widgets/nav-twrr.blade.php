<x-stat-card
    title="TWRR"
    :value="number_format($navTwrr, 2, ',', '.') . ' %'"
    icon="percent"
    :color="$navTwrr >= 0 ? 'success' : 'danger'"
    subtitle="Time-Weighted Rate of Return"
/>
