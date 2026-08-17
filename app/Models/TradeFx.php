<?php

namespace App\Models;

use App\Traits\UsesTradingDatabase;

class TradeFx extends BaseTradingModel
{
    use UsesTradingDatabase;

    protected $table = 'trades_fx';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'assetCategory',
        'tradeID',
        'ibOrderID',
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
        'dateTime' => 'datetime',
        'baseAmount' => 'decimal:4',
        'quoteAmount' => 'decimal:4',
        'fxRate' => 'decimal:4',
        'commission' => 'decimal:4',
    ];

    public function scopeByEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }
}
