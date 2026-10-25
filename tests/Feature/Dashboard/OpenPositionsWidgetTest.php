<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\UsesTradingTestConnection;
use Tests\TestCase;

/**
 * Regressionstest: Das Dashboard-Widget "Aktuelle offene Positionen" war auf 10 Zeilen
 * begrenzt (limit(10)), ohne Hinweis auf weitere offene Positionen. Jetzt werden alle
 * angezeigt (Tabelle scrollt), die Anzahl steht im Kopf.
 */
class OpenPositionsWidgetTest extends TestCase
{
    use RefreshDatabase;
    use UsesTradingTestConnection;

    protected function setUp(): void
    {
        parent::setUp();
        // Transaktion erst nach dem ersten Livewire::test() (siehe UsesTradingTestConnection)
        $this->setPhpunitPrefix();
    }

    protected function tearDown(): void
    {
        $this->rollbackTradingTransaction();
        parent::tearDown();
    }

    public function test_zeigt_alle_offenen_positionen_mit_anzahl(): void
    {
        $user = User::factory()->create(['table_prefix' => 'phpunit_']);
        $test = Livewire::actingAs($user)->test(Dashboard::class);
        $this->beginTradingTransaction();

        $db = DB::connection('trading_test');
        for ($i = 1; $i <= 12; $i++) {
            $db->table('trades_open')->insert([
                'symbol' => sprintf('PHPOP%02d', $i), 'assetCategory' => 'STK',
                'dateTime' => sprintf('2026-03-%02d 10:00:00', $i),
                'processed' => 0, 'environment' => 'TEST',
            ]);
        }
        $expectedCount = $db->table('trades_open')
            ->where('processed', 0)->where('environment', 'TEST')->count();

        $trades = $test->call('$refresh')->viewData('currentOpenTrades');

        $symbols = $trades->pluck('symbol')->filter(fn($s) => str_starts_with($s, 'PHPOP'));
        $this->assertCount(12, $symbols, 'alle 12 Test-Positionen, nicht nur die neuesten 10');
        $this->assertCount($expectedCount, $trades);
        // Ältester Eintrag (fiel früher als 11./12. Zeile weg) ist sichtbar, Anzahl steht im Kopf
        $test->assertSee('PHPOP01')
            ->assertSeeHtml('title="Anzahl offener Positionen">' . $expectedCount . '</span>');
    }
}
