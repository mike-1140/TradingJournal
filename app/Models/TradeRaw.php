<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTradingDatabase;

class TradeRaw extends BaseTradingModel
{
    use UsesTradingDatabase;
    // protected $connection = 'trading';
    protected $table = 'trades_raw';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'symID',
        'assetCategory',
        'subCategory',
        'symbol',
        'description',
        'underlyingSymbol',
        'conid',
        'underlyingConid',
        'multiplier',
        'strike',
        'expiry',
        'putCall',
        'buySell',
        'dateTime',
        'quantity',
        'tradePrice',
        'netCash',
        'proceeds',
        'ibCommission',
        'strategy',
        'status',
        'closedBy',
        'tradeID',
        'ibOrderID',
        'source',
        'is_split',
        'split_type',
        'actionID',
        'linkID',
        'opt_strategy',
        'opt_strategyName',
        'opt_strategyID',
        'environment',
        'checksum',
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
        'is_split' => 'integer',
        'is_strategy' => 'integer',
    ];

    public function symbol()
    {
        return $this->belongsTo(Symbol::class, 'symID');
    }

    // Scopes für Filterung
    public function scopeByEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    public function scopeProcessed($query)
    {
        return $query->where('processed', 1);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', 0);
    }
}
