<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTradingTestConnection;
use Tests\TestCase;

/**
 * Beweist, dass UsesTradingTestConnection tatsaechlich isoliert: Test 1 legt
 * eine Zeile an und sieht sie innerhalb der eigenen Transaktion. Test 2
 * startet frisch und findet die Tabelle leer vor -- der Rollback aus Test 1
 * hat nichts hinterlassen. Nutzt die 'symbols'-Tabelle unter dem
 * phpunit_-Praefix in ibkr_test, nie karola_/mike_.
 */
class TradingConnectionSmokeTest extends TestCase
{
    use UsesTradingTestConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTradingTestConnection();
    }

    protected function tearDown(): void
    {
        $this->tearDownTradingTestConnection();
        parent::tearDown();
    }

    public function test_symbol_kann_innerhalb_der_transaktion_gelesen_werden(): void
    {
        DB::connection('trading_test')->table('symbols')->insert([
            'assetCategory' => 'STK',
            'symbol' => 'PHPUNIT_TEST',
            'environment' => 'TEST',
        ]);

        $this->assertSame(
            1,
            DB::connection('trading_test')->table('symbols')->where('symbol', 'PHPUNIT_TEST')->count()
        );
    }

    public function test_vorheriger_test_hat_dank_rollback_nichts_hinterlassen(): void
    {
        $this->assertSame(
            0,
            DB::connection('trading_test')->table('symbols')->where('symbol', 'PHPUNIT_TEST')->count()
        );
    }
}
