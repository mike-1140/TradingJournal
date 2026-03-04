<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTradingDatabase;

class TradeFx extends BaseTradingModel
{
    use UsesTradingDatabase;
    // protected $connection = 'trading';
    protected $table = 'trades_fx';
    public $timestamps = false;

    protected $fillable = [
        'assetCategory',
        'tradeID',
        'symbol',
        'baseCurrency',
        'quoteCurrency',
        'baseAmount',
        'quoteAmount',
        'fxRate',
        'commission',
        'dateTime',
        'environment',
        'checksum',
    ];

    protected $casts = [
        'baseAmount' => 'float',
        'quoteAmount' => 'float',
        'fxRate' => 'float',
        'commission' => 'float',
        'tradeDate' => 'date',
    ];

    public function scopeOnEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Berechnet das Volumen in USD (vereinfacht)
     */
    public function getVolumeAttribute()
    {
        // Wenn Quote-Currency USD ist, nehme quoteAmount
        if ($this->quoteCurrency === 'USD') {
            return abs($this->quoteAmount);
        }
        // Sonst approximiere mit baseAmount * fxRate
        return abs($this->baseAmount * $this->fxRate);
    }

    /**
     * Formatiertes Symbol (z.B. EUR/USD)
     */
    public function getFormattedSymbolAttribute()
    {
        return "{$this->baseCurrency}/{$this->quoteCurrency}";
    }
}
