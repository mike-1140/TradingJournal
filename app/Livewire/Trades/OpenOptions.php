<?php

namespace App\Livewire\Trades;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TradeOpen;
use App\Traits\ManagesEnvironment;

class OpenOptions extends Component
{
    use WithPagination, ManagesEnvironment;

    public $search = '';
    public $sortField = 'expiry';
    public $sortDirection = 'asc';
    public $perPage = 25;
    public $filterPutCall = '';
    public $filterStrategy = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'expiry'],
        'sortDirection' => ['except' => 'asc'],
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

    public function resetFilters()
    {
        $this->search = '';
        $this->filterPutCall = '';
        $this->filterStrategy = '';
        $this->resetPage();
    }

    // Whitelist — sortField/sortDirection kommen vom Client (Livewire-Property + URL-Query)
    private const SORTABLE_FIELDS = ['dateTime', 'expiry', 'strike', 'underlyingSymbol'];

    public function sortBy($field)
    {
        if (!in_array($field, self::SORTABLE_FIELDS, true)) {
            return;
        }

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
            ->where('environment', $this->selectedEnvironment)
            ->whereIn('assetCategory', ['OPT', 'FOP']);

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

        $sortField = in_array($this->sortField, self::SORTABLE_FIELDS, true) ? $this->sortField : 'expiry';
        $query->orderBy($sortField, strtolower($this->sortDirection) === 'desc' ? 'desc' : 'asc');
        $trades = $query->paginate($this->perPage);

        $baseQuery = fn() => TradeOpen::onEnvironment($this->selectedEnvironment)
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->whereIn('assetCategory', ['OPT', 'FOP']);

        $totalTrades       = $baseQuery()->count();
        $totalPremium      = $baseQuery()->sum('netCash');
        $totalCommissions  = $baseQuery()->sum('ibCommission');
        $strategies        = $baseQuery()->distinct()->whereNotNull('strategy')->pluck('strategy')->filter();

        return view('livewire.trades.open-options', [
            'trades' => $trades,
            'totalTrades' => $totalTrades,
            'totalPremium' => $totalPremium,
            'totalCommissions' => $totalCommissions,
            'strategies' => $strategies,
        ]);
    }
}
