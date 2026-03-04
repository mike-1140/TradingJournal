<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTradingDatabase;

class Trade extends BaseTradingModel
{
    use UsesTradingDatabase;
    //protected $connection = 'trading';
    protected $table = 'trades_close';

    protected $fillable = [
        'symbol',
        'entry_date',
        'exit_date',
        'entry_price',
        'exit_price',
        'quantity',
        'status', // open/closed
        'profit_loss',
        // Weitere Felder
    ];

    protected $casts = [
        'entry_date' => 'datetime',
        'exit_date' => 'datetime',
        'entry_price' => 'decimal:2',
        'exit_price' => 'decimal:2',
        'profit_loss' => 'decimal:2',
    ];

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}
