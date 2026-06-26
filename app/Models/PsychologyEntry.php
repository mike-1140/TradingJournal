<?php

namespace App\Models;

use App\Traits\UsesTradingDatabase;

class PsychologyEntry extends BaseTradingModel
{
    use UsesTradingDatabase;

    protected $appends = [];
    protected $table = 'psychology_entries';
    public $timestamps = true;

    protected $fillable = [
        'trade_id',
        'opt_strategyID',
        'trade_type',
        'ruleset_id',
        'symbol',
        'trade_date',
        'pnl',
        'emotion_pre',
        'emotion_post',
        'motivation',
        'market_context',
        'lesson',
        'environment',
    ];

    protected $casts = [
        'trade_date'   => 'date',
        'pnl'          => 'decimal:2',
        'emotion_pre'  => 'integer',
        'emotion_post' => 'integer',
    ];

    public function ruleset()
    {
        return $this->belongsTo(PsychologyRuleset::class, 'ruleset_id');
    }

    public function complianceResults()
    {
        return $this->hasMany(PsychologyComplianceResult::class, 'entry_id');
    }

    public function getComplianceScoreAttribute(): int
    {
        return $this->complianceResults->where('complied', true)->count();
    }

    public function getMaxComplianceScoreAttribute(): int
    {
        return $this->complianceResults->count();
    }

    public function getTemptationCountAttribute(): int
    {
        return $this->complianceResults->where('tempted', true)->count();
    }
}
