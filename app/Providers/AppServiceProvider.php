<?php

namespace App\Providers;

use App\Exceptions\DemoModeWriteBlockedException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->guardAgainstTestingOnRealDatabase();

        Paginator::useBootstrapFive();
        setlocale(LC_TIME, 'de_DE.utf8', 'de_DE', 'deu_deu');
        \Carbon\Carbon::setLocale(config('app.locale'));

        // Nur lokal: in Produktion soll Nutzerwechsel (inkl. Sichtbarkeit anderer User)
        // nicht möglich sein, da der Rückweg zum Admin sonst eine echte Anmeldung
        // braucht statt wie lokal automatisch per SetDefaultUser zu passieren.
        Gate::define('switch-user', fn($user) => app()->environment('local') && (bool) $user->is_admin);

        $this->registerDemoModeGuard();
    }

    /**
     * Blockiert jeden schreibenden Eloquent-Zugriff im Demo-Modus, unabhaengig vom
     * konkreten Model — Livewire wickelt alle Schreibaktionen ueber einen einzigen
     * POST-Endpunkt ab, eine Routen-Middleware koennte "harmloses" Livewire-POST
     * (Pagination, Filter) nicht von echten Writes unterscheiden. Laravels eigene
     * Session-/Cache-Tabellen laufen ueber den Query Builder, nicht ueber Eloquent,
     * und bleiben davon unberuehrt. Ausnahmen: Login mit "Angemeldet bleiben" speichert
     * nur den remember_token — ohne die faellt der Login im Demo-Modus aus. Ebenso die
     * Zeitraum-Filter-Felder (filter_preset/filter_from/filter_to) — reine
     * Anzeige-Praeferenz des eingeloggten Demo-Users, keine Trading-Datenmutation;
     * ohne die Ausnahme koennte ein Demo-Besucher den Zeitraum nie wechseln.
     *
     * Der config()-Check sitzt bewusst INNERHALB der Closures (nicht als fruehes
     * Return beim Registrieren) — Listener werden immer registriert, demo_mode wird
     * bei jedem Schreibzugriff neu ausgewertet. Sonst wuerde ein zur Boot-Zeit noch
     * falscher Wert (z.B. in Tests per config()-Override gesetzt) nie mehr greifen.
     *
     * Wichtig: `Model::saving(...)` (die statische Sugar-Methode) registriert NUR
     * fuer die Klasse, auf der sie aufgerufen wird — bei zentraler Registrierung
     * ausserhalb eines Models bindet sie an den Event-Namen der Basisklasse
     * `Illuminate\Database\Eloquent\Model` selbst, der nie feuert (jede Subklasse
     * feuert ihr eigenes `eloquent.saving: App\Models\...`). Deshalb hier bewusst
     * `Event::listen('eloquent.saving: *', ...)` mit Wildcard.
     */
    private function registerDemoModeGuard(): void
    {
        Event::listen('eloquent.saving: *', function (string $eventName, array $data) {
            if (!$this->demoModeActive()) {
                return;
            }
            $model = $data[0];
            $allowedUserFields = ['remember_token', 'filter_preset', 'filter_from', 'filter_to'];
            if ($model instanceof User
                && array_diff(array_keys($model->getDirty()), $allowedUserFields) === []) {
                return;
            }
            throw new DemoModeWriteBlockedException();
        });

        Event::listen('eloquent.deleting: *', function (string $eventName, array $data) {
            if ($this->demoModeActive()) {
                throw new DemoModeWriteBlockedException();
            }
        });
    }

    /**
     * Sicherheitsnetz gegen einen Vorfall vom 2026-09-18: ein veralteter
     * bootstrap/cache/config.php fror app.env UND die "mysql"-Connection auf die
     * damals aktive .env (nicht .env.testing) ein. RefreshDatabase migrierte dadurch
     * unbemerkt gegen die ECHTE lokale ibkr_laravel-Datenbank statt gegen
     * ibkr_laravel_test und leerte die users-Tabelle.
     *
     * Bewusst NICHT auf app()->environment()/runningUnitTests() gestuetzt — das sind
     * genau die Werte, die ein solcher Cache einfriert, der Guard wuerde im exakten
     * Gefahrenfall also selbst nie auslösen. PHPUNIT_COMPOSER_INSTALL wird von PHPUnit
     * selbst gesetzt, bevor irgendein Laravel-Code (und damit auch ein evtl. Cache)
     * ueberhaupt geladen wird -- unabhaengig von jeder Konfiguration.
     *
     * Muss waehrend boot() laufen (Teil von refreshApplication() in
     * Tests\TestCase::setUp()), NICHT erst in einem test-eigenen setUp() -- Traits wie
     * RefreshDatabase feuern noch innerhalb desselben parent::setUp()-Aufrufs, ein
     * Check danach kaeme zu spaet (so beim ersten Versuch dieses Guards passiert).
     */
    private function guardAgainstTestingOnRealDatabase(): void
    {
        if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
            return;
        }

        $database = config('database.connections.mysql.database');

        if (!str_ends_with((string) $database, '_test')) {
            throw new \RuntimeException(
                "SICHERHEITSABBRUCH: PHPUnit laeuft, aber 'mysql'-Connection zeigt auf ".
                "'{$database}', nicht auf eine *_test-Datenbank. RefreshDatabase wuerde ".
                "diese DB leeren/neu migrieren. Meist verursacht durch einen veralteten ".
                "bootstrap/cache/config.php -- 'php artisan config:clear' ausfuehren."
            );
        }
    }

    private function demoModeActive(): bool
    {
        // runningInConsole() ist waehrend PHPUnit-Laeufen ebenfalls true (Tests laufen
        // ueber "php artisan test") — ohne die runningUnitTests()-Ausnahme wuerde der
        // Guard in Tests nie greifen. In echten Artisan-Kommandos (Wartung, Deploy)
        // bleibt er dagegen bewusst inaktiv.
        $exemptConsole = $this->app->runningInConsole() && !$this->app->runningUnitTests();

        return config('app.demo_mode') && !$exemptConsole;
    }

}
