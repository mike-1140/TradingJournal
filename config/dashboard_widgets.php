<?php

// Widget-Registry für das Trading-Dashboard.
// Jeder Eintrag beschreibt eine Kachel: welche View sie rendert, in welcher
// Section/Reihe sie liegt, wie breit die Bootstrap-Spalte ist, und ob sie nur
// bei vorhandenen NAV-Snapshot-Daten angezeigt werden darf ('requires' => 'nav').
// 'requires' steuert NUR ob die Kachel überhaupt ins DOM gerendert wird - bei
// ApexCharts-Kacheln (chart_nav) bleibt das Element aus JS-Stabilitätsgründen
// immer im DOM und wird stattdessen per CSS d-none versteckt (siehe
// feedback_apexcharts_conditional.md).

return [

    // --- KPI-Boxen: fixe Perioden + Zeitraum ---
    'pnl_month' => [
        'title'   => 'Brutto P&L Monat',
        'section' => 'kpi_primary',
        'col'     => 'col-md-4',
        'view'    => 'livewire.dashboard.widgets.pnl-month',
    ],
    'pnl_ytd' => [
        'title'   => 'Brutto P&L YTD',
        'section' => 'kpi_primary',
        'col'     => 'col-md-4',
        'view'    => 'livewire.dashboard.widgets.pnl-ytd',
    ],
    'pnl_period' => [
        'title'   => 'Brutto P&L Zeitraum',
        'section' => 'kpi_primary',
        'col'     => 'col-md-4',
        'view'    => 'livewire.dashboard.widgets.pnl-period',
    ],

    'win_rate' => [
        'title'   => 'Win Rate',
        'section' => 'kpi_secondary',
        'col'     => 'col-md-4',
        'view'    => 'livewire.dashboard.widgets.win-rate',
    ],
    'avg_pnl_trade' => [
        'title'   => 'Ø Brutto P&L / Trade',
        'section' => 'kpi_secondary',
        'col'     => 'col-md-4',
        'view'    => 'livewire.dashboard.widgets.avg-pnl-trade',
    ],
    'open_campaigns' => [
        'title'   => 'Offene Kampagnen',
        'section' => 'kpi_secondary',
        'col'     => 'col-md-4',
        'view'    => 'livewire.dashboard.widgets.open-campaigns',
    ],

    // --- Risiko-Kennzahlen ---
    'profit_factor' => [
        'title'   => 'Profit Factor',
        'section' => 'kpi_risk',
        'col'     => 'col-md-6',
        'view'    => 'livewire.dashboard.widgets.profit-factor',
    ],
    'max_drawdown' => [
        'title'    => 'Max Drawdown',
        'section'  => 'kpi_risk',
        'col'      => 'col-md-6',
        'view'     => 'livewire.dashboard.widgets.max-drawdown',
        'requires' => 'nav',
    ],

    // --- Performance-Detail ---
    'reward_risk_ratio' => [
        'title'   => 'Reward/Risk-Ratio',
        'section' => 'kpi_performance',
        'col'     => 'col-md-4',
        'view'    => 'livewire.dashboard.widgets.reward-risk-ratio',
    ],
    'largest_win' => [
        'title'   => 'Größter Gewinn',
        'section' => 'kpi_performance',
        'col'     => 'col-md-4',
        'view'    => 'livewire.dashboard.widgets.largest-win',
    ],
    'largest_loss' => [
        'title'   => 'Größter Verlust',
        'section' => 'kpi_performance',
        'col'     => 'col-md-4',
        'view'    => 'livewire.dashboard.widgets.largest-loss',
    ],

    // --- NAV-KPIs (nur bei vorhandenem Account-Snapshot) ---
    'nav_current' => [
        'title'    => 'NAV aktuell',
        'section'  => 'nav_primary',
        'col'      => 'col-md-4',
        'view'     => 'livewire.dashboard.widgets.nav-current',
        'requires' => 'nav',
    ],
    'nav_change' => [
        'title'    => 'NAV Änderung (Tag)',
        'section'  => 'nav_primary',
        'col'      => 'col-md-4',
        'view'     => 'livewire.dashboard.widgets.nav-change',
        'requires' => 'nav',
    ],
    'nav_twrr' => [
        'title'    => 'TWRR',
        'section'  => 'nav_primary',
        'col'      => 'col-md-4',
        'view'     => 'livewire.dashboard.widgets.nav-twrr',
        'requires' => 'nav',
    ],
    'cash_eur' => [
        'title'    => 'Cash EUR',
        'section'  => 'nav_secondary',
        'col'      => 'col-md-3',
        'view'     => 'livewire.dashboard.widgets.cash-eur',
        'requires' => 'nav',
    ],
    'cash_usd' => [
        'title'    => 'Cash USD',
        'section'  => 'nav_secondary',
        'col'      => 'col-md-3',
        'view'     => 'livewire.dashboard.widgets.cash-usd',
        'requires' => 'nav',
    ],
    'realized_today' => [
        'title'    => 'Realisiert (Tag)',
        'section'  => 'nav_secondary',
        'col'      => 'col-md-3',
        'view'     => 'livewire.dashboard.widgets.realized-today',
        'requires' => 'nav',
    ],
    'unrealized_today' => [
        'title'    => 'Unrealisiert (Tag)',
        'section'  => 'nav_secondary',
        'col'      => 'col-md-3',
        'view'     => 'livewire.dashboard.widgets.unrealized-today',
        'requires' => 'nav',
    ],

    // --- Charts ---
    // chart_nav: KEIN 'requires' => bleibt immer im DOM, Sichtbarkeit per CSS
    // (ApexCharts + wire:ignore, siehe feedback_apexcharts_conditional.md)
    'chart_nav' => [
        'title'   => 'NAV-Verlauf',
        'section' => 'chart_nav_row',
        'col'     => 'col-12',
        'view'    => 'livewire.dashboard.widgets.chart-nav',
    ],
    'chart_cumulative' => [
        'title'   => 'Kumulativer P&L',
        'section' => 'charts_main',
        'col'     => 'col-12',
        'view'    => 'livewire.dashboard.widgets.chart-cumulative',
    ],
    'chart_strategy' => [
        'title'   => 'P&L nach Spreads',
        'section' => 'charts_secondary',
        'col'     => 'col-md-6',
        'view'    => 'livewire.dashboard.widgets.chart-strategy',
    ],
    'chart_instrument' => [
        'title'   => 'P&L nach Option/Aktie',
        'section' => 'charts_secondary',
        'col'     => 'col-md-6',
        'view'    => 'livewire.dashboard.widgets.chart-instrument',
    ],
    'chart_monthly' => [
        'title'   => 'Monatlicher P&L',
        'section' => 'charts_monthly',
        'col'     => 'col-12',
        'view'    => 'livewire.dashboard.widgets.chart-monthly',
    ],

    // --- Tabellen ---
    'expiring_options' => [
        'title'   => 'Optionen verfallen diese Woche',
        'section' => 'table',
        'col'     => null,
        'view'    => 'livewire.dashboard.widgets.expiring-options',
    ],
    'open_positions_table' => [
        'title'   => 'Aktuelle offene Positionen',
        'section' => 'table',
        'col'     => null,
        'view'    => 'livewire.dashboard.widgets.open-positions-table',
    ],

];
