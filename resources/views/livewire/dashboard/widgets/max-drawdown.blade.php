@if($navCurrent !== null)
    <x-stat-card
        title="Max Drawdown"
        :value="'−€ ' . number_format($maxDrawdownAbs, 2, ',', '.') . ' (' . number_format($maxDrawdownPct, 1, ',', '.') . '%)'"
        icon="graph-down-arrow"
        :color="$maxDrawdownPct >= 10 ? 'danger' : ($maxDrawdownPct >= 5 ? 'warning' : 'success')"
        subtitle="Größter Rückgang vom bisherigen Hoch"
    />
@else
    <x-stat-card
        title="Max Drawdown"
        value="–"
        icon="graph-down-arrow"
        color="secondary"
        subtitle="Keine NAV-Daten vorhanden"
    />
@endif
