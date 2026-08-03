<?php
// app/Helpers/TradingHelper.php
namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TradingHelper
{
    /**
     * Gibt die DB-Connection für ein Environment zurück
     */
    public static function getConnection(?string $environment = null): string
    {
        // Fallback auf Default wenn leer oder null
        if (empty($environment)) {
            $environment = config('trading.default_environment', 'TEST');
        }

        $environments = config('trading.environments', []);

        // Prüfen ob Environment existiert
        if (!isset($environments[$environment])) {
            $environment = config('trading.default_environment', 'TEST');
        }

        return $environments[$environment]['connection'] ?? 'trading_test';
    }

    /**
     * Gibt nur die Environments zurück, die der übergebene User tatsächlich nutzt.
     * TEST ist immer dabei, da es nie fehlen soll (Default-Fallback).
     * Es gibt keine explizite User-Environment-Zuordnung — "genutzt" wird daran
     * erkannt, ob in der jeweiligen Environment-DB eine Tabelle mit dem
     * table_prefix des Users existiert (z.B. karola_trades_close in ibkr_money).
     * Ergebnis wird pro Prefix gecacht, da der Schema-Check einen echten
     * DB-Roundtrip pro Environment auslöst.
     */
    public static function getInstalledEnvironments(?string $tablePrefix = null): array
    {
        if ($tablePrefix === null) {
            $tablePrefix = auth()->user()?->table_prefix ?? '';
        }

        $cacheKey = 'trading.installed_environments.' . ($tablePrefix !== '' ? $tablePrefix : '_none');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($tablePrefix) {
            $environments = config('trading.environments', []);
            $installed = [];

            foreach ($environments as $key => $cfg) {
                if ($key === 'TEST') {
                    $installed[] = $key;
                    continue;
                }

                $connection = $cfg['connection'] ?? null;
                if (!$connection || $tablePrefix === '') {
                    continue;
                }

                try {
                    if (Schema::connection($connection)->hasTable($tablePrefix . 'trades_close')) {
                        $installed[] = $key;
                    }
                } catch (\Throwable $e) {
                    // Environment (noch) nicht installiert oder DB nicht erreichbar
                } finally {
                    DB::purge($connection);
                }
            }

            return $installed;
        });
    }

    /**
     * Gibt das Label für ein Environment zurück
     */
    public static function getEnvironmentLabel(string $environment): string
    {
        return config("trading.environments.{$environment}.label", $environment);
    }

    /**
     * Prüft ob Environment gültig ist
     */
    public static function isValidEnvironment(?string $environment): bool
    {
        if (empty($environment)) {
            return false;
        }

        return array_key_exists($environment, config('trading.environments', []));
    }

    /**
     * Normalisiert Environment (gibt valides Environment oder Default zurück)
     */
    public static function normalizeEnvironment(?string $environment): string
    {
        if (self::isValidEnvironment($environment)) {
            return $environment;
        }

        return config('trading.default_environment', 'TEST');
    }
}
