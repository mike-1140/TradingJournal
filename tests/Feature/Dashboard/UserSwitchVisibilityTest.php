<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\UsesTradingTestConnection;
use Tests\TestCase;

/**
 * Regressionstest: Der Nutzerwechsel (UserSwitchService, "switch-user"-Gate) darf
 * ausserhalb der lokalen Entwicklungsumgebung weder sichtbar noch ausfuehrbar sein --
 * lokal wird der Rueckweg zum Admin durch SetDefaultUser automatisch abgefangen, in
 * Produktion braeuchte man dafuer eine echte Anmeldung, und die Liste aller Nutzernamen
 * war vorher unabhaengig vom Admin-Status ueber die oeffentliche Dashboard-Property
 * $users sichtbar.
 */
class UserSwitchVisibilityTest extends TestCase
{
    use RefreshDatabase;
    use UsesTradingTestConnection;

    protected function setUp(): void
    {
        parent::setUp();
        // Praefix-Setup schon hier, Transaktion erst NACH dem ersten Livewire::test()-
        // Aufruf pro Testfall (siehe Docblock von UsesTradingTestConnection).
        $this->setPhpunitPrefix();
    }

    protected function tearDown(): void
    {
        $this->rollbackTradingTransaction();
        parent::tearDown();
    }

    public function test_userwechsel_dropdown_ausserhalb_local_env_nicht_sichtbar_auch_fuer_admin(): void
    {
        // APP_ENV in .env.testing ist "testing", nicht "local" -- Standardfall.
        $admin = User::factory()->create(['table_prefix' => 'phpunit_', 'is_admin' => true]);
        $other = User::factory()->create(['table_prefix' => 'phpunit2_', 'name' => 'Anderer Nutzer']);

        $component = Livewire::actingAs($admin)->test(Dashboard::class);
        $this->beginTradingTransaction();

        $component->assertDontSee('Anderer Nutzer');
    }

    public function test_userwechsel_dropdown_lokal_fuer_admin_sichtbar(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $admin = User::factory()->create(['table_prefix' => 'phpunit_', 'is_admin' => true]);
        $other = User::factory()->create(['table_prefix' => 'phpunit2_', 'name' => 'Anderer Nutzer']);

        $component = Livewire::actingAs($admin)->test(Dashboard::class);
        $this->beginTradingTransaction();

        $component->assertSee('Anderer Nutzer');
    }

    public function test_userwechsel_dropdown_lokal_fuer_nicht_admin_nicht_sichtbar(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $user = User::factory()->create(['table_prefix' => 'phpunit_', 'is_admin' => false]);
        $other = User::factory()->create(['table_prefix' => 'phpunit2_', 'name' => 'Anderer Nutzer']);

        $component = Livewire::actingAs($user)->test(Dashboard::class);
        $this->beginTradingTransaction();

        $component->assertDontSee('Anderer Nutzer');
    }

    public function test_switchuser_aktion_ausserhalb_local_bleibt_blockiert_auch_fuer_admin(): void
    {
        $admin = User::factory()->create(['table_prefix' => 'phpunit_', 'is_admin' => true]);
        $other = User::factory()->create(['table_prefix' => 'phpunit2_']);

        $component = Livewire::actingAs($admin)->test(Dashboard::class);
        $this->beginTradingTransaction();

        $component->call('switchUser', $other->id);

        $this->assertAuthenticatedAs($admin);
    }

    public function test_switchuser_aktion_funktioniert_lokal_fuer_admin(): void
    {
        // Tripwire-Gegenprobe: das Feature soll im vorgesehenen Kontext (lokal + Admin)
        // weiterhin funktionieren, nicht komplett abgeschaltet sein.
        $this->app->detectEnvironment(fn () => 'local');

        $admin = User::factory()->create(['table_prefix' => 'phpunit_', 'is_admin' => true]);
        $other = User::factory()->create(['table_prefix' => 'phpunit2_']);

        $component = Livewire::actingAs($admin)->test(Dashboard::class);
        $this->beginTradingTransaction();

        $component->call('switchUser', $other->id);

        $this->assertAuthenticatedAs($other);
    }
}
