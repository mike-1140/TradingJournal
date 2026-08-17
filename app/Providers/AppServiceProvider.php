<?php

namespace App\Providers;

use App\Exceptions\DemoModeWriteBlockedException;
use App\Models\User;
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
        Paginator::useBootstrapFive();
        setlocale(LC_TIME, 'de_DE.utf8', 'de_DE', 'deu_deu');
        \Carbon\Carbon::setLocale(config('app.locale'));

        Gate::define('switch-user', fn($user) => (bool) $user->is_admin);

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
