<?php
// app/Services/UserDatabaseService.php
namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class UserDatabaseService
{
    public function setUserPrefix(string $prefix, string $environment = null)
    {
        $environment = $environment ?? session('environment', 'TEST');
        $connection = $this->getConnectionName($environment);

        // Dynamischen Präfix setzen
        Config::set("database.connections.{$connection}.prefix", $prefix);

        // Connection neu aufbauen
        DB::purge($connection);
        DB::reconnect($connection);
    }

    private function getConnectionName(string $environment): string
    {
        flow_log('UsesDatabaseService');
        return match($environment) {
            'MONEY' => 'trading_money',
            // 'PAPER' => 'trading_paper',
            'TEST' => 'trading_test',
            'AUTOM_MONEY' => 'trading_autom_money',
            'AUTOM_PAPER' => 'trading_autom_paper',
            default => 'trading_paper',
        };
    }

    public function getCurrentPrefix(): string
    {
        $user = auth()->user();
        return $user ? $user->table_prefix : '';
    }
}
