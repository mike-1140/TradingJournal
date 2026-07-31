<?php

namespace App\Providers;

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
    }

}
