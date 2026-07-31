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
        'campaign_id',
        'underlying',
        'date_open',
        'price_open',
        'fees_open',
        'ivp_open',
        'ivr_open',
        'strategy',
        'exit_plan',
        'mindset_entry',
        'energy_level',
        'rationale',
        'confidence',
        'market_regime',
        'pre_mortem',
        'status',
        'date_close',
        'price_close',
        'ivp_close',
        'ivr_close',
        'premium_close',
        'fees_close',
        'realized_pnl',
        'actual_dte',
        'mindset_exit',
        'plan_adherence',
        'lessons_learned',
        'decision_quality',
        'notes',
    ];

    protected $casts = [
        'date_open'     => 'date',
        'date_close'    => 'date',
        'price_open'    => 'decimal:2',
        'fees_open'     => 'decimal:2',
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

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function scopeOnEnvironment($query, $environment)
    {
        return $query->where('environment', strtoupper($environment));
    }
}
