<?php

namespace Tests\Feature\Campaigns;

use App\Livewire\Campaigns\Campaigns;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\UsesTradingTestConnection;
use Tests\TestCase;

/**
 * Kampagnen-Art (Filter + Etikett in der Kampagnen-Liste): bestimmt aus allen Buchungen
 * einer Kampagne, nicht aus `strategy` (das Weaver nur nach dem ersten Trade setzt).
 *   STOCKS  = nur Aktien
 *   WHEEL   = Aktien und Optionen (mit oder ohne Zuteilung)
 *   SPREADS = nur Optionen, mindestens ein Leg mit opt_strategyID
 *   OPTIONS = nur Optionen, kein Spread
 */
class CampaignTypeFilterTest extends TestCase
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

    /** Legt eine Kampagne mit Buchungen an; jede Buchung: [assetCategory, opt_strategyID|null]. */
    private function campaign(string $symbol, array $entries): int
    {
        $db = DB::connection('trading_test');
        $id = $db->table('trading_campaigns')->insertGetId([
            'name' => "PHPUNIT $symbol", 'symbol' => $symbol, 'status' => 'CLOSED',
            'start_date' => '2026-03-01 10:00:00', 'end_date' => '2026-03-20 10:00:00',
            'environment' => 'TEST',
        ]);
        foreach ($entries as [$assetCategory, $strategyId]) {
            $openId = $db->table('trades_open')->insertGetId([
                'symbol' => $symbol, 'assetCategory' => $assetCategory,
                'opt_strategyID' => $strategyId, 'environment' => 'TEST',
            ]);
            $db->table('campaign_ledger')->insert([
                'campaign_id' => $id, 'assetCategory' => $assetCategory, 'symbol' => $symbol,
                'source_table' => 'TRADES_OPEN', 'source_id' => $openId,
                'dateTime' => '2026-03-02 10:00:00', 'environment' => 'TEST',
            ]);
        }
        return $id;
    }

    public function test_filter_zeigt_je_art_genau_die_passende_kampagne(): void
    {
        $user = User::factory()->create(['table_prefix' => 'phpunit_', 'filter_preset' => null]);
        $test = Livewire::actingAs($user)->test(Campaigns::class, ['filterStatus' => 'ALL']);
        $this->beginTradingTransaction();

        $this->campaign('PHPSTK', [['STK', null], ['STK', null]]);
        $this->campaign('PHPWHL', [['OPT', null], ['STK', null]]);          // Covered Call ohne Zuteilung
        $this->campaign('PHPSPR', [['OPT', 'spread-1'], ['OPT', 'spread-1']]);
        $this->campaign('PHPOPT', [['OPT', null]]);
        $this->campaign('PHPMIX', [['OPT', 'spread-2'], ['OPT', null]]);    // Spread + Einzeloption

        $expected = [
            'STOCKS'  => ['PHPSTK'],
            'WHEEL'   => ['PHPWHL'],
            'SPREADS' => ['PHPMIX', 'PHPSPR'],
            'OPTIONS' => ['PHPOPT'],
        ];
        foreach ($expected as $type => $symbols) {
            $campaigns = $test->call('setType', $type)->viewData('campaigns');
            $found = collect($campaigns->items())->pluck('symbol')->filter(fn($s) => str_starts_with($s, 'PHP'))->sort()->values()->all();
            $this->assertSame($symbols, $found, "Filter $type");
        }
    }

    public function test_alle_arten_zeigt_jede_kampagne_mit_etikett(): void
    {
        $user = User::factory()->create(['table_prefix' => 'phpunit_', 'filter_preset' => null]);
        $test = Livewire::actingAs($user)->test(Campaigns::class, ['filterStatus' => 'ALL']);
        $this->beginTradingTransaction();

        $this->campaign('PHPSTK', [['STK', null]]);
        $this->campaign('PHPWHL', [['STK', null], ['OPT', null]]);

        $campaigns = collect($test->call('setType', 'ALL')->viewData('campaigns')->items())->keyBy('symbol');

        $this->assertSame('STOCKS', $campaigns['PHPSTK']->campaign_type);
        $this->assertSame('WHEEL', $campaigns['PHPWHL']->campaign_type);
        $test->assertSee('Wheel')->assertSee('Aktien');
    }

    public function test_unbekannte_art_faellt_auf_alle_zurueck(): void
    {
        $user = User::factory()->create(['table_prefix' => 'phpunit_', 'filter_preset' => null]);

        Livewire::actingAs($user)->test(Campaigns::class)
            ->call('setType', 'GIBTSNICHT')
            ->assertSet('filterType', 'ALL');
    }
}
