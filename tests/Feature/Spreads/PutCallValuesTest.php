<?php

namespace Tests\Feature\Spreads;

use App\Livewire\Spreads\SpreadDetail;
use App\Livewire\Spreads\SpreadsList;
use App\Livewire\Trades\ClosedOptions;
use App\Livewire\Trades\ClosedOptionsDetail;
use App\Livewire\Trades\OpenOptions;
use App\Livewire\Trades\OpenOptionsDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\UsesTradingTestConnection;
use Tests\TestCase;

/**
 * Regressionstest (gefunden 2026-10-18, behoben 2026-10-26): IBKR liefert putCall als 'P'/'C',
 * mehrere Stellen verglichen aber mit 'PUT'/'CALL' -- Iron-Condor-Strukturgrafik leer,
 * Put-/Call-Strikes offener Spreads leer, Put/Call-Filter in Offene/Geschlossene Optionen
 * ohne Treffer, Call-Badges rot statt gruen.
 */
class PutCallValuesTest extends TestCase
{
    use RefreshDatabase;
    use UsesTradingTestConnection;

    private const IC_ID = 'phpunit-ic-putcall';

    protected function setUp(): void
    {
        parent::setUp();
        // Transaktion erst nach dem ersten Livewire::test() (siehe UsesTradingTestConnection)
        $this->setPhpunitPrefix();
    }

    protected function tearDown(): void
    {
        $this->rollbackTradingTransaction();
        // SpreadDetail braucht die Legs schon beim Mounten (sonst 404) und mount() verwirft eine
        // offene Transaktion -- diese Zeilen werden deshalb ohne Transaktion angelegt und hier
        // wieder entfernt (nur phpunit_-Tabellen).
        DB::connection('trading_test')->table('trades_close')->where('opt_strategyID', self::IC_ID)->delete();
        foreach (['trades_close', 'trades_open'] as $table) {
            DB::connection('trading_test')->table($table)->where('symbol', 'like', 'PHPPC %')->delete();
        }
        parent::tearDown();
    }

    private function user(): User
    {
        return User::factory()->create(['table_prefix' => 'phpunit_', 'filter_preset' => null]);
    }

    /** Iron Condor 90/95 P, 105/110 C -- Werte wie von IBKR (P/C). */
    private function ironCondorLegs(callable $extra): array
    {
        $legs = [];
        foreach ([['P', 90, 'BUY'], ['P', 95, 'SELL'], ['C', 105, 'SELL'], ['C', 110, 'BUY']] as [$pc, $strike, $bs]) {
            $legs[] = array_merge([
                'symbol' => "PHPIC $strike$pc", 'assetCategory' => 'OPT', 'underlyingSymbol' => 'PHPIC',
                'putCall' => $pc, 'strike' => $strike, 'opt_strategyID' => self::IC_ID,
                'opt_strategyName' => 'Iron Condor', 'environment' => 'TEST',
            ], $extra($bs));
        }
        return $legs;
    }

    public function test_iron_condor_strukturgrafik_zeigt_put_und_call_spread(): void
    {
        $closed = fn($bs) => [
            'buySellOpen' => $bs, 'tradePriceOpen' => 1, 'profit' => 10, 'ibCommission' => 0,
            'dateTimeOpen' => '2026-03-01 10:00:00', 'dateTimeClose' => '2026-03-20 16:20:00',
            'expiry' => '2026-03-20', 'quantity' => 1, 'multiplier' => 100,
        ];
        DB::connection('trading_test')->table('trades_close')->insert($this->ironCondorLegs($closed));

        $test = Livewire::actingAs($this->user())->test(SpreadDetail::class, ['opt_strategyID' => self::IC_ID]);

        $html = $test->html();
        // Beide Karten gefuellt: je Spread eine Width-Zeile, Strikes sichtbar
        $this->assertSame(2, substr_count($html, '<strong>Width:</strong>'), 'Put- und Call-Spread-Karte muessen gefuellt sein');
        $test->assertSee('95.00')->assertSee('105.00');
        // Call-Legs mit gruenem Badge
        $this->assertMatchesRegularExpression('/badge bg-success">\s*C\s*</', $html);
    }

    public function test_offene_spreads_liefern_put_und_call_strikes(): void
    {
        $test = Livewire::actingAs($this->user())->test(SpreadsList::class, ['filterStatus' => 'open']);
        $this->beginTradingTransaction();

        $open = fn($bs) => ['buySell' => $bs, 'processed' => 0, 'dateTime' => '2026-03-01 10:00:00',
                            'quantity' => 1, 'netCash' => 0, 'ibCommission' => 0];
        DB::connection('trading_test')->table('trades_open')->insert($this->ironCondorLegs($open));

        $spreads = $test->call('resetFilters')->viewData('spreads');
        $spread = collect($spreads->items())->firstWhere('opt_strategyID', self::IC_ID);

        $this->assertNotNull($spread);
        $this->assertSame('90/95', $spread->put_strikes);
        $this->assertSame('105/110', $spread->call_strikes);
    }

    public function test_put_filter_offene_optionen(): void
    {
        $test = Livewire::actingAs($this->user())->test(OpenOptions::class);
        $this->beginTradingTransaction();

        $open = fn($bs) => ['buySell' => $bs, 'processed' => 0, 'dateTime' => '2026-03-01 10:00:00',
                            'quantity' => 1, 'netCash' => 0, 'ibCommission' => 0];
        DB::connection('trading_test')->table('trades_open')->insert($this->ironCondorLegs($open));

        $test->assertSeeHtml('<option value="P">PUT</option>')->assertSeeHtml('<option value="C">CALL</option>');
        $puts = $test->set('filterPutCall', 'P')->viewData('trades');
        $symbols = collect($puts->items())->pluck('symbol')->filter(fn($s) => str_starts_with($s, 'PHPIC'))->sort()->values()->all();
        $this->assertSame(['PHPIC 90P', 'PHPIC 95P'], $symbols);
    }

    /** Option Details: Put rot (danger), Call gruen (success) -- war in beiden Detailseiten vertauscht. */
    public function test_option_detailseiten_put_rot_call_gruen(): void
    {
        $user = $this->user();
        $db = DB::connection('trading_test');
        $base = ['assetCategory' => 'OPT', 'underlyingSymbol' => 'PHPPC', 'strike' => 50,
                 'environment' => 'TEST', 'quantity' => -1, 'multiplier' => 100, 'expiry' => '2026-03-20'];
        foreach (['P' => 'danger', 'C' => 'success'] as $pc => $color) {
            $closeId = $db->table('trades_close')->insertGetId(array_merge($base, [
                'symbol' => "PHPPC 50$pc", 'putCall' => $pc, 'profit' => 10, 'ibCommission' => 0,
                'dateTimeOpen' => '2026-03-01 10:00:00', 'dateTimeClose' => '2026-03-20 16:20:00',
                'buySellOpen' => 'SELL', 'tradePriceOpen' => 1,
            ]));
            $openId = $db->table('trades_open')->insertGetId(array_merge($base, [
                'symbol' => "PHPPC 50$pc", 'putCall' => $pc, 'processed' => 0, 'buySell' => 'SELL',
                'dateTime' => '2026-03-01 10:00:00', 'tradePrice' => 1, 'netCash' => 100, 'ibCommission' => 0,
            ]));
            $label = $pc === 'P' ? 'PUT' : 'CALL';
            foreach ([[ClosedOptionsDetail::class, $closeId], [OpenOptionsDetail::class, $openId]] as [$component, $id]) {
                $html = Livewire::actingAs($user)->test($component, ['id' => $id])->html();
                $this->assertMatchesRegularExpression("/badge bg-$color\">\s*$label\s*</", $html, "$component $label");
            }
        }
    }

    public function test_put_filter_geschlossene_optionen(): void
    {
        $test = Livewire::actingAs($this->user())->test(ClosedOptions::class);
        $this->beginTradingTransaction();

        $test->assertSeeHtml('<option value="P">PUT</option>')->assertSeeHtml('<option value="C">CALL</option>');
    }
}
