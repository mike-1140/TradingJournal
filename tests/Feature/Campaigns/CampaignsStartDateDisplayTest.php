<?php

namespace Tests\Feature\Campaigns;

use App\Livewire\Campaigns\Campaigns;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\UsesTradingTestConnection;
use Tests\TestCase;

/**
 * Regressionstest fuer einen bereits gefixten Bug (CLAUDE.md "Bekannte Bugs"):
 * campaigns-index.blade.php nutzte $this->startDateProperty statt der echten
 * Computed Property $this->startDate -- das Zeitfenster wurde nie angezeigt,
 * es stand immer "Alle historischen Daten", egal welcher Filter aktiv war.
 */
class CampaignsStartDateDisplayTest extends TestCase
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
        $this->tearDownTradingTestConnection();
        parent::tearDown();
    }

    public function test_zeigt_das_tatsaechliche_startdatum_aus_dem_ytd_filter(): void
    {
        $user = User::factory()->create([
            'table_prefix' => 'phpunit_',
            'filter_preset' => 'ytd',
        ]);

        Livewire::actingAs($user)
            ->test(Campaigns::class)
            ->assertSee('Daten seit dem ' . now()->startOfYear()->format('d.m.Y'))
            ->assertDontSee('Alle historischen Daten');
    }

    public function test_zeigt_alle_historischen_daten_ohne_aktiven_filter(): void
    {
        $user = User::factory()->create([
            'table_prefix' => 'phpunit_',
            'filter_preset' => null,
            'filter_from' => null,
            'filter_to' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Campaigns::class)
            ->assertSee('Alle historischen Daten');
    }
}
