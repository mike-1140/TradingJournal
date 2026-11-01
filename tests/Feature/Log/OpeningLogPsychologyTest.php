<?php

namespace Tests\Feature\Log;

use App\Livewire\Log\LogList;
use App\Livewire\Log\OpeningLogModal;
use App\Livewire\Psychology\PsychologyAnalytics;
use App\Livewire\Psychology\PsychologyForm;
use App\Models\User;
use App\Support\OpeningMarkers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Concerns\CreatesPsychologyTables;
use Tests\Concerns\UsesTradingTestConnection;
use Tests\TestCase;

/**
 * Psychologie als Reiter im Log-Fenster. mount() verwirft eine offene Transaktion (siehe
 * UsesTradingTestConnection) → ohne Transaktion angelegt und in tearDown() entfernt.
 */
class OpeningLogPsychologyTest extends TestCase
{
    use RefreshDatabase;
    use UsesTradingTestConnection;
    use CreatesPsychologyTables;

    private const SYMBOL = 'PHPPSY';
    private const OPEN   = '2026-03-02 10:00:00';
    private const SECOND = '2026-03-12 11:00:00';

    private int $campaignId;
    private array $ruleIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setPhpunitPrefix();
        $this->createPsychologyTables();

        $db = DB::connection('trading_test');
        $this->campaignId = $db->table('trading_campaigns')->insertGetId([
            'name' => 'PHPUNIT', 'symbol' => self::SYMBOL, 'status' => 'ACTIVE', 'start_date' => self::OPEN, 'environment' => 'TEST',
        ]);
        foreach ([[100, 'OPT_PUT_SELL', -1, 2.00, self::OPEN, 0], [95, 'OPT_PUT_BUY', 1, 0.80, self::OPEN, 0],
                  [100, 'OPT_PUT_BUY_CLOSE', 1, 1.35, '2026-03-10 11:00:00', 65], [95, 'OPT_PUT_SELL_CLOSE', -1, 0.75, '2026-03-10 11:00:00', -5],
                  [90, 'OPT_PUT_SELL', -1, 2.50, self::SECOND, 0]] as [$strike, $action, $qty, $px, $dt, $pnl]) {
            $db->table('campaign_ledger')->insert([
                'campaign_id' => $this->campaignId, 'assetCategory' => 'OPT', 'environment' => 'TEST', 'multiplier' => 100,
                'commission' => -0.65, 'source_table' => 'TRADES_OPEN', 'source_id' => 0, 'action' => $action, 'strike' => $strike,
                'conid' => 9000 + $strike, 'tradePrice' => $px, 'inventory_delta' => $qty, 'dateTime' => $dt, 'realized_pnl' => $pnl,
                'symbol' => self::SYMBOL . " P$strike",
            ]);
        }
        $rulesetId = $db->table('psychology_rulesets')->insertGetId(['name' => 'Testregeln', 'environment' => 'TEST']);
        foreach (['Regel A', 'Regel B'] as $i => $label) {
            $this->ruleIds[] = $db->table('psychology_rules')->insertGetId(['ruleset_id' => $rulesetId, 'label' => $label, 'sort_order' => $i]);
        }
    }

    protected function tearDown(): void
    {
        $db = DB::connection('trading_test');
        $planIds = $db->table('trade_planner')->where('underlying', self::SYMBOL)->pluck('id');
        $db->table('trade_planner_legs')->whereIn('plan_id', $planIds)->delete();
        $db->table('trade_planner')->whereIn('id', $planIds)->delete();
        $db->table('campaign_ledger')->where('campaign_id', $this->campaignId)->delete();
        $db->table('trading_campaigns')->where('id', $this->campaignId)->delete();
        $this->dropPsychologyTables();
        parent::tearDown();
    }

    private function user(): User
    {
        return User::where('table_prefix', 'phpunit_')->first() ?? User::factory()->create(['table_prefix' => 'phpunit_']);
    }

    private function modal(string $openedAt = self::OPEN)
    {
        return Livewire::actingAs($this->user())->test(OpeningLogModal::class)
            ->dispatch('open-opening-log', campaignId: $this->campaignId, openedAt: $openedAt);
    }

    private function db()
    {
        return DB::connection('trading_test');
    }

    public function test_reiter_ist_immer_da_und_zeigt_die_regeln(): void
    {
        $this->modal()
            ->assertSet('tab', 'trade')
            ->assertSee('Psychologie')
            ->assertSee('Regel A')
            ->assertSee('Vor dem Trade')
            ->call('setTab', 'psychology')
            ->assertSet('tab', 'psychology');
    }

    /** Ohne Angaben kein Eintrag: das vorausgewählte Regelwerk allein ist keine Aussage. */
    public function test_ohne_angaben_entsteht_kein_psychologie_eintrag(): void
    {
        $this->modal()->set('rationale', 'Nur Text')->call('save')->assertHasNoErrors();

        $this->assertSame(0, $this->db()->table('psychology_entries')->count());
        $this->assertSame(1, $this->db()->table('trade_planner')->where('underlying', self::SYMBOL)->count());
    }

    /** Nur eine Emotion: Eintrag ja, aber keine „nicht eingehaltenen“ Regeln. */
    public function test_nur_emotion_speichert_keine_regeln(): void
    {
        $this->modal()->call('setEmotion', 'pre', 3)->call('save')->assertHasNoErrors();

        $entry = $this->db()->table('psychology_entries')->first();
        $this->assertSame(3, (int) $entry->emotion_pre);
        $this->assertNull($entry->ruleset_id);
        $this->assertSame(0, $this->db()->table('psychology_compliance_results')->count());
    }

    public function test_speichern_und_wieder_laden_mit_regeln_und_ledger_pnl(): void
    {
        $this->modal()
            ->call('setEmotion', 'pre', 4)->call('setMotivation', 'FOMO')->call('setMarketContext', 'high_iv')
            ->call('toggleCompliance', $this->ruleIds[0])->call('toggleTemptation', $this->ruleIds[1])
            ->call('save')->assertHasNoErrors();

        $entry = $this->db()->table('psychology_entries')->first();
        $this->assertSame($this->campaignId, (int) $entry->campaign_id);
        $this->assertSame(self::OPEN, $entry->opened_at);
        $this->assertEquals(60.0, $entry->pnl);
        $this->assertSame('2026-03-02', $entry->trade_date);
        $this->assertSame(2, $this->db()->table('psychology_compliance_results')->where('entry_id', $entry->id)->count());

        $this->modal()
            ->assertSet('psychologyId', (int) $entry->id)
            ->assertSet('emotionPre', 4)
            ->assertSet('motivation', 'FOMO')
            ->assertSet('complianceValues', [$this->ruleIds[0] => true, $this->ruleIds[1] => false])
            ->assertSet('temptationValues', [$this->ruleIds[0] => false, $this->ruleIds[1] => true]);
    }

    public function test_alles_abgewaehlt_entfernt_den_eintrag(): void
    {
        $this->modal()->call('setMotivation', 'SYSTEM')->call('toggleCompliance', $this->ruleIds[0])->call('save');
        $this->assertSame(1, $this->db()->table('psychology_entries')->count());

        $this->modal()->call('setMotivation', 'SYSTEM')->call('toggleCompliance', $this->ruleIds[0])->call('save')->assertHasNoErrors();

        $this->assertSame(0, $this->db()->table('psychology_entries')->count());
        $this->assertSame(0, $this->db()->table('psychology_compliance_results')->count());
    }

    /** Fehler bei der Psychologie: auch der Log-Eintrag wird nicht geschrieben. */
    public function test_fehler_schreibt_nichts_halb(): void
    {
        $test = $this->modal()->set('rationale', 'darf nicht bleiben')->call('toggleCompliance', $this->ruleIds[0]);
        $this->db()->statement('DROP TABLE `phpunit_psychology_compliance_results`');

        try {
            $test->call('save');
            $this->fail('Erwartet: Datenbankfehler');
        } catch (\Illuminate\Database\QueryException) {
            // erwartet
        }

        $this->assertSame(0, $this->db()->table('trade_planner')->where('underlying', self::SYMBOL)->count());
        $this->assertSame(0, $this->db()->table('psychology_entries')->count());
    }

    public function test_ohne_modul_kein_fehler_und_hinweis(): void
    {
        $this->dropPsychologyTables();

        $this->modal()
            ->assertSet('psychologyAvailable', false)
            ->assertSee('nicht eingerichtet')
            ->set('rationale', 'geht trotzdem')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('geht trotzdem', $this->db()->table('trade_planner')->where('underlying', self::SYMBOL)->value('rationale'));
    }

    public function test_marker_zeigt_psychologie(): void
    {
        $this->modal()->call('setEmotion', 'post', 2)->call('save');
        $ledger = $this->db()->table('campaign_ledger')->where('campaign_id', $this->campaignId)->orderBy('id')->get();

        $markers = array_values(OpeningMarkers::forEntries($ledger, 'trading_test', 'TEST'));

        $this->assertTrue($markers[0]['psychology']);
        $this->assertFalse($markers[2]['psychology'], 'zweite Eröffnung hat keine');
    }

    /** Auswertung rechnet mit dem Ledger, nicht mit dem gespeicherten Wert; offene Eröffnung ohne P&L. */
    public function test_auswertung_rechnet_mit_dem_ledger_und_zeigt_abdeckung(): void
    {
        $this->user()->update(['filter_preset' => 'all', 'filter_from' => null, 'filter_to' => null]);
        $this->modal()->call('setMotivation', 'SYSTEM')->call('save');
        $this->modal(self::SECOND)->set('rationale', 'nur Log')->call('save');
        $this->db()->table('psychology_entries')->update(['pnl' => 999]);
        $this->db()->table('psychology_entries')->insert(['symbol' => self::SYMBOL, 'trade_date' => '2026-03-20', 'pnl' => 40,
            'motivation' => 'FOMO', 'environment' => 'TEST']);
        $this->db()->table('psychology_entries')->insert(['symbol' => self::SYMBOL, 'trade_date' => '2026-03-20', 'pnl' => 500,
            'motivation' => 'IMPULSE', 'campaign_id' => $this->campaignId, 'opened_at' => self::SECOND, 'environment' => 'TEST']);

        Livewire::actingAs($this->user())->test(PsychologyAnalytics::class)
            ->assertViewHas('stats', fn($s) => $s['total'] === 3 && abs($s['total_pnl'] - 100) < 0.001 && $s['win_rate'] == 67)
            ->assertViewHas('charts', fn($c) => $c['motivation']['data'] === [60.0, 40.0, null, null, null])
            ->assertViewHas('covered', 2)
            ->assertViewHas('documented', 2)
            ->assertSee('Psychologie zu 2 von 2 dokumentierten Eröffnungen');
    }

    public function test_kommentare_zeigen_psychologie_und_loeschen_gemeinsam(): void
    {
        $this->modal()->call('setMotivation', 'REVENGE')->call('setEmotion', 'pre', 2)
            ->call('toggleCompliance', $this->ruleIds[0])->call('save');
        $planId = $this->db()->table('trade_planner')->where('underlying', self::SYMBOL)->value('id');

        Livewire::actingAs($this->user())->test(LogList::class)
            ->assertSee('REVENGE')
            ->assertSee('Regeln 1/2')
            ->call('deleteEntry', $planId);

        $this->assertSame(0, $this->db()->table('psychology_entries')->count());
        $this->assertSame(0, $this->db()->table('psychology_compliance_results')->count());
    }

    /** Nicht zugeordnete Einträge bleiben sichtbar und bearbeitbar, zugeordnete führen zur Kampagne. */
    public function test_zuordnung_steuert_das_alte_formular(): void
    {
        $free = $this->db()->table('psychology_entries')->insertGetId(['symbol' => self::SYMBOL, 'trade_date' => '2026-03-20',
            'motivation' => 'FOMO', 'environment' => 'TEST', 'lesson' => 'alte Lektion']);
        $linked = $this->db()->table('psychology_entries')->insertGetId(['symbol' => self::SYMBOL, 'trade_date' => '2026-03-20',
            'campaign_id' => $this->campaignId, 'opened_at' => self::OPEN, 'environment' => 'TEST']);

        Livewire::actingAs($this->user())->test(LogList::class)->assertSee('ohne passenden Kommentar');
        Livewire::actingAs($this->user())->test(PsychologyForm::class, ['id' => $free])
            ->assertNoRedirect()->assertSet('lesson', 'alte Lektion');
        Livewire::actingAs($this->user())->test(PsychologyForm::class, ['id' => $linked])
            ->assertRedirect(route('campaigns.campaign.detail', $this->campaignId));
    }

    public function test_alte_routen_und_menue(): void
    {
        $html = file_get_contents(resource_path('views/livewire/navigation.blade.php'));

        $this->assertStringNotContainsString("route('psychology.create')", $html);
        $this->assertStringNotContainsString("route('psychology.list')", $html);
        $this->assertStringContainsString("route('psychology.analytics')", $html);
        $this->assertStringContainsString("route('psychology.rulesets')", $html);
        $this->assertFalse(Route::has('psychology.create'));
    }
}
