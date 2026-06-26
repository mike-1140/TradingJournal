<?php

namespace App\Models;

use App\Traits\UsesTradingDatabase;

class PsychologyRule extends BaseTradingModel
{
    use UsesTradingDatabase;

    protected $appends = [];
    protected $table = 'psychology_rules';
    public $timestamps = false;

    protected $fillable = [
        'ruleset_id',
        'rule_key',
        'label',
        'description',
        'error_type',
        'sort_order',
    ];

    public function ruleset()
    {
        return $this->belongsTo(PsychologyRuleset::class, 'ruleset_id');
    }
}
