<?php

namespace App\Models;

use App\Traits\UsesTradingDatabase;
use Illuminate\Support\Facades\Schema;

class PsychologyEntry extends BaseTradingModel
{
    use UsesTradingDatabase;

    protected $appends = [];
    protected $table = 'psychology_entries';
    public $timestamps = true;

    protected static array $tableExistsCache = [];

    /**
     * Nicht jeder User/jede Environment hat das Psychology-Modul eingerichtet
     * (z.B. karola_/MONEY) — Trade-/Spread-Detailseiten fragen psychology_entries
     * aber unabhängig davon ab. Ohne diesen Check wirft eine fehlende Tabelle
     * MySQL-Fehler 1146, den der globale Handler als harten "Datenbanktabelle
     * nicht gefunden"-Fehler anzeigt statt einfach "kein Eintrag vorhanden".
     */
    public static function tableExistsFor(string $connection): bool
    {
        return static::$tableExistsCache[$connection] ??= Schema::connection($connection)->hasTable((new static())->getTable());
    }

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
