<?php

namespace App\Models;

use App\Traits\UsesTradingDatabase;

class PsychologyRuleset extends BaseTradingModel
{
    use UsesTradingDatabase;

    protected $appends = [];
    protected $table = 'psychology_rulesets';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'strategy_type',
        'description',
        'is_active',
        'sort_order',
        'environment',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function rules()
    {
        return $this->hasMany(PsychologyRule::class, 'ruleset_id');
    }

    public function activeRules()
    {
        return $this->hasMany(PsychologyRule::class, 'ruleset_id')->orderBy('sort_order');
    }
}
