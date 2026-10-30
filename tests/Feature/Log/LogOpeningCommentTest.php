<?php

namespace Tests\Feature\Log;

use App\Livewire\Campaigns\CampaignDetail;
use App\Livewire\Log\LogForm;
use App\Livewire\Log\LogList;
use App\Models\User;
use App\Support\GreeksLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\CreatesGreeksTables;
use Tests\Concerns\UsesTradingTestConnection;
use Tests\TestCase;

/**
 * Log als Kommentar je Eröffnung: die Zahlen kommen aus dem Ledger, gespeichert werden nur Texte
 * und von Hand eingetragene Kurs-/IV-Werte.
 *
 * mount() verwirft eine offene Transaktion (siehe UsesTradingTestConnection) → ohne Transaktion
 * angelegt und in tearDown() entfernt (nur phpunit_-Tabellen).
 */
class LogOpeningCommentTest extends TestCase
{
    use RefreshDatabase;
    use UsesTradingTestConnection;
    use CreatesGreeksTables;

    private const SYMBOL = 'PHPOPN';
    private const FIRST  = '2026-03-02 10:00:00';
    private const SECOND = '2026-03-12 11:00:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setPhpunitPrefix();
    }

    protected function tearDown(): void
    {
        $db = DB::connection('trading_test');
        $campaignIds = $db->table('trading_campaigns')->where('symbol', self::SYMBOL)->pluck('id');
        $planIds = $db->table('trade_planner')->where('underlying', self::SYMBOL)->pluck('id');
        $db->table('trade_planner_legs')->whereIn('plan_id', $planIds)->delete();
        $db->table('trade_planner')->whereIn('id', $planIds)->delete();
        $db->table('campaign_ledger')->whereIn('campaign_id', $campaignIds)->delete();
        $db->table('trading_campaigns')->whereIn('id', $campaignIds)->delete();
        $db->table('trades_open')->where('symbol', 'like', self::SYMBOL . '%')->delete();
        $db->table('trades_raw')->where('symbol', 'like', self::SYMBOL . '%')->delete();
        $this->dropGreeksTables();
        parent::tearDown();
    }

    /** Zwei Bull Put Spreads: 1. geschlossen (Kredit 120 $, P&L +60), 2. noch offen (Kredit 150 $). */
    private function seedCampaign(): int
    {
        $db = DB::connection('trading_test');
        $id = $db->table('trading_campaigns')->insertGetId([
            'name' => 'PHPUNIT ' . self::SYMBOL, 'symbol' => self::SYMBOL, 'status' => 'ACTIVE',
            'start_date' => self::FIRST, 'environment' => 'TEST',
        ]);
        $entry = fn(array $e) => $db->table('campaign_ledger')->insert(array_merge([
            'campaign_id' => $id, 'assetCategory' => 'OPT', 'environment' => 'TEST', 'multiplier' => 100,
            'commission' => -0.65, 'realized_pnl' => 0, 'source_table' => 'TRADES_OPEN', 'source_id' => 0,
        ], $e));
        $leg = fn(string $action, int $strike, float $qty, float $px, string $dt, float $pnl = 0) => $entry([
            'action' => $action, 'strike' => $strike, 'conid' => 9000 + $strike, 'tradePrice' => $px,
            'inventory_delta' => $qty, 'dateTime' => $dt, 'realized_pnl' => $pnl, 'symbol' => self::SYMBOL . " P$strike",
        ]);
        $leg('OPT_PUT_SELL', 100, -1, 2.00, self::FIRST);
        $leg('OPT_PUT_BUY', 95, 1, 0.80, self::FIRST);
        $leg('OPT_PUT_BUY_CLOSE', 100, 1, 1.35, '2026-03-10 11:00:00', 65);
        $leg('OPT_PUT_SELL_CLOSE', 95, -1, 0.75, '2026-03-10 11:00:00', -5);
        $leg('OPT_PUT_SELL', 90, -1, 2.50, self::SECOND);
        $leg('OPT_PUT_BUY', 85, 1, 1.00, self::SECOND);

        return $id;
    }

    private function user(): User
    {
        return User::where('table_prefix', 'phpunit_')->first() ?? User::factory()->create(['table_prefix' => 'phpunit_']);
    }

    private function plan(int $campaignId, array $values = []): int
    {
        return DB::connection('trading_test')->table('trade_planner')->insertGetId(array_merge([
            'environment' => 'TEST', 'campaign_id' => $campaignId, 'opened_at' => self::FIRST,
            'underlying' => self::SYMBOL, 'date_open' => '2026-03-02', 'status' => 'open',
        ], $values));
    }

    public function test_neuer_kommentar_zeigt_zahlen_der_eroeffnung_aus_dem_ledger(): void
    {
        $id = $this->seedCampaign();

        Livewire::withQueryParams(['campaign_id' => $id, 'opened_at' => self::FIRST])
            ->actingAs($this->user())->test(LogForm::class)
            ->assertSet('openedAt', self::FIRST)
            ->assertSet('date_open', '2026-03-02')
            ->assertSee('120,00 $ Kredit')
            ->assertSee('Prämie Close:')
            ->assertSee('60,00 $')
            ->assertSee('geschlossen')
            ->assertSee('Rationale')
            ->assertDontSee('Trade schließen')
            ->assertDontSee('Leg hinzufügen');
    }

    public function test_ohne_angabe_die_erste_noch_nicht_kommentierte_eroeffnung(): void
    {
        $id = $this->seedCampaign();
        $this->plan($id);

        Livewire::withQueryParams(['campaign_id' => $id])->actingAs($this->user())->test(LogForm::class)
            ->assertSet('openedAt', self::SECOND)
            ->assertSet('planId', null)
            ->assertSee('150,00 $ Kredit');
    }

    public function test_vorhandener_kommentar_wird_bearbeitet_statt_doppelt_angelegt(): void
    {
        $id = $this->seedCampaign();
        $planId = $this->plan($id, ['rationale' => 'erster Text']);

        Livewire::withQueryParams(['campaign_id' => $id, 'opened_at' => self::FIRST])
            ->actingAs($this->user())->test(LogForm::class)
            ->assertSet('planId', $planId)
            ->assertSet('rationale', 'erster Text');
    }

    /** Beide Eröffnungen bekommen eigene Einträge; gespeichert werden nur Texte, keine Ledger-Zahlen. */
    public function test_speichern_legt_je_eroeffnung_einen_eintrag_ohne_zahlen_an(): void
    {
        $id = $this->seedCampaign();
        $user = $this->user();

        foreach ([self::FIRST => 'Text eins', self::SECOND => 'Text zwei'] as $at => $text) {
            Livewire::withQueryParams(['campaign_id' => $id, 'opened_at' => $at])
                ->actingAs($user)->test(LogForm::class)
                ->set('rationale', $text)
                ->call('savePlan')
                ->assertHasNoErrors();
        }

        $db = DB::connection('trading_test');
        $rows = $db->table('trade_planner')->where('campaign_id', $id)->orderBy('opened_at')->get();
        $this->assertCount(2, $rows);
        $this->assertSame(['Text eins', 'Text zwei'], $rows->pluck('rationale')->all());
        $this->assertSame(['closed', 'open'], $rows->pluck('status')->all());
        $this->assertNull($rows[0]->realized_pnl);
        $this->assertNull($rows[0]->fees_open);
        $this->assertSame(0, $db->table('trade_planner_legs')->whereIn('plan_id', $rows->pluck('id'))->count());
    }

    /** Alte Zahlen und Legs eines bestehenden Eintrags bleiben beim Speichern unberührt. */
    public function test_speichern_laesst_bestehende_zahlen_und_legs_stehen(): void
    {
        $id = $this->seedCampaign();
        $planId = $this->plan($id, ['realized_pnl' => 57.40, 'fees_close' => 2.60]);
        $db = DB::connection('trading_test');
        $db->table('trade_planner_legs')->insert(['plan_id' => $planId, 'leg_type' => 'SP', 'strike' => 100, 'delta' => 31, 'premium' => 2.0, 'sort_order' => 0]);

        Livewire::actingAs($this->user())->test(LogForm::class, ['id' => $planId])
            ->set('notes', 'neu')->call('savePlan')->assertHasNoErrors();

        $row = $db->table('trade_planner')->where('id', $planId)->first();
        $this->assertSame('neu', $row->notes);
        $this->assertEquals(57.40, $row->realized_pnl);
        $this->assertEquals(2.60, $row->fees_close);
        $this->assertSame(1, $db->table('trade_planner_legs')->where('plan_id', $planId)->count());
    }

    public function test_eintrag_ohne_opened_at_gehoert_zur_ersten_eroeffnung(): void
    {
        $id = $this->seedCampaign();
        $planId = $this->plan($id, ['opened_at' => null]);

        Livewire::actingAs($this->user())->test(LogForm::class, ['id' => $planId])
            ->assertSet('openedAt', self::FIRST)
            ->assertSee('120,00 $ Kredit');
    }

    /** Zeitpunkt nicht im Ledger: Texte bleiben erreichbar, Formular zeigt die gespeicherten Werte. */
    public function test_eintrag_ohne_passende_eroeffnung_bleibt_bearbeitbar(): void
    {
        $id = $this->seedCampaign();
        $planId = $this->plan($id, ['opened_at' => '2020-01-01 00:00:00', 'rationale' => 'alter Text', 'realized_pnl' => 12]);

        Livewire::actingAs($this->user())->test(LogForm::class, ['id' => $planId])
            ->assertSee('nicht mehr zu finden')
            ->assertSet('rationale', 'alter Text')
            ->assertSet('realized_pnl', '12.00');

        Livewire::actingAs($this->user())->test(CampaignDetail::class, ['campaignId' => $id])
            ->assertSee('ohne passende Eröffnung')
            ->assertSee('alter Text');
    }

    public function test_unverknuepfter_eintrag_behaelt_das_volle_formular(): void
    {
        Livewire::actingAs($this->user())->test(LogForm::class)
            ->assertSee('Leg hinzufügen')
            ->assertSee('Trade schließen')
            ->assertDontSee('Prämie Eröffnung')
            ->set('underlying', self::SYMBOL)
            ->set('date_open', '2026-03-02')
            ->set('rationale', 'ohne Kampagne')
            ->call('toggleClose')
            ->set('realized_pnl', '42.50')
            ->call('savePlan')
            ->assertHasNoErrors();

        $row = DB::connection('trading_test')->table('trade_planner')->where('underlying', self::SYMBOL)->first();
        $this->assertNull($row->campaign_id);
        $this->assertEquals(42.50, $row->realized_pnl);
    }

    public function test_nachtraegliches_verknuepfen_waehlt_die_passende_eroeffnung(): void
    {
        $id = $this->seedCampaign();
        $this->plan($id);

        Livewire::actingAs($this->user())->test(LogForm::class)
            ->set('underlying', self::SYMBOL)
            ->set('date_open', '2026-03-03')
            ->call('linkCampaign', $id)
            ->assertSet('openedAt', self::SECOND);

        $planId = DB::connection('trading_test')->table('trade_planner')->insertGetId([
            'environment' => 'TEST', 'underlying' => self::SYMBOL, 'date_open' => '2026-03-11', 'status' => 'open',
        ]);
        Livewire::actingAs($this->user())->test(LogList::class)->call('linkPlanToCampaign', $planId, $id);
        $this->assertSame(self::SECOND, DB::connection('trading_test')->table('trade_planner')->where('id', $planId)->value('opened_at'));
    }

    public function test_kampagnenseite_zeigt_bloecke_und_zaehlt_kommentare(): void
    {
        $id = $this->seedCampaign();
        $this->plan($id, ['rationale' => 'Warum Spread eins']);

        Livewire::actingAs($this->user())->test(CampaignDetail::class, ['campaignId' => $id])
            ->assertSee('1 von 2 kommentiert')
            ->assertSee('120,00 $ Kredit')
            ->assertSee('150,00 $ Kredit')
            ->assertSee('Warum Spread eins')
            ->assertSee('Kommentar')
            ->assertSee('opened_at=2026-03-12', false);
    }

    /** Liste und Summen aus dem Ledger, nicht aus den alten Spalten; Filter folgt dem Ledger-Status. */
    public function test_log_liste_rechnet_aus_dem_ledger(): void
    {
        $id = $this->seedCampaign();
        $this->plan($id, ['status' => 'open', 'realized_pnl' => 999]);
        $this->plan($id, ['opened_at' => self::SECOND, 'status' => 'closed', 'date_open' => '2026-03-12', 'realized_pnl' => 111]);
        DB::connection('trading_test')->table('trade_planner')->insert([
            'environment' => 'TEST', 'underlying' => self::SYMBOL, 'date_open' => '2026-01-01', 'status' => 'closed', 'realized_pnl' => 10,
        ]);

        $test = Livewire::actingAs($this->user())->test(LogList::class);
        $test->assertViewHas('statistics', fn($s) => $s['open_count'] === 1 && $s['closed_count'] === 2 && abs($s['realized_pnl'] - 70) < 0.001);
        $test->assertSee('60,00 $')->assertDontSee('999,00');

        $test->set('filterStatus', 'open')
            ->assertViewHas('logs', fn($logs) => $logs->total() === 1 && $logs->first()->date_open->format('Y-m-d') === '2026-03-12');
    }

    /** Berechnete Werte als Vorschlag; von Hand eingetragene gelten; Hinweis nur beim berechneten Wert. */
    public function test_kurs_iv_und_delta_aus_enrich_von_hand_eingetragene_gelten(): void
    {
        $id = $this->seedCampaign();
        $this->createGreeksTables();
        $db = DB::connection('trading_test');
        $openId = $db->table('trades_open')->insertGetId(['tradeID' => 'O-1', 'symbol' => self::SYMBOL . ' P100',
            'assetCategory' => 'OPT', 'environment' => 'TEST', 'processed' => 1]);
        $db->table('campaign_ledger')->where('campaign_id', $id)->where('action', 'OPT_PUT_SELL')->where('strike', 100)
            ->update(['source_id' => $openId]);
        foreach ([['O-1', self::FIRST, 2.0, 101.23], ['C-1', '2026-03-10 11:00:00', 1.35, 98.76]] as [$tid, $dt, $px, $under]) {
            $db->table('trades_raw')->insert(['tradeID' => $tid, 'symbol' => self::SYMBOL . ' P100', 'conid' => 9100,
                'assetCategory' => 'OPT', 'dateTime' => $dt, 'tradePrice' => $px, 'environment' => 'TEST']);
            $db->table('trade_greeks')->insert(['tradeID' => $tid, 'environment' => 'TEST', 'underlying' => self::SYMBOL,
                'trade_datetime' => $dt, 'underlying_price' => $under, 'delta' => -0.32, 'computed_at' => now()]);
        }
        foreach (array_merge(array_fill(0, 10, 0.10), array_fill(0, 10, 0.30), [0.20]) as $i => $iv) {
            $db->table('iv_history')->insert(['underlying' => self::SYMBOL,
                'iv_date' => \Carbon\Carbon::parse('2026-03-02')->subDays(20 - $i)->toDateString(), 'iv' => $iv]);
        }
        $planId = $this->plan($id, ['price_close' => 77.50]);

        $test = Livewire::actingAs($this->user())->test(LogForm::class, ['id' => $planId]);
        $test->assertViewHas('details', fn($d) => $d['price_open'] == 101.23 && $d['price_close'] == 98.76
                && $d['ivr_open'] === 50 && $d['ivp_open'] === 50 && $d['deltas'][0] === 32)
            ->assertSee('aus IBKR berechnet')
            ->assertSee('placeholder="101.23"', false)
            ->assertSet('price_close', '77.50');

        // Auf der Kampagnenseite gilt der eingetragene Kurs vor dem berechneten
        Livewire::actingAs($this->user())->test(CampaignDetail::class, ['campaignId' => $id])
            ->assertViewHas('openingsView', fn($v) => $v['rows'][0]['details']['price_close'] == 77.50
                && $v['rows'][0]['details']['price_open'] == 101.23);
    }

    public function test_ohne_enrich_tabellen_kein_fehler(): void
    {
        $id = $this->seedCampaign();

        Livewire::withQueryParams(['campaign_id' => $id])->actingAs($this->user())->test(LogForm::class)
            ->assertOk()
            ->assertViewHas('details', fn($d) => $d['price_open'] === null && $d['ivr_open'] === null)
            ->assertDontSee('aus IBKR berechnet');
    }

    public function test_formel_gleich_wie_im_weaver(): void
    {
        $values = array_merge(array_fill(0, 10, 10.0), array_fill(0, 10, 30.0), [20.0]);
        $this->assertSame(['ivr' => 50, 'ivp' => 50], GreeksLookup::rankPercentile($values));
        $this->assertNull(GreeksLookup::rankPercentile(array_fill(0, GreeksLookup::MIN_IV_DAYS - 1, 0.2)));
        $this->assertSame(32, GreeksLookup::deltaForLog('-0.32000'));
    }
}
