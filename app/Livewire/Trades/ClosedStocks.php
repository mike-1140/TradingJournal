<?php

namespace App\Livewire\Trades;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TradeClose;
use App\Traits\ManagesEnvironment;
use App\Traits\FilterableByTime;

class ClosedStocks extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $search = '';
    public $sortField = 'dateTimeClose';
    public $sortDirection = 'desc';
    public $perPage = 25;
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

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $baseQuery = fn() => $this->applyTimeFilter(
            TradeClose::onEnvironment($this->selectedEnvironment)
                ->where('environment', $this->selectedEnvironment)
                ->where('assetCategory', 'STK'),
            'dateTimeClose'
        );

        $query = $baseQuery()->with('symbol');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('symbol', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStrategy) {
            $query->where('strategy', $this->filterStrategy);
        }

        $query->orderBy($this->sortField, $this->sortDirection);
        $trades = $query->paginate($this->perPage)->onEachSide(2);

        $totalTrades = $baseQuery()->count();

        $totalValue = $baseQuery()
            ->selectRaw('SUM(ABS(profit)) as total')
            ->value('total');

        $totalCommissions = $baseQuery()->sum('ibCommission');

        $strategies = $baseQuery()
            ->distinct()
            ->whereNotNull('strategy')
            ->pluck('strategy')
            ->filter();
        //dbg($trades[0]);

        return view('livewire.trades.closed-stocks', [
            'trades' => $trades,
            'totalTrades' => $totalTrades,
            'totalValue' => $totalValue,
            'totalCommissions' => $totalCommissions,
            'strategies' => $strategies,
        ]);
    }
}
