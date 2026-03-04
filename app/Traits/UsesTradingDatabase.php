<?php

namespace App\Traits;

trait UsesTradingDatabase
{
    /**
     * Get the database connection based on environment
     */
    public static function getConnectionForEnvironment($environment)
    {
        // flow_log('UsesTradingDatabase');
        return match(strtoupper($environment)) {
            'MONEY' => 'trading_money',
            // 'PAPER' => 'trading_paper',
            'TEST' => 'trading_test',
            'AUTOM_MONEY' => 'trading_autom_money',
            'AUTOM_PAPER' => 'trading_autom_paper',
            default => 'trading_paper',
        };
    }

    /**
     * Set connection based on environment
     */
    public static function forEnvironment($environment)
    {
        $connection = static::getConnectionForEnvironment($environment);
        return (new static)->setConnection($connection);
    }

    /**
     * Query mit spezifischer Connection
     */
    public static function onEnvironment($environment)
    {
        $connection = static::getConnectionForEnvironment($environment);
        return static::on($connection);
    }
}
