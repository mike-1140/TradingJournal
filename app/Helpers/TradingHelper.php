<?php
// app/Helpers/TradingHelper.php
namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class TradingHelper
{
    /**
     * Gibt die DB-Connection für ein Environment zurück
     */
    public static function getConnection(?string $environment = null): string
    {
        // Fallback auf Default wenn leer oder null
        if (empty($environment)) {
            $environment = config('trading.default_environment', 'PAPER');
        }

        $environments = config('trading.environments', []);

        // Prüfen ob Environment existiert
        if (!isset($environments[$environment])) {
//            Log::channel('debug')->warning("Unknown trading environment:", [
//                'Environments' => $environments,
//                'Environment' => $environment,
//                //'Environments[Environment]' => $environments[$environment],
//            ]);
            $environment = config('trading.default_environment', 'TEST');
        }

        return $environments[$environment]['connection'] ?? 'trading_test';
    }

    /**
     * Gibt alle verfügbaren Environments zurück
     */
    public static function getEnvironments(): array
    {
        $envs = array_keys(config('trading.environments', []));

//        flow_log('TradingHelper - Enter: getEnvironments', [
//            'environments' => $envs,
//        ]);

        return $envs;
    }

    /**
     * Gibt das Label für ein Environment zurück
     */
    public static function getEnvironmentLabel(string $environment): string
    {
//        flow_log('TradingHelper - Enter: getEnvironmentLabel', [
//            'Environment' => $environment,
//        ]);
        return config("trading.environments.{$environment}.label", $environment);
    }

    /**
     * Prüft ob Environment gültig ist
     */
    public static function isValidEnvironment(?string $environment): bool
    {
//        flow_log('TradingHelper - Enter: isValidEnvironment', [
//            'Environment' => $environment,
//        ]);
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
//        flow_log('TradingHelper - Enter: normalizeEnvironment', [
//            'Environment' => $environment,
//        ]);
        if (self::isValidEnvironment($environment)) {
            return $environment;
        }

        return config('trading.default_environment', 'PAPER');
    }
}
