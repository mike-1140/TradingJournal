@php
    $rrDisplay = $rewardRiskRatio !== null ? number_format($rewardRiskRatio, 2, ',', '.') : '–';
    $rrColor = $rewardRiskRatio === null
        ? 'secondary'
        : ($rewardRiskRatio >= 2 ? 'success' : ($rewardRiskRatio >= 1 ? 'warning' : 'danger'));
@endphp
<x-stat-card
    title="Reward/Risk-Ratio"
    :value="$rrDisplay"
    icon="arrows-angle-expand"
    :color="$rrColor"
    :subtitle="'Ø Gewinn: $ ' . number_format($avgWin, 2) . ' · Ø Verlust: $ ' . number_format($avgLoss, 2)"
/>
