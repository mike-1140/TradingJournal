<?php
// app/Helpers/TradingHelper.php
namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
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
     * Environments für den Environment-Dropdown: alle, in denen der User Tabellen hat,
     * plus TEST — TEST steht immer im Dropdown, auch ohne Tabellen (sicherer
     * Ausgangspunkt; fehlen die Tabellen, zeigt die App beim Auswählen eine Fehlermeldung).
     */
    public static function getInstalledEnvironments(?string $tablePrefix = null): array
    {
        return self::withTestEntry(self::getUsableEnvironments($tablePrefix));
    }

    /**
     * Start-Environment einer neuen Session: das konfigurierte Default-Environment
     * (TRADING_DEFAULT_ENV, sonst TEST), falls der User dort Tabellen hat — sonst das
     * erste Environment mit Tabellen (Reihenfolge wie in config/trading.php). Verhindert,
     * dass eine Installation ohne TEST-Tabellen direkt beim ersten Aufruf auf einen
     * Datenbankfehler läuft.
     */
    public static function getStartEnvironment(?string $tablePrefix = null): string
    {
        return self::pickStartEnvironment(
            config('trading.default_environment', 'TEST'),
            self::getUsableEnvironments($tablePrefix)
        );
    }

    /**
     * Auswahl-Logik für getStartEnvironment(), ohne DB-Zugriff (testbar).
     */
    public static function pickStartEnvironment(string $default, array $usable): string
    {
        if (in_array($default, $usable, true) || $usable === []) {
            return $default;
        }

        return $usable[0];
    }

    /**
     * Ergänzt TEST in einer Environment-Liste, Reihenfolge wie in config/trading.php.
     */
    public static function withTestEntry(array $environments): array
    {
        return array_values(array_filter(
            array_keys(config('trading.environments', [])),
            fn ($key) => $key === 'TEST' || in_array($key, $environments, true)
        ));
    }

    /**
     * Gibt nur die Environments zurück, in denen der übergebene User tatsächlich Tabellen hat
     * (TEST eingeschlossen, aber nur mit Tabellen).
     * Es gibt keine explizite User-Environment-Zuordnung — "genutzt" wird daran
     * erkannt, ob in der jeweiligen Environment-DB eine Tabelle mit dem
     * table_prefix des Users existiert (z.B. tom_trades_close in ibkr_money).
     * Ergebnis wird pro Prefix gecacht, da der Schema-Check einen echten
     * DB-Roundtrip pro Environment auslöst.
     */
    public static function getUsableEnvironments(?string $tablePrefix = null): array
    {
        if ($tablePrefix === null) {
            $tablePrefix = auth()->user()?->table_prefix ?? '';
        }

        $cacheKey = 'trading.usable_environments.' . ($tablePrefix !== '' ? $tablePrefix : '_none');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($tablePrefix) {
            $environments = config('trading.environments', []);
            $installed = [];

            foreach ($environments as $key => $cfg) {
                $connection = $cfg['connection'] ?? null;
                if (!$connection || $tablePrefix === '') {
                    continue;
                }

                // Der Tabellenname wird unten manuell mit $tablePrefix zusammengesetzt —
                // die Connection selbst darf hier keinen eigenen Prefix mitbringen, sonst
                // wird doppelt prefixt (z.B. "tom_anna_trades_close", falls die
                // Connection von einem vorherigen Aufruf noch einen anderen User-Prefix
                // gesetzt hat) und die Prüfung schlägt faelschlich fehl.
                $originalPrefix = config("database.connections.{$connection}.prefix");
                Config::set("database.connections.{$connection}.prefix", '');
                DB::purge($connection);

                try {
                    if (Schema::connection($connection)->hasTable($tablePrefix . 'trades_close')) {
                        $installed[] = $key;
                    }
                } catch (\Throwable $e) {
                    // Environment (noch) nicht installiert oder DB nicht erreichbar
                } finally {
                    Config::set("database.connections.{$connection}.prefix", $originalPrefix);
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
