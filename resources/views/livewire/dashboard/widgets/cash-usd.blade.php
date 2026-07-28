<x-stat-card
    title="Cash USD"
    :value="'$ ' . number_format($navCashUsd ?? 0, 2, ',', '.')"
    icon="currency-dollar"
    color="secondary"
    :subtitle="'Stand: ' . $navDate"
/>
