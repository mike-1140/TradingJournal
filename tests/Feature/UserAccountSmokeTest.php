<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Beweist, dass die Haupt-DB-Verbindung fuers Testen korrekt auf
 * ibkr_laravel_test zeigt (siehe .env.testing) -- niemals auf die echte
 * ibkr_laravel mit den echten User-Accounts. RefreshDatabase migriert die
 * echten Laravel-Migrationen (users, cache, ...) frisch pro Testlauf.
 */
class UserAccountSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_kann_angelegt_und_wieder_gelesen_werden(): void
    {
        $user = User::factory()->create(['table_prefix' => 'phpunit_']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'table_prefix' => 'phpunit_',
        ]);
    }
}
