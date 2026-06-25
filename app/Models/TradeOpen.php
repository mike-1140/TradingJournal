<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTradingDatabase;

class TradeOpen extends BaseTradingModel
{
    use UsesTradingDatabase;
    // protected $connection = 'trading';
    protected $table = 'trades_open';

    public $timestamps = false;

    protected $fillable = [
        'id' ,
        'symID' ,
        'assetCategory' ,
        'subCategory' ,
        'symbol' ,
        'description' ,
        'underlyingSymbol' ,
        'conid' ,
        'underlyingConid' ,
        'quantity' ,
        'expiry' ,
        'buySell' ,
        'putCall' ,
        'strike' ,
        'multiplier' ,
        'dateTime' ,
        'tradePrice' ,
        'netCash' ,
        'proceeds' ,
        'ibCommission' ,
        'strategy' ,
        'status' ,
        'tradeID' ,
        'ibOrderID' ,
        'linkID' ,
        'opt_strategy' ,
        'opt_strategyName' ,
        'opt_strategyID' ,
        'environment' ,
        'checksum' ,
        'processed',
        'currency',
        'fxRateToBase',
    ];

    protected $casts = [
        'dateTime' => 'datetime',
        'expiry' => 'date',
        'tradePrice' => 'decimal:2',
        'netCash' => 'decimal:2',
        'ibCommission' => 'decimal:2',
        'strike' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'quantity' => 'decimal:2',
        'fxRateToBase' => 'decimal:6',
        'processed' => 'integer',
        'is_strategy' => 'integer',
    ];

    public function symbol()
    {
        return $this->belongsTo(Symbol::class, 'symID');
    }

    public function scopeByEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    public function scopeOptions($query)
    {
        return $query->whereIn('assetCategory', ['OPT', 'FOP']);
    }

    public function scopeStocks($query)
    {
        return $query->where('assetCategory', 'STK');
    }

    // Berechne aktuellen Wert (würde Live-Preis benötigen)
    public function getCurrentValue($currentPrice = null)
    {
        if ($currentPrice) {
            return $this->quantity * $currentPrice;
        }
        return null;
    }

    // Berechne unrealisierten Gewinn/Verlust
    public function getUnrealizedPL($currentPrice = null)
    {
        if ($currentPrice) {
            $currentValue = $this->quantity * $currentPrice;
            return $currentValue + $this->netCash; // netCash ist negativ bei Kauf
        }
        return null;
    }

    /**
     * Extrahiert Strike aus dem Symbol wenn strike Spalte nicht existiert
     */
    public function getStrikeAttribute()
    {
        // Wenn strike Spalte existiert, gib sie zurück
        if (array_key_exists('strike', $this->attributes) && $this->attributes['strike']) {
            return $this->attributes['strike'];
        }

        // Ansonsten extrahiere Strike aus Symbol
        if (preg_match('/[CP](\d{8})/', $this->symbol, $matches)) {
            return floatval($matches[1]) / 1000;
        }

        return null;
    }

    public function hasStrike()
    {
        return $this->strike !== null;
    }

}
