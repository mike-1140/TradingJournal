<?php

namespace App\Models;

use App\Traits\UsesTradingDatabase;

class PsychologyComplianceResult extends BaseTradingModel
{
    use UsesTradingDatabase;

    protected $appends = [];
    protected $table = 'psychology_compliance_results';
    public $timestamps = false;

    protected $fillable = [
        'entry_id',
        'rule_id',
        'complied',
        'tempted',
    ];

    protected $casts = [
        'complied' => 'boolean',
        'tempted'  => 'boolean',
    ];

    public function entry()
    {
        return $this->belongsTo(PsychologyEntry::class, 'entry_id');
    }

    public function rule()
    {
        return $this->belongsTo(PsychologyRule::class, 'rule_id');
    }
}
