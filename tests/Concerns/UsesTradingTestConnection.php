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
 * Praefix ('phpunit_'), NIE die echten Benutzer-Tabellen (z.B. anna_/tom_) darin.
 *
 * WICHTIG: ManagesEnvironment::applyUserPrefix() ruft bei JEDEM Component-
 * mount() DB::purge($connection) auf (auch wenn sich der Praefix nicht
 * aendert) -- das verwirft eine bereits offene Transaktion samt allem
 * unfertigen Inhalt. Deshalb sind Praefix-Setup und Transaktion getrennt:
 *
 *   - Tests OHNE Livewire::test() (reine DB-Zugriffe): setUpTradingTestConnection()/
 *     tearDownTradingTestConnection() wie gewohnt in setUp()/tearDown().
 *   - Tests MIT Livewire::test() auf einer ManagesEnvironment-Komponente:
 *     nur setPhpunitPrefix() in setUp(); beginTradingTransaction() ERST NACH
 *     dem ersten ->test()-Aufruf (also nach dem Mounten) manuell im Testfall
 *     aufrufen, danach ganz normal rollbackTradingTransaction() in tearDown().
 */
trait UsesTradingTestConnection
{
    protected function setUpTradingTestConnection(): void
    {
        $this->setPhpunitPrefix();
        $this->beginTradingTransaction();
    }

    protected function tearDownTradingTestConnection(): void
    {
        $this->rollbackTradingTransaction();
    }

    protected function setPhpunitPrefix(): void
    {
        Config::set('database.connections.trading_test.prefix', 'phpunit_');
        DB::purge('trading_test');
    }

    protected function beginTradingTransaction(): void
    {
        DB::connection('trading_test')->beginTransaction();
    }

    protected function rollbackTradingTransaction(): void
    {
        if (DB::connection('trading_test')->transactionLevel() > 0) {
            DB::connection('trading_test')->rollBack();
        }
    }
}
