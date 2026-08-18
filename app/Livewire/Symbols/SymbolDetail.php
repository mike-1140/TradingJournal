<?php

namespace App\Livewire\Symbols;

use App\Models\Campaign;
use App\Models\CampaignLedger;
use App\Models\TradePlan;
use App\Traits\FilterableByTime;
use App\Traits\ManagesEnvironment;
use App\Traits\HasSmartBackUrl;
use Carbon\Carbon;
use Livewire\Component;

class SymbolDetail extends Component
{
    use ManagesEnvironment, FilterableByTime, HasSmartBackUrl;

    public string $symbol;

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount(string $symbol)
    {
        $this->mountManagesEnvironment();
        $this->symbol = strtoupper($symbol);
        $this->setBackUrl('symbols');
    }

    public function render()
    {
        // Alle Kampagnen für dieses Symbol (unzeitgefiltert — volle Kampagnenliste)
        $campaigns = Campaign::where('symbol', $this->symbol)
            ->where('environment', $this->selectedEnvironment)
            ->orderBy('start_date', 'asc')
            ->get();

        $campaignIds = $campaigns->pluck('id');

        if ($campaignIds->isEmpty()) {
            return view('livewire.symbols.symbol-detail', [
                'campaigns'          => $campaigns,
                'campaignStats'      => collect(),
                'metrics'            => null,
                'winRate'            => null,
                'winCount'           => 0,
                'closedCount'        => 0,
                'chartData'          => [],
                'latestCampaign'     => null,
                'loggedCampaignIds'  => collect(),
            ]);
        }

        $loggedCampaignIds = TradePlan::whereIn('campaign_id', $campaignIds)
            ->where('environment', $this->selectedEnvironment)
            ->pluck('campaign_id');

        // Zeitgefilterte Basis für Metriken oben + Chart
        $filteredQuery = fn() => $this->applyTimeFilter(
            CampaignLedger::whereIn('campaign_id', $campaignIds->toArray())
                ->where('environment', $this->selectedEnvironment),
            'dateTime'
        );

        // Aggregat-Metriken (zeitgefiltert)
        $metrics = $filteredQuery()->selectRaw('
            SUM(realized_pnl)               as total_pnl,
            SUM(commission)                 as total_commission,
            SUM(realized_pnl + commission)  as total_net_pnl,
            MIN(dateTime)                   as first_trade,
            MAX(dateTime)                   as last_trade
        ')->first();

        // Pro-Kampagne Statistiken (NICHT zeitgefiltert — volle Kampagnenergebnisse)
        $campaignStats = CampaignLedger::whereIn('campaign_id', $campaignIds->toArray())
            ->where('environment', $this->selectedEnvironment)
            ->selectRaw('
                campaign_id,
                SUM(realized_pnl)              as total_pnl,
                SUM(commission)                as total_commission,
                SUM(realized_pnl + commission) as total_net_pnl,
                COUNT(*)                       as ledger_count
            ')
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        // Win Rate: nur CLOSED Kampagnen, Netto P&L > 0 zählt als Win
        $closedCampaigns = $campaigns->where('status', 'CLOSED');
        $winCount = 0;
        foreach ($closedCampaigns as $camp) {
            $stats = $campaignStats->get($camp->id);
            if ($stats && (float) $stats->total_net_pnl > 0) {
                $winCount++;
            }
        }
        $closedCount = $closedCampaigns->count();
        $winRate     = $closedCount > 0 ? round($winCount / $closedCount * 100, 1) : null;

        // Chart-Daten: kumulativer P&L über alle Kampagnen (zeitgefiltert)
        $allEntries = $filteredQuery()->orderBy('dateTime', 'asc')->get();
        $running    = 0.0;
        $chartData  = [];

        $firstEntry = $allEntries->first();
        if ($firstEntry) {
            $chartData[] = [
                'label' => Carbon::parse($firstEntry->dateTime)->format('d.m.y'),
                'cumul' => 0.0,
            ];
        }

        $allEntries
            ->filter(fn($e) => (float) $e->realized_pnl != 0)
            ->groupBy(fn($e) => Carbon::parse($e->dateTime)->format('Y-m-d'))
            ->each(function ($day, $dateKey) use (&$running, &$chartData) {
                $running    += $day->sum(fn($e) => (float) $e->realized_pnl);
                $chartData[] = [
                    'label' => Carbon::parse($dateKey)->format('d.m.y'),
                    'cumul' => round($running, 2),
                ];
            });

        $latestCampaign = $campaigns->sortByDesc('start_date')->first();

        return view('livewire.symbols.symbol-detail', [
            'campaigns'         => $campaigns,
            'campaignStats'     => $campaignStats,
            'metrics'           => $metrics,
            'winRate'           => $winRate,
            'winCount'          => $winCount,
            'closedCount'       => $closedCount,
            'chartData'         => $chartData,
            'latestCampaign'    => $latestCampaign,
            'loggedCampaignIds' => $loggedCampaignIds,
        ]);
    }
}
