<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->web(append: [
            \App\Http\Middleware\SetDefaultUser::class,
            \App\Http\Middleware\SetUserDatabaseContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->renderable(function (
            \App\Exceptions\DemoModeWriteBlockedException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->hasHeader('X-Livewire')) {
                // Bewusst 403 statt null/500: der bestehende 500-Toast-Hook zeigt einen
                // festen "wahrscheinlich ein Datenbankproblem"-Text, der hier irrefuehrend
                // waere. Eigener 403-Hook in app.js zeigt stattdessen die echte Meldung.
                // Kein session()->flash() hier — der Toast deckt den Livewire-Fall ab;
                // ein Flash wuerde sonst beim naechsten normalen Seitenaufruf als
                // ueberfluessige zweite Banner-Meldung auftauchen.
                return response()->json(['message' => $e->getMessage()], 403);
            }

            session()->flash('demo_blocked', $e->getMessage());

            return back()->withInput();
        });

        $exceptions->renderable(function (
            \Illuminate\Database\QueryException $e,
            \Illuminate\Http\Request $request
        ) {
            // MySQL-Fehlercode aus der PDO-Exception
            $code = (int) ($e->getPrevious()?->getCode() ?? $e->getCode());
            $raw  = $e->getMessage();

            $message = match (true) {
                $code === 1146 || str_contains($raw, '42S02')
                    => 'Datenbanktabelle nicht gefunden — bitte <code>reset_IBKR_DB.py</code> ausführen.',
                $code === 1045
                    => 'Datenbankzugang verweigert — .env-Datei prüfen (DB_USERNAME / DB_PASSWORD).',
                $code === 2002
                    => 'Keine Verbindung zur Datenbank — läuft MySQL auf Port 3306?',
                $code === 1049
                    => 'Datenbank nicht vorhanden — .env-Datei prüfen (DB_DATABASE).',
                $code === 1054
                    => 'Unbekannte Spalte — Schema stimmt nicht mit Model überein. reset_IBKR_DB.py ausführen.',
                default
                    => 'Datenbankfehler (Code ' . $code . '): ' . ($e->getPrevious()?->getMessage() ?? $raw),
            };

            // Für alle Requests in Session schreiben (sichtbar beim nächsten Seitenaufruf)
            session()->flash('db_error', $message);

            if ($request->hasHeader('X-Livewire')) {
                // Livewire-AJAX: null = Laravel/Livewire übernimmt das 500-Handling.
                // JS-Hook in app.blade.php zeigt sofort einen Toast.
                return null;
            }

            // Normaler Request: zurück mit Fehlermeldung
            return back()->withInput();
        });

    })->create();
