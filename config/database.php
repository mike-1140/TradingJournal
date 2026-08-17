<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        // Haupt-Laravel Datenbank
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            // DEMO_MODE schaltet die komplette App auf die isolierte Demo-DB um
            // (Login, Users, Psychology-Tabellen) — analog zum App-Switch bei Naikido.
            'database' => env('DEMO_MODE', false) ? 'ibkr_demo' : env('DB_DATABASE', 'ibkr_laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',     // Kein Präfix für Laravel-Tabellen
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // Simulation/Paper Trading Datenbank
        'trading_paper' => [
            'driver' => 'mysql',
            'host' => env('TRADING_DB_HOST', '127.0.0.1'),
            'port' => env('TRADING_DB_PORT', '3306'),
            'database' => 'ibkr_paper',
            'username' => env('TRADING_DB_USERNAME', 'root'),
            'password' => env('TRADING_DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',     // Wird dynamisch gesetzt
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],
        // Test Trading Datenbank
        'trading_test' => [
            'driver' => 'mysql',
            'host' => env('TRADING_DB_HOST', '127.0.0.1'),
            'port' => env('TRADING_DB_PORT', '3306'),
            // DEMO_MODE zeigt hier auf dieselbe ibkr_demo-DB wie die Haupt-Connection —
            // die Demo-Trading-Daten liegen unter Environment TEST mit Prefix "demo_"
            // (siehe xml_proj/tools/demo/README.md). Prefix bleibt dynamisch (ManagesEnvironment
            // liest table_prefix des eingeloggten Users, für den Demo-User bereits "demo_").
            'database' => env('DEMO_MODE', false) ? 'ibkr_demo' : 'ibkr_test',
            'username' => env('TRADING_DB_USERNAME', 'root'),
            'password' => env('TRADING_DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',     // Wird dynamisch gesetzt
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],

        // Real Money Trading Datenbank
        'trading_money' => [
            'driver' => 'mysql',
            'host' => env('TRADING_DB_HOST', '127.0.0.1'),
            'port' => env('TRADING_DB_PORT', '3306'),
            'database' => 'ibkr_money',
            'username' => env('TRADING_DB_USERNAME', 'root'),
            'password' => env('TRADING_DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',     // Wird dynamisch gesetzt
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],
        // Real Money Trading Datenbank
        'trading_autom_money' => [
            'driver' => 'mysql',
            'host' => env('TRADING_DB_HOST', '127.0.0.1'),
            'port' => env('TRADING_DB_PORT', '3306'),
            'database' => 'ibkr_autom_money',
            'username' => env('TRADING_DB_USERNAME', 'root'),
            'password' => env('TRADING_DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',     // Wird dynamisch gesetzt
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],
        // Automaion - Simulation/Paper Trading Datenbank
        'trading_autom_paper' => [
            'driver' => 'mysql',
            'host' => env('TRADING_DB_HOST', '127.0.0.1'),
            'port' => env('TRADING_DB_PORT', '3306'),
            'database' => 'ibkr_autom_paper',
            'username' => env('TRADING_DB_USERNAME', 'root'),
            'password' => env('TRADING_DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',     // Wird dynamisch gesetzt
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],
    ],
];
