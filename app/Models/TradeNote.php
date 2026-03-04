<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTradingDatabase;

class TradeNote extends BaseTradingModel
{
    use UsesTradingDatabase;

    protected $table = 'trade_notes';
    public $timestamps = true;

    protected $fillable = [
        'trade_id',
        'opt_strategyID',
        'trade_type',
        'note_type',
        'title',
        'content',
        'environment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope für Environment-basierte Queries
     */
    public function scopeOnEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Scope für Trade-spezifische Notes
     */
    public function scopeForTrade($query, $tradeId, $tradeType)
    {
        return $query->where('trade_id', $tradeId)
            ->where('trade_type', $tradeType);
    }

    /**
     * Scope für Spread-spezifische Notes
     */
    public function scopeForSpread($query, $opt_strategyID)
    {
        return $query->where('opt_strategyID', $opt_strategyID)
            ->where('trade_type', 'spread');
    }

    /**
     * Note Type Labels
     */
    public function getNoteTypeLabel()
    {
        return match($this->note_type) {
            'entry_plan' => '📋 Entry Plan',
            'exit_reason' => '🚪 Exit Reason',
            'lesson_learned' => '🎓 Lesson Learned',
            'general' => '📝 General Note',
            default => '📝 Note',
        };
    }

    /**
     * Note Type Badge Class
     */
    public function getNoteTypeBadgeClass()
    {
        return match($this->note_type) {
            'entry_plan' => 'bg-info',
            'exit_reason' => 'bg-warning',
            'lesson_learned' => 'bg-success',
            'general' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }
}
