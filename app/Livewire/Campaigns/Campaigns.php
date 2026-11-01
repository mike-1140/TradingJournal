<?php

namespace App\Livewire\Campaigns;

use App\Traits\FilterableByTime;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Campaign;
use App\Models\TradePlan;
use App\Support\OpeningMarkers;
use App\Traits\ManagesEnvironment;

class Campaigns extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $search = '';
    public $filterStatus = 'OPEN';
    public $filterType = 'ALL';   // ALL oder Schluessel aus Campaign::TYPES
    public bool $onlyUndocumented = false;
    public $perPage = 20;

    protected $queryString = [
        'search'       => ['except' => ''],
        'filterStatus' => ['except' => 'OPEN'],
        'filterType'   => ['except' => 'ALL'],
        'onlyUndocumented' => ['except' => false],
    ];

    public function setStatus($status)
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    public function setType($type)
    {
        $this->filterType = array_key_exists($type, Campaign::TYPES) ? $type : 'ALL';
        $this->resetPage();
    }

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount()
    {
        $this->mountManagesEnvironment();
    }

    public function toggleUndocumented()
    {
        $this->onlyUndocumented = !$this->onlyUndocumented;
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Campaign::query()
            ->where('environment', $this->selectedEnvironment);

        // Die Suche gilt über alle Status und Zeiträume, sonst fehlen geschlossene Kampagnen
        $search = trim((string) $this->search);
        if ($search !== '') {
            $query->where(fn($q) => $q->where('symbol', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        }

        // Status-Filter
        if ($search === '' && $this->filterStatus === 'OPEN') {
            $query->whereIn('status', ['ACTIVE', 'PENDING']);
        } elseif ($search === '' && $this->filterStatus === 'CLOSED') {
            $query->where('status', 'CLOSED');
        }

        // Zeitfilter: OPEN-Kampagnen haben kein end_date — würden sonst herausgefiltert
        // CLOSED → end_date, ALL → start_date, OPEN → kein Filter
        if ($search === '' && $this->filterStatus === 'CLOSED') {
            $query = $this->applyTimeFilter($query, 'end_date');
        } elseif ($search === '' && $this->filterStatus === 'ALL') {
            $query = $this->applyTimeFilter($query, 'start_date');
        }

        $env = $this->selectedEnvironment;

        // Art-Filter (Aktien / Wheel / Optionen / Spreads) -- vor der Summe, damit sie mitfiltert
        if (array_key_exists($this->filterType, Campaign::TYPES)) {
            $query->ofType($this->filterType, $env);
        }

        $connection = TradePlan::getConnectionForEnvironment($env);

        if ($this->onlyUndocumented) {
            $coverage = OpeningMarkers::coverage((clone $query)->pluck('id')->all(), $connection, $env);
            $query->whereIn('id', array_keys(array_filter($coverage, fn($c) => $c['documented'] < $c['total'])));
        }

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
            ->withSum(['ledgerEntries as total_commission' => fn($q) => $q->where('environment', $env)], 'commission')
            ->withTypeFlags($env);

        $campaigns = $query->orderBy('symbol', 'asc')
            ->orderBy('start_date', 'desc')
            ->paginate($this->perPage);

        return view('livewire.campaigns.campaigns-index', [
            'campaigns'         => $campaigns,
            'searching'         => $search !== '',
            'summary'           => $summary,
            'coverage'          => OpeningMarkers::coverage($campaigns->pluck('id')->all(), $connection, $env),
        ]);
    }
}
