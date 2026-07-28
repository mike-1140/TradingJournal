<x-stat-card
    title="Größter Gewinn"
    :value="$largestWin !== null ? '$ ' . number_format($largestWin, 2) : '–'"
    icon="arrow-up-circle"
    :color="$largestWin !== null ? 'success' : 'secondary'"
    :subtitle="$largestWin !== null ? ($largestWinSymbol . ' · ' . $largestWinDate) : 'Kein Gewinn im Zeitraum'"
/>
