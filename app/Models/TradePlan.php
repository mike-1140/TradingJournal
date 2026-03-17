<?php

namespace App\Models;

use App\Traits\UsesTradingDatabase;

class TradePlan extends BaseTradingModel
{
    use UsesTradingDatabase;

    protected $table = 'trade_planner';
    public $timestamps = true;

    protected $fillable = [
        'environment',
        'underlying',
        'date_open',
        'price_open',
        'ivp_open',
        'ivr_open',
        'strategy',
        'exit_plan',
        'status',
        'date_close',
        'price_close',
        'ivp_close',
        'ivr_close',
        'premium_close',
        'fees_close',
        'realized_pnl',
        'actual_dte',
        'notes',
    ];

    protected $casts = [
        'date_open'     => 'date',
        'date_close'    => 'date',
        'price_open'    => 'decimal:2',
        'price_close'   => 'decimal:2',
        'premium_close' => 'decimal:2',
        'fees_close'    => 'decimal:2',
        'realized_pnl'  => 'decimal:2',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function legs()
    {
        return $this->hasMany(TradePlanLeg::class, 'plan_id')->orderBy('sort_order');
    }

    public function scopeOnEnvironment($query, $environment)
    {
        return $query->where('environment', strtoupper($environment));
    }
}
