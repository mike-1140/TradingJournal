<?php
// app/Http/Middleware/SetUserDatabaseContext.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Helpers\TradingHelper;

class SetUserDatabaseContext
{
    public function handle($request, Closure $next)
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $prefix = auth()->user()->table_prefix ?? '';
        // $prefix = 'mike_'
        $environment = session(
            'trading_environment',
            config('trading.default_environment')
        );

        // ZENTRAL: Nur noch EINE Stelle!
        $connection = TradingHelper::getConnection($environment);

        Config::set("database.connections.{$connection}.prefix", $prefix);
        DB::purge($connection);

        return $next($request);
    }
}
