<?php

namespace App\Livewire\Psychology;

use App\Models\PsychologyEntry;
use App\Models\PsychologyComplianceResult;
use App\Models\PsychologyRule;
use App\Traits\ManagesEnvironment;
use App\Traits\FilterableByTime;
use Livewire\Component;

class PsychologyAnalytics extends Component
{
    use ManagesEnvironment, FilterableByTime;

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount(): void
    {
        $this->mountManagesEnvironment();
        $this->mountFilterableByTime();
    }

    public function render()
    {
        $env        = $this->selectedEnvironment;
        $connection = PsychologyEntry::getConnectionForEnvironment($env);

        $base = PsychologyEntry::on($connection)->where('environment', $env);
        $this->applyTimeFilter($base, 'trade_date');

        $total    = (clone $base)->count();
        $totalPnl = (clone $base)->sum('pnl');
        $wins     = (clone $base)->where('pnl', '>', 0)->count();
        $withTempt = (clone $base)->whereHas('complianceResults', fn ($q) => $q->where('tempted', 1))->count();

        // Ø Compliance: complied / Anzahl Regeln pro Eintrag
        $complianceAvg = null;
        if ($total > 0) {
            $entries = (clone $base)->with('complianceResults')->get();
            $scores  = $entries->filter(fn ($e) => $e->complianceResults->count() > 0)
                               ->map(fn ($e) => $e->complianceResults->avg('complied'));
            $complianceAvg = $scores->count() > 0 ? round($scores->avg() * 100) : null;
        }

        // 1. Cost of Emotion — Ø P&L pro Motivation
        $motivations = ['SYSTEM', 'FOMO', 'REVENGE', 'BOREDOM', 'IMPULSE'];
        $motivationLabels = ['System', 'FOMO', 'Revenge', 'Langeweile', 'Impuls'];
        $motivationData   = [];
        foreach ($motivations as $m) {
            $avg = (clone $base)->where('motivation', $m)->whereNotNull('pnl')->avg('pnl');
            $motivationData[] = $avg !== null ? round((float) $avg, 2) : null;
        }

        // 2. Emotional Sweet Spot — Ø P&L pro emotion_pre (1-5)
        $emotionLabels = ['1', '2', '3', '4', '5'];
        $emotionData   = [];
        for ($i = 1; $i <= 5; $i++) {
            $avg = (clone $base)->where('emotion_pre', $i)->whereNotNull('pnl')->avg('pnl');
            $emotionData[] = $avg !== null ? round((float) $avg, 2) : null;
        }

        // 3. Compliance-Performance — Ø P&L pro Compliance-Bucket (0-25%, 25-50%, 50-75%, 75-100%)
        $compBucketLabels = ['0–25%', '25–50%', '50–75%', '75–100%'];
        $compBucketData   = [null, null, null, null];
        if ($total > 0) {
            $entries = (clone $base)->whereNotNull('pnl')->with('complianceResults')->get();
            $buckets = [[], [], [], []];
            foreach ($entries as $e) {
                $cr = $e->complianceResults;
                if ($cr->count() === 0) continue;
                $pct   = $cr->avg('complied') * 100;
                $idx   = min(3, (int) floor($pct / 25));
                $buckets[$idx][] = (float) $e->pnl;
            }
            foreach ($buckets as $i => $vals) {
                $compBucketData[$i] = count($vals) > 0 ? round(array_sum($vals) / count($vals), 2) : null;
            }
        }

        // 4. Regelversuchungen — Häufigkeit pro Regel (horizontal bar)
        $temptRuleLabels = [];
        $temptRuleData   = [];
        $ruleConn = PsychologyRule::getConnectionForEnvironment($env);
        $rules = PsychologyRule::on($ruleConn)
            ->whereHas('ruleset', fn ($q) => $q->where('environment', $env)->where('is_active', 1))
            ->orderBy('sort_order')
            ->get();

        foreach ($rules as $rule) {
            $temptCount = PsychologyComplianceResult::on($connection)
                ->where('rule_id', $rule->id)
                ->where('tempted', 1)
                ->count();
            if ($temptCount > 0) {
                $temptRuleLabels[] = $rule->label;
                $temptRuleData[]   = $temptCount;
            }
        }

        $stats = [
            'total'          => $total,
            'total_pnl'      => $totalPnl,
            'win_rate'       => $total > 0 ? round($wins / $total * 100) : null,
            'compliance_avg' => $complianceAvg,
            'tempt_rate'     => $total > 0 ? round($withTempt / $total * 100) : null,
        ];

        $charts = [
            'motivation' => ['labels' => $motivationLabels, 'data' => $motivationData],
            'emotion'    => ['labels' => $emotionLabels,    'data' => $emotionData],
            'compliance' => ['labels' => $compBucketLabels, 'data' => $compBucketData],
            'temptation' => ['labels' => $temptRuleLabels,  'data' => $temptRuleData],
        ];

        $this->dispatch('psych-analytics-data',
            motivationLabels:  $charts['motivation']['labels'],
            motivationData:    $charts['motivation']['data'],
            emotionLabels:     $charts['emotion']['labels'],
            emotionData:       $charts['emotion']['data'],
            complianceLabels:  $charts['compliance']['labels'],
            complianceData:    $charts['compliance']['data'],
            temptationLabels:  $charts['temptation']['labels'],
            temptationData:    $charts['temptation']['data'],
        );

        return view('livewire.psychology.psychology-analytics', [
            'stats'  => $stats,
            'charts' => $charts,
        ]);
    }
}
