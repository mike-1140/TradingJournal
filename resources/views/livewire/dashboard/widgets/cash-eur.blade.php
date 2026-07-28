<x-stat-card
    title="Cash EUR"
    :value="'€ ' . number_format($navCashEur ?? 0, 2, ',', '.')"
    icon="cash-coin"
    color="secondary"
    :subtitle="'Stand: ' . $navDate"
/>
