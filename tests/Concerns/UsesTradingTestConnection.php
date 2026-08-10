<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Fuer Tests, die echte Trading-Daten lesen/schreiben (TradeClose, Campaign,
 * CampaignLedger, ...). Die App bindet diese Modelle fest an benannte
 * MySQL-Connections (trading_money/paper/test/...) -- SQLite kann das nicht
 * abbilden (siehe tests/README.md). Nutzt die 'trading_test'-Connection
 * (ibkr_test) mit einem eigenen, nur fuer automatisierte Tests reservierten
 * Praefix ('phpunit_'), NIE die echten karola_/mike_-Tabellen darin.
 *
 * Jeder Test laeuft in einer Transaktion, die am Ende zurueckgerollt wird --
 * kein Aufraeum-Code noetig, keine Seiteneffekte zwischen Tests.
 */
trait UsesTradingTestConnection
{
    protected function setUpTradingTestConnection(): void
    {
        Config::set('database.connections.trading_test.prefix', 'phpunit_');
        DB::purge('trading_test');
        DB::connection('trading_test')->beginTransaction();
    }

    protected function tearDownTradingTestConnection(): void
    {
        DB::connection('trading_test')->rollBack();
    }
}
