<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTradingDatabase;

class TradeTagPivot extends BaseTradingModel
{
    use UsesTradingDatabase;

    protected $table = 'trade_tag_pivot';
    public $timestamps = false;

    protected $fillable = [
        'tag_id',
        'trade_id',
        'trade_type',
        'environment',
        'tagged_at',
    ];

    protected $casts = [
        'tagged_at' => 'datetime',
    ];

    /**
     * Beziehung zum Tag (aus zentraler mysql DB)
     */
    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }

    /**
     * Hole Connection basierend auf Environment
     */
//      soll nur an einer Stelle sein
//    public static function getConnectionForEnvironment($environment)
//    {
//        return match($environment) {
//            'MONEY' => 'trading_money',
//            'PAPER' => 'trading_paper',
//            'TEST' => 'trading_test',
//            default => 'trading_paper',
//        };
//    }
}
