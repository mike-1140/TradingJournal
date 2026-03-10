<?php

namespace App\Livewire\Trades;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TradeClose;
use App\Traits\ManagesEnvironment;
use App\Traits\FilterableByTime;

class ClosedOptions extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $search = '';
    public $sortField = 'dateTimeClose';
    public $sortDirection = 'desc';
    public $perPage = 25;
    public $filterPutCall = '';
    public $filterStrategy = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'dateTimeClose'],
        'sortDirection' => ['except' => 'desc'],
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

    public function changeEnvironment($environment)
    {
        $this->selectedEnvironment = $environment;
        session(['trading_environment' => $environment]);
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterPutCall = '';
        $this->filterStrategy = '';
        $this->resetPage();
    }

    public function sortBy($field)
    {
        $defaultDesc = ['dateTimeOpen', 'dateTimeClose', 'profit'];

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = in_array($field, $defaultDesc) ? 'desc' : 'asc';
        }
    }

    public function render()
    {
        $baseQuery = fn() => $this->applyTimeFilter(
            TradeClose::onEnvironment($this->selectedEnvironment)
                ->where('environment', $this->selectedEnvironment)
                ->whereIn('assetCategory', ['OPT', 'FOP']),
            'dateTimeClose'
        );

        $query = $baseQuery()->with('symbol');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('symbol', 'like', '%' . $this->search . '%')
                    ->orWhere('underlyingSymbol', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterPutCall) {
            $query->where('putCall', $this->filterPutCall);
        }

        if ($this->filterStrategy) {
            $query->where('strategy', $this->filterStrategy);
        }

        $query->orderBy($this->sortField, $this->sortDirection);
        $trades = $query->paginate($this->perPage);

        $totalTrades = $baseQuery()->count();

        $totalPremium = $baseQuery()->sum('profit');
        flow_log("Premium", [$totalTrades, $totalPremium], true);

        $totalCommissions = $baseQuery()->sum('ibCommission');

        $strategies = $baseQuery()
            ->distinct()
            ->whereNotNull('strategy')
            ->pluck('strategy')
            ->filter();

        return view('livewire.trades.closed-options', [
            'trades' => $trades,
            'totalTrades' => $totalTrades,
            'totalPremium' => $totalPremium,
            'totalCommissions' => $totalCommissions,
            'strategies' => $strategies,
        ]);
    }
}
