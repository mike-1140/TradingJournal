<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTradingDatabase;

class Symbol extends BaseTradingModel
{
    use UsesTradingDatabase;
    // protected $connection = 'trading';
    protected $table = 'symbols';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'assetCategory',
        'symbol',
        'underlyingSymbol',
        'isin',
        'conid',
        'underlyingConid',
        'environment',
    ];

    // Beziehungen zu den Trade-Tabellen
    public function rawTrades()
    {
        return $this->hasMany(TradeRaw::class, 'symID');
    }

    public function openTrades()
    {
        return $this->hasMany(TradeOpen::class, 'symID');
    }

    public function closedTrades()
    {
        return $this->hasMany(TradeClose::class, 'symID');
    }
}
