<?php

namespace Tests\Feature\Log;

use App\Livewire\Campaigns\CampaignDetail;
use App\Livewire\Campaigns\Campaigns;
use App\Livewire\Campaigns\GlobalSymbolDetail;
use App\Livewire\Log\LogForm;
use App\Livewire\Log\LogList;
use App\Livewire\Log\OpeningLogModal;
use App\Livewire\Stats\ActivityLog;
use App\Models\User;
use App\Support\GreeksLookup;
use App\Support\OpeningMarkers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\CreatesGreeksTables;
use Tests\Concerns\UsesTradingTestConnection;
use Tests\TestCase;

/**
 * Log je Eröffnung: Knopf in der Buchungszeile, Eingabe im Fenster, Zahlen aus dem Ledger.
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
    private function seedCampaign(string $status = 'ACTIVE', string $start = self::FIRST): int
    {
        $db = DB::connection('trading_test');
        $id = $db->table('trading_campaigns')->insertGetId([
            'name' => 'PHPUNIT ' . self::SYMBOL, 'symbol' => self::SYMBOL, 'status' => $status,
            'start_date' => $start, 'environment' => 'TEST',
        ]);
        $this->leg($id, 'OPT_PUT_SELL', 100, -1, 2.00, self::FIRST);
        $this->leg($id, 'OPT_PUT_BUY', 95, 1, 0.80, self::FIRST);
        $this->leg($id, 'OPT_PUT_BUY_CLOSE', 100, 1, 1.35, '2026-03-10 11:00:00', 65);
        $this->leg($id, 'OPT_PUT_SELL_CLOSE', 95, -1, 0.75, '2026-03-10 11:00:00', -5);
        $this->leg($id, 'OPT_PUT_SELL', 90, -1, 2.50, self::SECOND);
        $this->leg($id, 'OPT_PUT_BUY', 85, 1, 1.00, self::SECOND);

        return $id;
    }

    private function leg(int $id, string $action, int $strike, float $qty, float $px, string $dt, float $pnl = 0, array $extra = []): void
    {
        DB::connection('trading_test')->table('campaign_ledger')->insert(array_merge([
            'campaign_id' => $id, 'assetCategory' => 'OPT', 'environment' => 'TEST', 'multiplier' => 100,
            'commission' => -0.65, 'source_table' => 'TRADES_OPEN', 'source_id' => 0,
            'action' => $action, 'strike' => $strike, 'conid' => 9000 + $strike, 'tradePrice' => $px,
            'inventory_delta' => $qty, 'dateTime' => $dt, 'realized_pnl' => $pnl, 'symbol' => self::SYMBOL . " P$strike",
        ], $extra));
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

    private function modal(int $campaignId, string $openedAt)
    {
        return Livewire::actingAs($this->user())->test(OpeningLogModal::class)
            ->dispatch('open-opening-log', campaignId: $campaignId, openedAt: $openedAt);
    }

    // ── Knopf in der Zeile ───────────────────────────────────────────────

    /** Ein Spread hat zwei Zeilen, aber einen Knopf; schließende Zeilen haben keinen. */
    public function test_ein_knopf_je_eroeffnung_mit_status(): void
    {
        $id = $this->seedCampaign();
        $this->plan($id);
        $ledger = DB::connection('trading_test')->table('campaign_ledger')->where('campaign_id', $id)->orderBy('id')->get();

        $markers = OpeningMarkers::forEntries($ledger, 'trading_test', 'TEST');

        $this->assertCount(4, $markers, 'nur eröffnende Zeilen');
        $values = array_values($markers);
        $this->assertSame([true, false, true, false], array_column($values, 'first'));
        $this->assertSame([true, true, false, false], array_column($values, 'documented'));
        $this->assertSame(self::SECOND, $values[2]['opened_at']);
    }

    public function test_seite_zeigt_knopf_nach_status_und_oeffnet_die_richtige_eroeffnung(): void
    {
        $id = $this->seedCampaign();
        $this->plan($id);

        Livewire::actingAs($this->user())->test(CampaignDetail::class, ['campaignId' => $id])
            ->assertSee('1 von 2 Eröffnungen dokumentiert')
            ->assertSee('btn-success', false)
            ->assertSee('btn-outline-warning', false)
            ->assertSee("openedAt: '" . self::SECOND . "'", false)
            ->assertDontSee('Eröffnungen &amp; Kommentare', false);
    }

    /** Aktien aus einer Zuteilung sind keine Entscheidung; ein eigener Kauf schon. */
    public function test_kein_knopf_bei_zuteilung_aber_beim_aktienkauf(): void
    {
        $db = DB::connection('trading_test');
        $id = $db->table('trading_campaigns')->insertGetId(['name' => 'PHPUNIT', 'symbol' => self::SYMBOL, 'status' => 'ACTIVE',
            'start_date' => self::FIRST, 'environment' => 'TEST']);
        $stock = ['assetCategory' => 'STK', 'strike' => 0, 'symbol' => self::SYMBOL, 'conid' => 1];
        $this->leg($id, 'OPT_PUT_SELL', 30, -1, 0.50, self::FIRST);
        $this->leg($id, 'STK_BUY', 0, 100, 30, '2026-03-20 16:20:00', 0, $stock);
        $this->leg($id, 'OPTION_EXERCISE', 30, 1, 0, '2026-03-20 16:20:00', 50);
        $this->leg($id, 'STK_BUY', 0, 50, 28, '2026-04-01 10:00:00', 0, $stock);
        $ledger = $db->table('campaign_ledger')->where('campaign_id', $id)->orderBy('id')->get();

        $markers = OpeningMarkers::forEntries($ledger, 'trading_test', 'TEST');

        $this->assertSame([self::FIRST, '2026-04-01 10:00:00'], array_column($markers, 'opened_at'));
    }

    public function test_zuordnung_rechnet_mit_der_ganzen_kampagne_auch_bei_ausschnitt(): void
    {
        $id = $this->seedCampaign();
        $this->plan($id, ['opened_at' => self::SECOND, 'date_open' => '2026-03-12']);
        $shown = DB::connection('trading_test')->table('campaign_ledger')->where('campaign_id', $id)
            ->where('dateTime', self::SECOND)->orderBy('id')->get();

        $markers = array_values(OpeningMarkers::forEntries($shown, 'trading_test', 'TEST'));

        $this->assertCount(2, $markers);
        $this->assertTrue($markers[0]['documented']);
    }

    public function test_knopf_auch_in_symbol_ansicht_und_aktivitaet(): void
    {
        $id = $this->seedCampaign();
        $this->plan($id);
        $today = now()->format('Y-m-d H:i:s');
        DB::connection('trading_test')->table('campaign_ledger')->where('campaign_id', $id)
            ->where('dateTime', self::SECOND)->update(['dateTime' => $today]);

        Livewire::actingAs($this->user())->test(GlobalSymbolDetail::class, ['symbol' => self::SYMBOL])
            ->assertSee('open-opening-log', false)
            ->assertSee('btn-success', false);
        Livewire::actingAs($this->user())->test(ActivityLog::class)
            ->assertSee('open-opening-log', false)
            ->assertViewHas('openingMarkers', fn($m) => count($m) === 2);
    }

    // ── Fenster ──────────────────────────────────────────────────────────

    public function test_fenster_zeigt_zahlen_aus_dem_ledger(): void
    {
        $id = $this->seedCampaign();

        $this->modal($id, self::FIRST)
            ->assertSet('show', true)
            ->assertSet('planId', null)
            ->assertSee('120,00 $ Kredit')
            ->assertSee('Prämie Close:')
            ->assertSee('60,00 $')
            ->assertSee('geschlossen')
            ->assertSee('Rationale');
    }

    /**
     * Regressionstest: die erfundene Klasse d-contents ließ den Inhalt nicht scrollen, untere Felder
     * waren unerreichbar. Fenster muss scrollen, sich verschieben und in der Größe ändern lassen.
     */
    public function test_fenster_scrollt_und_laesst_sich_verschieben_und_vergroessern(): void
    {
        $id = $this->seedCampaign();

        $this->modal($id, self::FIRST)
            ->assertSee('overflow-y: auto; min-height: 0;', false)
            ->assertSee('resize: both', false)
            ->assertSee('@pointerdown', false)
            ->assertDontSee('d-contents', false);
    }

    /** Abschluss erst, wenn die Eröffnung geschlossen ist. */
    public function test_abschlussfeld_erst_nach_dem_schliessen(): void
    {
        $id = $this->seedCampaign();

        $this->modal($id, self::FIRST)->assertSee('Abschluss / Lessons Learned');
        $this->modal($id, self::SECOND)->assertDontSee('Abschluss / Lessons Learned');
    }

    /** Je Eröffnung ein Eintrag; gespeichert werden nur Eingaben, keine Ledger-Zahlen. */
    public function test_speichern_legt_je_eroeffnung_einen_eintrag_ohne_zahlen_an(): void
    {
        $id = $this->seedCampaign();

        $this->modal($id, self::FIRST)->set('rationale', 'Text eins')->set('lessons_learned', 'Gelernt')
            ->call('save')->assertHasNoErrors()->assertSet('show', false)->assertDispatched('opening-log-saved');
        $this->modal($id, self::SECOND)->set('rationale', 'Text zwei')->call('save')->assertHasNoErrors();

        $db = DB::connection('trading_test');
        $rows = $db->table('trade_planner')->where('campaign_id', $id)->orderBy('opened_at')->get();
        $this->assertCount(2, $rows);
        $this->assertSame(['Text eins', 'Text zwei'], $rows->pluck('rationale')->all());
        $this->assertSame('Gelernt', $rows[0]->lessons_learned);
        $this->assertSame(['closed', 'open'], $rows->pluck('status')->all());
        $this->assertNull($rows[0]->realized_pnl);
        $this->assertNull($rows[0]->fees_open);
        $this->assertSame(0, $db->table('trade_planner_legs')->whereIn('plan_id', $rows->pluck('id'))->count());
    }

    public function test_vorhandener_eintrag_wird_bearbeitet_statt_doppelt_angelegt(): void
    {
        $id = $this->seedCampaign();
        $planId = $this->plan($id, ['rationale' => 'erster Text', 'realized_pnl' => 57.40]);

        $this->modal($id, self::FIRST)->assertSet('planId', $planId)->assertSet('rationale', 'erster Text')
            ->set('notes', 'neu')->call('save')->assertHasNoErrors();

        $rows = DB::connection('trading_test')->table('trade_planner')->where('campaign_id', $id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('neu', $rows[0]->notes);
        $this->assertEquals(57.40, $rows[0]->realized_pnl, 'alte Zahl bleibt unberührt');
    }

    public function test_eintrag_ohne_opened_at_gehoert_zur_ersten_eroeffnung(): void
    {
        $id = $this->seedCampaign();
        $planId = $this->plan($id, ['opened_at' => null]);

        $this->modal($id, self::FIRST)->assertSet('planId', $planId);
        $this->modal($id, self::SECOND)->assertSet('planId', null);
    }

    public function test_unbekannte_eroeffnung_oeffnet_kein_fenster(): void
    {
        $id = $this->seedCampaign();

        $this->modal($id, '2020-01-01 00:00:00')->assertSet('show', false);
    }

    /** Berechnete Werte als Vorschlag; von Hand eingetragene gelten. */
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
        $this->plan($id, ['price_close' => 77.50]);

        $this->modal($id, self::FIRST)
            ->assertViewHas('details', fn($d) => $d['price_open'] == 101.23 && $d['price_close'] == 98.76
                && $d['ivr_open'] === 50 && $d['ivp_open'] === 50 && $d['deltas'][0] === 32)
            ->assertSee('berechnet')
            ->assertSee('placeholder="101.23"', false)
            ->assertSet('price_close', '77.50');
    }

    public function test_ohne_enrich_tabellen_kein_fehler(): void
    {
        $id = $this->seedCampaign();

        $this->modal($id, self::FIRST)
            ->assertOk()
            ->assertViewHas('details', fn($d) => $d['price_open'] === null && $d['ivr_open'] === null)
            ->assertDontSee('berechnet');
    }

    // ── Kampagnen-Kacheln ────────────────────────────────────────────────

    /** Die Suche zeigt jede Kampagne als eigene Kachel, auch geschlossene bei Filter „Offen“. */
    public function test_suche_zeigt_mehrere_kacheln_ueber_alle_status(): void
    {
        $this->seedCampaign('CLOSED');
        $this->seedCampaign('ACTIVE');

        Livewire::actingAs($this->user())->test(Campaigns::class)
            ->assertSet('filterStatus', 'OPEN')
            ->set('search', self::SYMBOL)
            ->assertViewHas('campaigns', fn($c) => $c->total() === 2)
            ->assertSee('Suche über alle Status und Zeiträume')
            ->assertDontSee('über alle Kampagnen hinweg</h5>', false);
    }

    public function test_kachel_zeigt_abdeckung_und_filter_nicht_dokumentiert(): void
    {
        $partly = $this->seedCampaign();
        $this->plan($partly);
        $complete = $this->seedCampaign();
        $this->plan($complete);
        $this->plan($complete, ['opened_at' => self::SECOND, 'date_open' => '2026-03-12']);

        $test = Livewire::actingAs($this->user())->test(Campaigns::class)
            ->set('search', self::SYMBOL)
            ->assertViewHas('coverage', fn($c) => $c[$partly] === ['documented' => 1, 'total' => 2]
                && $c[$complete] === ['documented' => 2, 'total' => 2])
            ->assertSee('1/2');

        $test->call('toggleUndocumented')
            ->assertViewHas('campaigns', fn($c) => $c->total() === 1 && $c->first()->id === $partly);
    }

    // ── Trade-Kommentare und Menü ────────────────────────────────────────

    /** Liste und Summen aus dem Ledger, nicht aus den alten Spalten; Filter folgt dem Ledger-Status. */
    public function test_trade_kommentare_rechnen_aus_dem_ledger(): void
    {
        $id = $this->seedCampaign();
        $this->plan($id, ['status' => 'open', 'realized_pnl' => 999, 'rationale' => 'Warum eins', 'lessons_learned' => 'Gelernt eins']);
        $this->plan($id, ['opened_at' => self::SECOND, 'status' => 'closed', 'date_open' => '2026-03-12', 'realized_pnl' => 111]);
        DB::connection('trading_test')->table('trade_planner')->insert([
            'environment' => 'TEST', 'underlying' => self::SYMBOL, 'date_open' => '2026-01-01', 'status' => 'closed', 'realized_pnl' => 10,
        ]);

        $test = Livewire::actingAs($this->user())->test(LogList::class);
        $test->assertViewHas('statistics', fn($s) => $s['open_count'] === 1 && $s['closed_count'] === 2 && abs($s['realized_pnl'] - 70) < 0.001);
        $test->assertSee('Trade-Kommentare')->assertSee('60,00 $')->assertDontSee('999,00')
            ->assertSee('Warum eins')->assertSee('Gelernt eins')
            ->assertSee('open-opening-log', false);

        $test->set('filterStatus', 'open')
            ->assertViewHas('logs', fn($logs) => $logs->total() === 1 && $logs->first()->date_open->format('Y-m-d') === '2026-03-12');
    }

    public function test_menue_ohne_log_und_ohne_schnelleingabe(): void
    {
        $html = file_get_contents(resource_path('views/livewire/navigation.blade.php'));

        $this->assertStringNotContainsString('log.quick', $html);
        $this->assertStringNotContainsString('unloggedCount', $html);
        $this->assertStringContainsString('Trade-Kommentare', $html);
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('log.create'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('log.quick'));
    }

    // ── Einträge ohne Eröffnung ──────────────────────────────────────────

    public function test_alter_link_auf_verknuepften_eintrag_fuehrt_zur_kampagne(): void
    {
        $id = $this->seedCampaign();
        $planId = $this->plan($id);

        Livewire::actingAs($this->user())->test(LogForm::class, ['id' => $planId])
            ->assertRedirect(route('campaigns.campaign.detail', $id));
    }

    /** Zeitpunkt nicht im Ledger: Texte bleiben erreichbar, kein Kommentar verschwindet. */
    public function test_eintrag_ohne_passende_eroeffnung_bleibt_sichtbar_und_bearbeitbar(): void
    {
        $id = $this->seedCampaign();
        $planId = $this->plan($id, ['opened_at' => '2020-01-01 00:00:00', 'rationale' => 'alter Text', 'realized_pnl' => 12]);

        Livewire::actingAs($this->user())->test(LogForm::class, ['id' => $planId])
            ->assertNoRedirect()
            ->assertSee('nicht mehr zu finden')
            ->assertSet('rationale', 'alter Text')
            ->assertSet('realized_pnl', '12.00');

        Livewire::actingAs($this->user())->test(CampaignDetail::class, ['campaignId' => $id])
            ->assertSee('ohne passende Eröffnung')
            ->assertSee('alter Text');
    }

    public function test_eintrag_ohne_kampagne_bleibt_bearbeitbar_und_laesst_sich_verknuepfen(): void
    {
        $id = $this->seedCampaign();
        $this->plan($id);
        $db = DB::connection('trading_test');
        $planId = $db->table('trade_planner')->insertGetId([
            'environment' => 'TEST', 'underlying' => self::SYMBOL, 'date_open' => '2026-03-03', 'status' => 'open',
        ]);

        $test = Livewire::actingAs($this->user())->test(LogForm::class, ['id' => $planId])
            ->assertNoRedirect()
            ->assertSee('Leg hinzufügen')
            ->assertSee('Trade schließen')
            ->call('toggleClose')
            ->set('realized_pnl', '42.50')
            ->call('savePlan')
            ->assertHasNoErrors();
        $this->assertEquals(42.50, $db->table('trade_planner')->where('id', $planId)->value('realized_pnl'));

        Livewire::actingAs($this->user())->test(LogForm::class, ['id' => $planId])
            ->call('linkCampaign', $id)
            ->assertRedirect(route('campaigns.campaign.detail', $id));
        $row = $db->table('trade_planner')->where('id', $planId)->first();
        $this->assertSame($id, $row->campaign_id);
        $this->assertSame(self::SECOND, $row->opened_at, 'erste Eröffnung ist schon vergeben');
    }

    public function test_formel_gleich_wie_im_weaver(): void
    {
        $values = array_merge(array_fill(0, 10, 10.0), array_fill(0, 10, 30.0), [20.0]);
        $this->assertSame(['ivr' => 50, 'ivp' => 50], GreeksLookup::rankPercentile($values));
        $this->assertNull(GreeksLookup::rankPercentile(array_fill(0, GreeksLookup::MIN_IV_DAYS - 1, 0.2)));
        $this->assertSame(32, GreeksLookup::deltaForLog('-0.32000'));
    }
}
