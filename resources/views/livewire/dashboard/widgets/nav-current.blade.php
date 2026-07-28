<x-stat-card
    title="NAV aktuell"
    :value="'€ ' . number_format($navCurrent, 2, ',', '.')"
    icon="wallet2"
    color="primary"
    :subtitle="'Stand: ' . $navDate"
/>
