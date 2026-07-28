<x-stat-card
    title="Offene Kampagnen"
    :value="number_format($activeCampaignsCount)"
    icon="briefcase"
    color="primary"
    :subtitle="'Kapital gebunden: $ ' . number_format($capitalAtRisk, 0)"
/>
