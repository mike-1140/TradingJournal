<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Legt die Tabellen des Weaver-Schritts "enrich" für den Test-Prefix phpunit_ an bzw. entfernt sie.
 * Gleiche Struktur wie ensure_tables() in TradingJournal-Weaver src/enrich_greeks.py — die
 * Testumgebung (reset_IBKR_DB.py) kennt sie nicht, da es abgeleitete Tabellen sind.
 */
trait CreatesGreeksTables
{
    protected function createGreeksTables(): void
    {
        $db = DB::connection('trading_test');
        $db->statement('CREATE TABLE IF NOT EXISTS `phpunit_iv_history` (
            underlying VARCHAR(20) NOT NULL, iv_date DATE NOT NULL, iv DECIMAL(10,6) NOT NULL,
            PRIMARY KEY (underlying, iv_date))');
        $db->statement('CREATE TABLE IF NOT EXISTS `phpunit_trade_greeks` (
            tradeID VARCHAR(50) NOT NULL PRIMARY KEY, environment VARCHAR(20) NOT NULL,
            symbol VARCHAR(50) NULL, underlying VARCHAR(20) NULL, trade_datetime DATETIME NULL,
            underlying_price DECIMAL(14,4) NULL, option_iv DECIMAL(10,6) NULL, delta DECIMAL(8,5) NULL,
            underlying_iv DECIMAL(10,6) NULL, ivr DECIMAL(6,2) NULL, ivp DECIMAL(6,2) NULL,
            iv_days INT NOT NULL DEFAULT 0, error VARCHAR(255) NULL, computed_at DATETIME NOT NULL)');
    }

    protected function dropGreeksTables(): void
    {
        $db = DB::connection('trading_test');
        $db->statement('DROP TABLE IF EXISTS `phpunit_trade_greeks`');
        $db->statement('DROP TABLE IF EXISTS `phpunit_iv_history`');
    }
}
