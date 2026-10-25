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
     * Kampagnen-Arten fuer Filter und Anzeige. Bestimmt aus ALLEN Buchungen der Kampagne --
     * nicht aus `strategy`, das Weaver nur nach dem ersten Trade setzt (ein Bull Put Spread
     * steht dort z. B. als HEDGE/SPECULATION, weil das gekaufte Bein zuerst gebucht wurde).
     */
    public const TYPES = [
        'STOCKS'  => ['label' => 'Aktien',   'color' => 'secondary', 'icon' => 'graph-up'],
        'WHEEL'   => ['label' => 'Wheel',    'color' => 'info',      'icon' => 'arrow-repeat'],
        'OPTIONS' => ['label' => 'Optionen', 'color' => 'primary',   'icon' => 'lightning'],
        'SPREADS' => ['label' => 'Spreads',  'color' => 'warning',   'icon' => 'layers'],
    ];

    /**
     * Haengt has_stock / has_option / has_spread an jede Kampagne (EXISTS-Abfragen,
     * Tabellen-Praefix greift automatisch). Grundlage fuer campaign_type.
     */
    public function scopeWithTypeFlags($query, string $environment)
    {
        return $query->withExists([
            'ledgerEntries as has_stock'  => fn($q) => self::stockEntries($q, $environment),
            'ledgerEntries as has_option' => fn($q) => self::optionEntries($q, $environment),
            'ledgerEntries as has_spread' => fn($q) => self::spreadEntries($q, $environment),
        ]);
    }

    /**
     * Filtert auf eine Art aus TYPES:
     *   STOCKS  = nur Aktien
     *   WHEEL   = Aktien und Optionen (mit oder ohne Zuteilung)
     *   SPREADS = nur Optionen, mindestens ein erkannter Spread
     *   OPTIONS = nur Optionen, kein Spread
     */
    public function scopeOfType($query, string $type, string $environment)
    {
        $stock  = fn($q) => self::stockEntries($q, $environment);
        $option = fn($q) => self::optionEntries($q, $environment);
        $spread = fn($q) => self::spreadEntries($q, $environment);

        return match ($type) {
            'STOCKS'  => $query->whereHas('ledgerEntries', $stock)->whereDoesntHave('ledgerEntries', $option),
            'WHEEL'   => $query->whereHas('ledgerEntries', $stock)->whereHas('ledgerEntries', $option),
            'SPREADS' => $query->whereDoesntHave('ledgerEntries', $stock)->whereHas('ledgerEntries', $spread),
            'OPTIONS' => $query->whereDoesntHave('ledgerEntries', $stock)->whereHas('ledgerEntries', $option)
                               ->whereDoesntHave('ledgerEntries', $spread),
            default   => $query,
        };
    }

    /**
     * Art der Kampagne (Schluessel aus TYPES); null, wenn withTypeFlags() nicht geladen
     * wurde oder die Kampagne keine Buchungen hat.
     */
    public function getCampaignTypeAttribute(): ?string
    {
        if (!array_key_exists('has_stock', $this->attributes)) {
            return null;
        }
        if ($this->has_stock) {
            return $this->has_option ? 'WHEEL' : 'STOCKS';
        }
        if ($this->has_spread) {
            return 'SPREADS';
        }
        return $this->has_option ? 'OPTIONS' : null;
    }

    private static function stockEntries($query, string $environment)
    {
        return $query->where('environment', $environment)->where('assetCategory', 'STK');
    }

    private static function optionEntries($query, string $environment)
    {
        return $query->where('environment', $environment)->where('assetCategory', 'OPT');
    }

    private static function spreadEntries($query, string $environment)
    {
        return self::optionEntries($query, $environment)
            ->where('source_table', 'TRADES_OPEN')
            ->whereHas('openTrade', fn($o) => $o->whereNotNull('opt_strategyID')->where('opt_strategyID', '<>', ''));
    }

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
