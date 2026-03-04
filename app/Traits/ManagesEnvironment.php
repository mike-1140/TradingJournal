<?php
// app/Traits/ManagesEnvironment.php

namespace App\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Helpers\TradingHelper;
use Illuminate\Support\Facades\Log;

trait ManagesEnvironment
{
    public $selectedEnvironment;
    public $environments;

    public function mountManagesEnvironment()
    {
        // flow_trace();

        // 1. Environments laden
        $this->environments = TradingHelper::getEnvironments();

        // flow_log('TradingHelper::getEnvironments()', [
        //     'returned Environment' => $this->environments,
        // ]);

        // 2. Environment aus Session holen oder Default
        $this->selectedEnvironment = $this->getOrInitializeEnvironment();
        // flow_log('ManagesEnvironment - $this->getOrInitializeEnvironment()', [
        //     'returned Environment' => $this->selectedEnvironment,
        // ]);
        // 3. Database Context setzen (nur wenn User eingeloggt)
        $this->applyUserPrefix();
    }

    /**
     * Holt Environment aus Session oder initialisiert es
     */
    protected function getOrInitializeEnvironment(): string
    {
        // flow_trace();

        $sessionEnv = session('trading_environment');

        // flow_log('trading_environment:', [
        //     'from' => $sessionEnv,
        // ]);
//
        //////////////////////
//        $default = config('trading.default_environment', 'TEST');
//        session(['trading_environment' => $default]);
//        $sessionEnv = session('trading_environment');
        //////////////////

        // Wenn nicht gesetzt oder ungültig
        if (!TradingHelper::isValidEnvironment($sessionEnv)) {
            $default = config('trading.default_environment', 'TEST');
            session(['trading_environment' => $default]);

            flow_log('Livewire: Trading environment initialized', [
                'from' => $sessionEnv,
                'to' => $default,
            ]);

            return $default;
        }

        return $sessionEnv;
    }

    public function updatedSelectedEnvironment($value)
    {
        // flow_trace();
        $this->setEnvironment($value);
    }

    public function setEnvironment($environment)
    {
        // flow_trace();
        // Validierung & Normalisierung
        if (!TradingHelper::isValidEnvironment($environment)) {
            // flow_log('Invalid environment selected', [
            //     'environment' => $environment,
            //     'component' => static::class,
            // ]);
            return;
        }

        $this->selectedEnvironment = $environment;
        session(['trading_environment' => $environment]);
        $this->applyUserPrefix();

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Wendet User-Präfix an (nur für eingeloggte User)
     */
    protected function applyUserPrefix()
    {
        // flow_trace();
        // Kein User eingeloggt → nichts zu tun
        if (!auth()->check()) {
            // flow_log('applyUserPrefix skipped: No authenticated user');
            return;
        }

        $user = auth()->user();
        $prefix = $user->table_prefix ?? '';
//        flow_log('User has table_prefix', [
//            'user_id' => $user->id,
//            'prefix' => $prefix,
//        ]);
        // $prefix = 'mike_';

        // Warning wenn User keinen Präfix hat
        if (empty($prefix)) {
           //  flow_log('User has no table_prefix in Livewire', [
           //      'user_id' => $user->id,
           //      'environment' => $this->selectedEnvironment,
           //      'connection' => $connection,
           // ]);

            // Optional: Auto-generieren
            // $prefix = $this->generatePrefixForUser($user);
        }

        $connection = TradingHelper::getConnection($this->selectedEnvironment);

        // Präfix setzen (auch wenn leer)
        Config::set("database.connections.{$connection}.prefix", $prefix);
        DB::purge($connection);

        flow_log('Livewire: Database context applied', [
            'prefix' => $prefix ?: '(none)',
            'environment' => $this->selectedEnvironment,
            'connection' => $connection,
        ]);
    }

    /**
     * Optional: Auto-generiere Präfix
     */
    protected function generatePrefixForUser($user): string
    {
        $cleanName = preg_replace('/[^a-z0-9]/', '', strtolower($user->name));
        $prefix = 'user_' . $cleanName . '_';

        $user->update(['table_prefix' => $prefix]);

        flow_log('Livewire: Auto-generated table_prefix', [
            'user_id' => $user->id,
            'prefix' => $prefix,
        ]);

        return $prefix;
    }

    public function getTradingConnectionName()
    {
        // flow_trace();
        return TradingHelper::getConnection($this->selectedEnvironment);
    }
}
