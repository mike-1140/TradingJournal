<?php

namespace Tests\Unit;

use App\Helpers\TradingHelper;
use Tests\TestCase;

/**
 * Reine Logik, kein DB-Zugriff -- laeuft ohne jede Datenbank-Verbindung.
 */
class TradingHelperTest extends TestCase
{
    public function test_bekanntes_environment_gilt_als_gueltig(): void
    {
        $this->assertTrue(TradingHelper::isValidEnvironment('MONEY'));
    }

    public function test_unbekanntes_environment_gilt_als_ungueltig(): void
    {
        $this->assertFalse(TradingHelper::isValidEnvironment('NICHT_EXISTENT'));
        $this->assertFalse(TradingHelper::isValidEnvironment(null));
    }

    public function test_normalize_gibt_gueltiges_environment_unveraendert_zurueck(): void
    {
        $this->assertSame('PAPER', TradingHelper::normalizeEnvironment('PAPER'));
    }

    public function test_normalize_faellt_bei_ungueltigem_environment_auf_default_zurueck(): void
    {
        $this->assertSame(
            config('trading.default_environment', 'TEST'),
            TradingHelper::normalizeEnvironment('UNSINN')
        );
    }

    public function test_getConnection_liefert_die_richtige_connection_je_environment(): void
    {
        $this->assertSame('trading_money', TradingHelper::getConnection('MONEY'));
        $this->assertSame('trading_test', TradingHelper::getConnection('TEST'));
    }

    public function test_getConnection_faellt_bei_leerem_environment_auf_default_zurueck(): void
    {
        $this->assertSame(
            TradingHelper::getConnection(config('trading.default_environment', 'TEST')),
            TradingHelper::getConnection(null)
        );
    }
}
