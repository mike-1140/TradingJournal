<?php

namespace App\Livewire\Campaigns;

use App\Traits\FilterableByTime;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Campaign;
use App\Models\TradePlan;
use App\Traits\ManagesEnvironment;
use Livewire\Attributes\On;

class Campaigns extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $search = '';
    public $filterStatus = 'OPEN';
    public $perPage = 20;

    public function setStatus($status)
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

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
        $this->resetPage();
    }

    #[On('clear-search')]
    public function resetFilters()
    {
        $this->reset(['search']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Campaign::query()
            ->where('environment', $this->selectedEnvironment);

        // Status-Filter
        if ($this->filterStatus === 'OPEN') {
            $query->whereIn('status', ['ACTIVE', 'PENDING']);
        } elseif ($this->filterStatus === 'CLOSED') {
            $query->where('status', 'CLOSED');
        }

        // Zeitfilter: OPEN-Kampagnen haben kein end_date — würden sonst herausgefiltert
        // CLOSED → end_date, ALL → start_date, OPEN → kein Filter
        if ($this->filterStatus === 'CLOSED') {
            $query = $this->applyTimeFilter($query, 'end_date');
        } elseif ($this->filterStatus === 'ALL') {
            $query = $this->applyTimeFilter($query, 'start_date');
        }

        $env = $this->selectedEnvironment;

        // Aggregat-PnL über alle gefilterten Kampagnen (alle Seiten)
        $allIds  = (clone $query)->pluck('id');
        $summary = \App\Models\CampaignLedger::whereIn('campaign_id', $allIds)
            ->where('environment', $env)
            ->selectRaw('SUM(realized_pnl) as total_pnl, SUM(realized_pnl + commission) as total_net_pnl, COUNT(DISTINCT campaign_id) as campaign_count')
            ->first();

        $query
            ->withSum(['ledgerEntries as total_pnl' => fn($q) => $q->where('environment', $env)], 'realized_pnl')
            ->withSum(['ledgerEntries as shares_balance' => fn($q) => $q->where('environment', $env)->where('assetCategory', 'STK')], 'inventory_delta')
            ->withSum(['ledgerEntries as total_proceeds' => fn($q) => $q->where('environment', $env)], 'proceeds')
            ->withSum(['ledgerEntries as total_commission' => fn($q) => $q->where('environment', $env)], 'commission');

        $globalView = !empty($this->search) && strlen($this->search) >= 2;

        $campaigns = $query->orderBy('symbol', 'asc')
            ->orderBy('start_date', 'desc')
            ->paginate($this->perPage);

        $loggedCampaignIds = TradePlan::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('environment', $env)
            ->pluck('campaign_id');

        return view('livewire.campaigns.campaigns-index', [
            'campaigns'         => $campaigns,
            'globalView'        => $globalView,
            'searchTerm'        => strtoupper($this->search),
            'summary'           => $summary,
            'loggedCampaignIds' => $loggedCampaignIds,
        ]);
    }
}
