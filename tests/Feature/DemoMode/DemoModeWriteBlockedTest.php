<?php

namespace Tests\Feature\DemoMode;

use App\Exceptions\DemoModeWriteBlockedException;
use App\Models\TradePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesTradingTestConnection;
use Tests\TestCase;

/**
 * Regressionstest fuer die Demo-Modus-Schreibsperre
 * (AppServiceProvider::registerDemoModeGuard). Deckt sowohl das Blockieren
 * (demo_mode=true) als auch die Tripwire-Gegenprobe (demo_mode=false -> normales
 * Schreiben funktioniert weiterhin) sowie die remember_token-Ausnahme fuer den
 * Login mit "Angemeldet bleiben" ab.
 */
class DemoModeWriteBlockedTest extends TestCase
{
    use RefreshDatabase;
    use UsesTradingTestConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTradingTestConnection();
    }

    protected function tearDown(): void
    {
        config(['app.demo_mode' => false]);
        $this->tearDownTradingTestConnection();
        parent::tearDown();
    }

    private function makeTradePlan(): TradePlan
    {
        return TradePlan::create([
            'environment' => 'TEST',
            'underlying' => 'FOO',
            'date_open' => '2026-01-01',
            'status' => 'open',
        ]);
    }

    public function test_update_an_trading_model_wird_im_demo_modus_blockiert(): void
    {
        $plan = $this->makeTradePlan();
        config(['app.demo_mode' => true]);

        $this->expectException(DemoModeWriteBlockedException::class);
        $plan->update(['status' => 'closed']);
    }

    public function test_delete_wird_im_demo_modus_blockiert(): void
    {
        $plan = $this->makeTradePlan();
        config(['app.demo_mode' => true]);

        $this->expectException(DemoModeWriteBlockedException::class);
        $plan->delete();
    }

    public function test_create_wird_im_demo_modus_blockiert(): void
    {
        config(['app.demo_mode' => true]);

        $this->expectException(DemoModeWriteBlockedException::class);
        $this->makeTradePlan();
    }

    public function test_user_update_wird_im_demo_modus_blockiert(): void
    {
        $user = User::factory()->create(['table_prefix' => 'phpunit_', 'filter_preset' => null]);
        config(['app.demo_mode' => true]);

        $this->expectException(DemoModeWriteBlockedException::class);
        $user->update(['filter_preset' => 'ytd']);
    }

    public function test_tripwire_schreiben_bleibt_ohne_demo_modus_weiterhin_moeglich(): void
    {
        // Gegenprobe: der Guard darf ausserhalb des Demo-Modus nicht aktiv sein.
        config(['app.demo_mode' => false]);
        $plan = $this->makeTradePlan();
        $plan->update(['status' => 'closed']);

        $this->assertDatabaseHas('trade_planner', [
            'id' => $plan->id,
            'status' => 'closed',
        ], 'trading_test');
    }

    public function test_remember_token_update_bleibt_im_demo_modus_erlaubt(): void
    {
        // Ohne diese Ausnahme wuerde "Angemeldet bleiben" beim Login im Demo-Modus fehlschlagen.
        $user = User::factory()->create(['table_prefix' => 'phpunit_']);
        config(['app.demo_mode' => true]);

        $user->setRememberToken('a-fake-token');
        $user->save();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'remember_token' => 'a-fake-token',
        ]);
    }

    public function test_login_mit_angemeldet_bleiben_funktioniert_im_demo_modus(): void
    {
        $user = User::factory()->create([
            'table_prefix' => 'phpunit_',
            'password' => bcrypt('secret1234'),
        ]);
        config(['app.demo_mode' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret1234',
            'remember' => 'on',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
