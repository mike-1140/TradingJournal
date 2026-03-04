<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends BaseTradingModel
{
    // Die Tabelle wird ohne Präfix definiert, da Laravel diesen
    // über den UserDatabaseService (Config::set('database.connections...prefix')) erhält.
    protected $table = 'trading_campaigns';

    protected $fillable = [
        'id',
        'name',
        'symbol',
        'strategy',
        'assetCategory',
        'status',       // 'ACTIVE', 'PENDING', 'CLOSED'
        'start_date',
        'end_date',
        'closed_at',
        'environment',
    ];


    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Sammelt alle Ledger-Einträge dieser Kampagne.
     */
    public function ledgerEntries()
    {
        return $this->hasMany(CampaignLedger::class, 'campaign_id');
    }

    /**
     * Scope für die aktuelle Umgebung (Paper/Money/Test)
     */
    public function scopeOnEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    public function getLedgerTimeline()
    {
        return $this->ledgerEntries()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($entry) {
                // Optional: Hier könnten wir Details aus den Quelltabellen (trades_open/closed)
                // via DB-Query dazuladen, falls nötig.
                return $entry;
            });
    }

    /**
     * Berechnet die Performance-Kennzahlen basierend auf dem Ledger
     */
    public function getMetrics()
    {
        return [
            'netcash' => $this->ledgerEntries()->sum('netcash'),
            'shares'   => $this->ledgerEntries()->where('assetCategory', 'STK')->sum('inventory_delta'),
            'options'  => $this->ledgerEntries()->where('assetCategory', 'OPT')->sum('inventory_delta'),
        ];
    }
}
