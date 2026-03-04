<?php
// config/trading.php

return [
    /*
    |--------------------------------------------------------------------------
    | Trading Environments
    |--------------------------------------------------------------------------
    |
    | Verfügbare Trading-Environments und ihre Datenbank-Connections
    |
    */
    'environments' => [
        'MONEY' => [
            'label' => 'Live Trading',
            'connection' => 'trading_money',
            'color' => 'danger',
        ],
        'PAPER' => [
            'label' => 'Paper Trading',
            'connection' => 'trading_paper',
            'color' => 'warning',
        ],
        'TEST' => [
            'label' => 'Test Environment',
            'connection' => 'trading_test',
            'color' => 'secondary',
        ],
        'AUTOM_MONEY' => [
            'label' => 'Automation Live Trading',
            'connection' => 'trading_autom_money',
            'color' => 'danger',
        ],
        'AUTOM_PAPER' => [
            'label' => 'Automation Paper Trading',
            'connection' => 'trading_autom_paper',
            'color' => 'warning',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Environment
    |--------------------------------------------------------------------------
    */
    'default_environment' => env('TRADING_DEFAULT_ENV', 'TEST'),

    /*
    |--------------------------------------------------------------------------
    | User Table Prefix
    |--------------------------------------------------------------------------
    */
    'user_prefix_format' => '{username}_',
];
