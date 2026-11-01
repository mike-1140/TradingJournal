<?php

namespace App\Livewire\Psychology;

use App\Models\CampaignLedger;
use App\Models\PsychologyEntry;
use App\Models\PsychologyComplianceResult;
use App\Models\PsychologyRule;
use App\Models\TradePlan;
use App\Support\CampaignOpenings;
use App\Traits\ManagesEnvironment;
use App\Traits\FilterableByTime;
use App\Traits\RequiresPsychologyModule;
use Livewire\Component;

class PsychologyAnalytics extends Component
{
    use ManagesEnvironment, FilterableByTime, RequiresPsychologyModule;

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount(): void
    {
        $this->mountManagesEnvironment();
    }

    public function render()
    {
        if ($this->psychologyModuleMissing()) {
            return $this->moduleNotInstalledView();
        }

        $env        = $this->selectedEnvironment;
        $connection = PsychologyEntry::getConnectionForEnvironment($env);

        $base = PsychologyEntry::on($connection)->where('environment', $env);
        $this->applyTimeFilter($base, 'trade_date');

        $all = $this->withLedgerPnl((clone $base)->with('complianceResults')->get(), $connection);

        $total    = $all->count();
        $totalPnl = $all->sum(fn($e) => (float) $e->pnl);
        $wins     = $all->filter(fn($e) => (float) $e->pnl > 0)->count();
        $withTempt = (clone $base)->whereHas('complianceResults', fn ($q) => $q->where('tempted', 1))->count();

        // Ø Compliance: complied / Anzahl Regeln pro Eintrag
        $scores = $all->filter(fn($e) => $e->complianceResults->count() > 0)
                      ->map(fn($e) => $e->complianceResults->avg('complied'));
        $complianceAvg = $scores->count() > 0 ? round($scores->avg() * 100) : null;

        $withPnl = $all->filter(fn($e) => $e->pnl !== null);
        $avgPnl  = fn($entries) => $entries->isNotEmpty() ? round($entries->avg(fn($e) => (float) $e->pnl), 2) : null;

        // 1. Cost of Emotion — Ø P&L pro Motivation
        $motivations = ['SYSTEM', 'FOMO', 'REVENGE', 'BOREDOM', 'IMPULSE'];
        $motivationLabels = ['System', 'FOMO', 'Revenge', 'Langeweile', 'Impuls'];
        $motivationData   = [];
        foreach ($motivations as $m) {
            $motivationData[] = $avgPnl($withPnl->where('motivation', $m));
        }

        // 2. Emotional Sweet Spot — Ø P&L pro emotion_pre (1-5)
        $emotionLabels = ['1', '2', '3', '4', '5'];
        $emotionData   = [];
        for ($i = 1; $i <= 5; $i++) {
            $emotionData[] = $avgPnl($withPnl->where('emotion_pre', $i));
        }

        // 3. Compliance-Performance — Ø P&L pro Compliance-Bucket (0-25%, 25-50%, 50-75%, 75-100%)
        $compBucketLabels = ['0–25%', '25–50%', '50–75%', '75–100%'];
        $buckets = [[], [], [], []];
        foreach ($withPnl as $e) {
            $cr = $e->complianceResults;
            if ($cr->count() === 0) continue;
            $idx = min(3, (int) floor($cr->avg('complied') * 100 / 25));
            $buckets[$idx][] = (float) $e->pnl;
        }
        $compBucketData = array_map(fn($vals) => count($vals) > 0 ? round(array_sum($vals) / count($vals), 2) : null, $buckets);

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

        $documented = $this->applyTimeFilter(
            TradePlan::on($connection)->where('environment', $env)->whereNotNull('campaign_id'),
            'date_open'
        )->count();

        return view('livewire.psychology.psychology-analytics', [
            'stats'      => $stats,
            'charts'     => $charts,
            'covered'    => $all->whereNotNull('campaign_id')->count(),
            'documented' => $documented,
        ]);
    }

    /**
     * P&L zugeordneter Einträge aus dem Ledger: bei geschlossener Eröffnung deren Wert, bei noch
     * offener keiner. Nicht zugeordnete behalten den gespeicherten Wert. Wird nicht gespeichert.
     */
    private function withLedgerPnl($entries, string $connection)
    {
        $ledgers = CampaignLedger::on($connection)
            ->whereIn('campaign_id', $entries->pluck('campaign_id')->filter()->unique())
            ->orderBy('dateTime')->orderBy('id')->get()->groupBy('campaign_id');

        return $entries->each(function ($entry) use ($ledgers) {
            if (!$entry->campaign_id || !$entry->opened_at || !isset($ledgers[$entry->campaign_id])) {
                return;
            }
            $opening = CampaignOpenings::find(
                CampaignOpenings::fromLedger($ledgers[$entry->campaign_id])['openings'],
                $entry->opened_at->format('Y-m-d H:i:s')
            );
            if ($opening) {
                $entry->pnl = $opening['state'] === 'closed' ? ($opening['pnl'] ?? $entry->pnl) : null;
            }
        });
    }
}
