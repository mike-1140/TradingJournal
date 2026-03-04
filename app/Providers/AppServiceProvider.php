<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Services\YahooFinanceService;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
//        $this->app->singleton(YahooFinanceService::class, function ($app) {
//            return new YahooFinanceService();
//        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Erzwingt Bootstrap 5 Styles für alle Paginatoren
        Paginator::useBootstrapFive();
        // Setzt die Sprache für PHP-Funktionen global auf Deutsch
        setlocale(LC_TIME, 'de_DE.utf8', 'de_DE', 'deu_deu');

        // Optional: Carbon explizit auf Deutsch zwingen
        \Carbon\Carbon::setLocale(config('app.locale'));
    }

}
