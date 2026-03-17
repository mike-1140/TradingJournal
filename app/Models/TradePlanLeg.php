<?php

namespace App\Models;

use App\Traits\UsesTradingDatabase;

class TradePlanLeg extends BaseTradingModel
{
    use UsesTradingDatabase;

    protected $table = 'trade_planner_legs';
    public $timestamps = false;

    protected $fillable = [
        'plan_id',
        'leg_type',
        'dte',
        'strike',
        'delta',
        'premium',
        'fees',
        'max_risk',
        'sort_order',
    ];

    protected $casts = [
        'strike'  => 'decimal:2',
        'delta'   => 'decimal:2',
        'premium' => 'decimal:2',
        'fees'    => 'decimal:2',
        'max_risk'=> 'decimal:2',
    ];

    public function plan()
    {
        return $this->belongsTo(TradePlan::class, 'plan_id');
    }
}
