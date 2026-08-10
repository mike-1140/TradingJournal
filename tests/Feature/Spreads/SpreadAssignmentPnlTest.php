<?php

namespace Tests\Feature\Spreads;

use App\Livewire\Spreads\SpreadsList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\UsesTradingTestConnection;
use Tests\TestCase;

/**
 * Regressionstest fuer den Spread-Assignment-linkID-Bug (siehe Memory
 * project-spread-assignment-linkid-bug, behoben 2026-07-31): wird ein
 * Spread-Leg per Assignment/Exercise geschlossen, entsteht zusaetzlich ein
 * Stock-Trade (Zwangskauf/-verkauf zum Strike). Der reine Options-P&L einer
 * Spread-Gruppe ignorierte diesen Stock-Trade -- ein per Assignment
 * glattgestellter Spread wirkte dadurch kuenstlich profitabel.
 *
 * Nachgebautes TXN-Beispiel (Kampagne #665): Bull Put Spread 295/300, reine
 * Options-Legs +118 $, zusaetzlicher Stock-Trade aus der Zuteilung -500 $ ->
 * tatsaechliches Ergebnis -382 $.
 */
class SpreadAssignmentPnlTest extends TestCase
{
    use RefreshDatabase;
    use UsesTradingTestConnection;

    protected function setUp(): void
    {
        parent::setUp();
        // Nur Praefix setzen -- die Transaktion beginnt erst NACH dem ersten
        // Livewire::test()-Aufruf (siehe UsesTradingTestConnection-Docblock:
        // mount() -> ManagesEnvironment::applyUserPrefix() -> DB::purge()
        // wuerde eine hier schon offene Transaktion sonst verwerfen).
        $this->setPhpunitPrefix();
    }

    protected function tearDown(): void
    {
        $this->rollbackTradingTransaction();
        parent::tearDown();
    }

    public function test_spread_liste_rechnet_die_aktienzuteilung_aus_dem_assignment_ein(): void
    {
        $user = User::factory()->create(['table_prefix' => 'phpunit_']);

        // Erstes Mounten loest ManagesEnvironment::applyUserPrefix() und damit
        // DB::purge('trading_test') aus -- erst DANACH ist die Connection stabil
        // genug fuer eine Transaktion, die bis zum tearDown() haelt.
        $test = Livewire::actingAs($user)->test(SpreadsList::class, ['filterStatus' => 'closed']);
        $this->beginTradingTransaction();

        $linkId = 'test-assignment-link-665';
        $defaults = [
            'assetCategory' => null, 'underlyingSymbol' => null, 'opt_strategyID' => null,
            'opt_strategyName' => null, 'environment' => 'TEST', 'dateTimeOpen' => null,
            'dateTimeClose' => null, 'buySellOpen' => null, 'putCall' => null, 'strike' => null,
            'profit' => 0, 'linkID' => null, 'closedBy' => null, 'ibCommission' => 0,
        ];

        $optLeg1 = array_merge($defaults, [
            'assetCategory' => 'OPT', 'underlyingSymbol' => 'TXN', 'opt_strategyID' => 'test-strategy-665',
            'opt_strategyName' => 'Bull Put Spread', 'dateTimeOpen' => '2026-06-22 10:00:00',
            'dateTimeClose' => '2026-07-17 10:00:00', 'buySellOpen' => 'SELL', 'putCall' => 'P',
            'strike' => 300, 'profit' => 646, 'closedBy' => 'ASSIGNMENT',
        ]);
        $optLeg2 = array_merge($optLeg1, ['strike' => 295, 'profit' => -528, 'linkID' => $linkId]);
        // Stock-Trade aus der Zuteilung: gleiche linkID, assetCategory=STK, kein opt_strategyID
        $stockTrade = array_merge($defaults, [
            'assetCategory' => 'STK', 'underlyingSymbol' => 'TXN', 'dateTimeClose' => '2026-07-17 10:00:00',
            'linkID' => $linkId, 'profit' => -500, 'closedBy' => 'BUY',
        ]);

        DB::connection('trading_test')->table('trades_close')->insert([$optLeg1, $optLeg2, $stockTrade]);

        // Erzwingt einen frischen render() nach dem Insert (harmlose No-Op-Action).
        $spreads = $test->call('resetFilters')->viewData('spreads');

        $spread = collect($spreads->items())->firstWhere('opt_strategyID', 'test-strategy-665');

        $this->assertNotNull($spread, 'Erwarteter Spread wurde nicht gefunden');
        $this->assertEquals(-382.0, (float) $spread->total_profit);
    }
}
