<?php

namespace App\Livewire\Trades;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TradeOpen;
use App\Traits\ManagesEnvironment;

class OpenTrades extends Component
{
    use WithPagination, ManagesEnvironment;

    public $search = '';
    public $sortField = 'dateTime';
    public $sortDirection = 'desc';
    public $perPage = 25;
    public $filterAssetCategory = ''; // Behält bestehende Property
    public $filterStrategy = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterAssetCategory' => ['except' => '', 'as' => 'type'],
        'sortField' => ['except' => 'dateTime'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
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

    public function updatedFilterAssetCategory()
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
        $query = TradeOpen::onEnvironment($this->selectedEnvironment)
            ->with('symbol')
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment);

        // Asset Category Filter
        if ($this->filterAssetCategory) {
            if ($this->filterAssetCategory === 'OPT') {
                $query->whereIn('assetCategory', ['OPT', 'FOP']);
            } else {
                $query->where('assetCategory', $this->filterAssetCategory);
            }
        }

        // Suchfilter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('symbol', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('tradeID', 'like', '%' . $this->search . '%')
                    ->orWhere('strategy', 'like', '%' . $this->search . '%');
            });
        }

        // Strategy Filter
        if ($this->filterStrategy) {
            $query->where('strategy', $this->filterStrategy);
        }

        // Sortierung
        $query->orderBy($this->sortField, $this->sortDirection);

        $trades = $query->paginate($this->perPage);

        // Statistiken
        $totalTrades = TradeOpen::onEnvironment($this->selectedEnvironment)
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->count();

        $totalNetCash = TradeOpen::onEnvironment($this->selectedEnvironment)
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->sum('netCash');

        $totalCommissions = TradeOpen::onEnvironment($this->selectedEnvironment)
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->sum('ibCommission');

        // Strategies für Filter
        $strategies = TradeOpen::onEnvironment($this->selectedEnvironment)
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->distinct()
            ->whereNotNull('strategy')
            ->pluck('strategy')
            ->filter();

        return view('livewire.trades.xopen-trades', [
            'trades' => $trades,
            'totalTrades' => $totalTrades,
            'totalNetCash' => $totalNetCash,
            'totalCommissions' => $totalCommissions,
            'strategies' => $strategies,
        ]);
    }
}
