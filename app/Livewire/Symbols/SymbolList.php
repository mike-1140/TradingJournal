<?php

namespace App\Livewire\Symbols;

use App\Models\Campaign;
use App\Models\CampaignLedger;
use App\Traits\FilterableByTime;
use App\Traits\ManagesEnvironment;
use Livewire\Component;

class SymbolList extends Component
{
    use ManagesEnvironment, FilterableByTime;

    public string $search       = '';
    public string $sortField    = 'symbol';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search'        => ['except' => ''],
        'sortField'     => ['except' => 'symbol'],
        'sortDirection' => ['except' => 'asc'],
    ];

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount()
    {
        $this->mountFilterableByTime();
        $this->mountManagesEnvironment();
    }

    public function updatingSearch()
    {
        // kein resetPage mehr nötig — keine Pagination
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        // Alle Kampagnen für dieses Environment (Query 1)
        $campaigns = Campaign::where('environment', $this->selectedEnvironment)
            ->when($this->search, fn($q) => $q->where('symbol', 'like', '%' . $this->search . '%'))
            ->orderBy('symbol')
            ->orderBy('start_date')
            ->get();

        if ($campaigns->isEmpty()) {
            return view('livewire.symbols.symbol-list', [
                'symbols'       => collect(),
                'totalSymbols'  => 0,
                'activeSymbols' => 0,
            ]);
        }

        // P&L pro Kampagne — eine Query für alle (Query 2)
        $pnlByCampaign = CampaignLedger::whereIn('campaign_id', $campaigns->pluck('id')->toArray())
            ->where('environment', $this->selectedEnvironment)
            ->selectRaw('
                campaign_id,
                SUM(realized_pnl)              as total_pnl,
                SUM(commission)                as total_commission,
                SUM(realized_pnl + commission) as total_net_pnl
            ')
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        // Pro Symbol aggregieren (PHP-seitig, kein weiterer DB-Roundtrip)
        $symbols = $campaigns->groupBy('symbol')->map(function ($group, $symbol) use ($pnlByCampaign) {
            $closedGroup = $group->where('status', 'CLOSED');
            $totalPnl    = 0.0;
            $totalComm   = 0.0;
            $totalNetPnl = 0.0;
            $winCount    = 0;

            foreach ($group as $campaign) {
                $pnl = $pnlByCampaign->get($campaign->id);
                if ($pnl) {
                    $totalPnl    += (float) $pnl->total_pnl;
                    $totalComm   += (float) $pnl->total_commission;
                    $totalNetPnl += (float) $pnl->total_net_pnl;
                }
            }

            foreach ($closedGroup as $campaign) {
                $pnl = $pnlByCampaign->get($campaign->id);
                if ($pnl && (float) $pnl->total_net_pnl > 0) {
                    $winCount++;
                }
            }

            $closedCount = $closedGroup->count();

            return (object) [
                'symbol'          => $symbol,
                'campaign_count'  => $group->count(),
                'open_count'      => $group->whereIn('status', ['ACTIVE', 'PENDING'])->count(),
                'closed_count'    => $closedCount,
                'total_pnl'       => $totalPnl,
                'total_commission' => $totalComm,
                'total_net_pnl'   => $totalNetPnl,
                'win_rate'        => $closedCount > 0 ? round($winCount / $closedCount * 100, 1) : null,
                'win_count'       => $winCount,
                'last_activity'   => $group->max('end_date'),
                'first_activity'  => $group->min('start_date'),
                'latest_status'   => $group->sortByDesc('start_date')->first()->status,
            ];
        })->values();

        // Sortierung anwenden
        $symbols = match($this->sortField) {
            'total_net_pnl'  => $this->sortDirection === 'asc'
                ? $symbols->sortBy('total_net_pnl')
                : $symbols->sortByDesc('total_net_pnl'),
            'last_activity'  => $this->sortDirection === 'asc'
                ? $symbols->sortBy('last_activity')
                : $symbols->sortByDesc('last_activity'),
            'campaign_count' => $this->sortDirection === 'asc'
                ? $symbols->sortBy('campaign_count')
                : $symbols->sortByDesc('campaign_count'),
            default          => $this->sortDirection === 'asc'
                ? $symbols->sortBy('symbol')
                : $symbols->sortByDesc('symbol'),
        };

        return view('livewire.symbols.symbol-list', [
            'symbols'       => $symbols->values(),
            'totalSymbols'  => $symbols->count(),
            'activeSymbols' => $symbols->where('open_count', '>', 0)->count(),
        ]);
    }
}
