<x-stat-card
    title="Größter Verlust"
    :value="$largestLoss !== null ? '$ ' . number_format($largestLoss, 2) : '–'"
    icon="arrow-down-circle"
    :color="$largestLoss !== null ? 'danger' : 'secondary'"
    :subtitle="$largestLoss !== null ? ($largestLossSymbol . ' · ' . $largestLossDate) : 'Kein Verlust im Zeitraum'"
/>
