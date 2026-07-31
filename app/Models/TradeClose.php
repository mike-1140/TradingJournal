<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTradingDatabase;
use App\Traits\FormatsIbkrStrategyLabel;

class TradeClose extends BaseTradingModel
{
    use UsesTradingDatabase, FormatsIbkrStrategyLabel;
    // protected $connection = 'trading';
    protected $table = 'trades_close';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'symID',
        'conid',
        'assetCategory',
        'subCategory',
        'symbol',
        'description',
        'underlyingSymbol',
        'underlyingConid',
        'multiplier',
        'strike',
        'expiry',
        'dateTimeOpen',
        'dateTimeClose',
        'putCall',
        'buySellOpen',
        'buySellClose',
        'quantity',
        'tradePriceOpen',
        'tradePriceClose',
        'netCashOpen',
        'netCashClose',
        'prceedsOpen',
        'prceedsClose',
        'daysHeld',
        'totalSeconds',
        'durationStr',
        'profit',
        'ROI',
        'tradeIDOpen',
        'tradeIDClose',
        'ibOrderIDOpen',
        'ibOrderIDClose',
        'ibCommission',
        'closedBy',
        'strategy',
        'status',
        'opt_strategy',
        'opt_strategyName',
        'opt_strategyID',
        'linkID',
        'environment',
        'fxRateToBase_open',
        'fxRateToBase_close',
        'netCashOpen_EUR',
        'netCashClose_EUR',
        'ibCommission_EUR',
        'profit_EUR',
    ];

    protected $casts = [
        'dateTimeOpen' => 'datetime',
        'dateTimeClose' => 'datetime',
        'expiry' => 'date',
        'tradePriceOpen' => 'decimal:2',
        'tradePriceClose' => 'decimal:2',
        'netCashOpen' => 'decimal:2',
        'netCashClose' => 'decimal:2',
        'profit' => 'decimal:2',
        'ROI' => 'decimal:2',
        'ibCommission' => 'decimal:2',
        'quantity' => 'decimal:2',
        'fxRateToBase_open' => 'decimal:6',
        'fxRateToBase_close' => 'decimal:6',
        'netCashOpen_EUR' => 'decimal:4',
        'netCashClose_EUR' => 'decimal:4',
        'ibCommission_EUR' => 'decimal:4',
        'profit_EUR' => 'decimal:4',
        'daysHeld' => 'integer',
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

    public function scopeWinners($query)
    {
        return $query->where('profit', '>', 0);
    }

    public function scopeLosers($query)
    {
        return $query->where('profit', '<', 0);
    }

    public function scopeByClosedBy($query, $closedBy)
    {
        return $query->where('closedBy', $closedBy);
    }

    // Helper: Ist Trade profitabel?
    public function isProfitable()
    {
        return $this->profit > 0;
    }

    // Helper: Trade-Dauer in Tagen
    public function getTradeDuration()
    {
        return $this->daysHeld ?? 0;
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
        // Format: SPY 240119C00475000
        // Strike ist die Zahl nach C oder P, geteilt durch 1000
        if (preg_match('/[CP](\d{8})/', $this->symbol, $matches)) {
            return floatval($matches[1]) / 1000;
        }

        return null;
    }

    /**
     * Überprüft ob ein Strike existiert oder extrahiert werden kann
     */
    public function hasStrike()
    {
        return $this->strike !== null;
    }

}

