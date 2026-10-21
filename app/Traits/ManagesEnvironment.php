<?php
// app/Traits/ManagesEnvironment.php

namespace App\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Helpers\TradingHelper;

trait ManagesEnvironment
{
    public $selectedEnvironment;
    public $environments;

    public function mountManagesEnvironment()
    {
        // 1. Environments laden (nur die, in denen der User Tabellen hat; TEST immer dabei)
        $this->environments = TradingHelper::getInstalledEnvironments();

        // 2. Environment aus Session holen oder Default
        $this->selectedEnvironment = $this->getOrInitializeEnvironment();

        // 3. Database Context setzen (nur wenn User eingeloggt)
        $this->applyUserPrefix();
    }

    /**
     * Holt Environment aus Session oder initialisiert es
     */
    protected function getOrInitializeEnvironment(): string
    {
        $sessionEnv = session('trading_environment');

        // Wenn nicht gesetzt oder ungültig: Default-Environment, falls der User dort Tabellen
        // hat, sonst das erste Environment mit Tabellen (siehe TradingHelper::getStartEnvironment)
        if (!TradingHelper::isValidEnvironment($sessionEnv)) {
            $default = TradingHelper::getStartEnvironment();
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
        $this->setEnvironment($value);
    }

    public function setEnvironment($environment)
    {
        // Validierung & Normalisierung
        if (!TradingHelper::isValidEnvironment($environment)) {
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
        // Kein User eingeloggt → nichts zu tun
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        $prefix = $user->table_prefix ?? '';

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
        return TradingHelper::getConnection($this->selectedEnvironment);
    }
}
