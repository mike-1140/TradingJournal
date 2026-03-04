<?php

namespace App\Livewire\Trades;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TradeFx;
use App\Traits\ManagesEnvironment;

class FxTrades extends Component
{
    use WithPagination, ManagesEnvironment;

    public $search = '';
    public $sortField = 'dateTime';
    public $sortDirection = 'desc';
    public $perPage = 25;
    public $filterCurrency = '';

    protected $queryString = [
        'search' => ['except' => ''],
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
        $query = TradeFx::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment);

        // Suchfilter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('symbol', 'like', '%' . $this->search . '%')
                    ->orWhere('baseCurrency', 'like', '%' . $this->search . '%')
                    ->orWhere('quoteCurrency', 'like', '%' . $this->search . '%')
                    ->orWhere('tradeID', 'like', '%' . $this->search . '%');
            });
        }

        // Currency Filter
        if ($this->filterCurrency) {
            $query->where(function($q) {
                $q->where('baseCurrency', $this->filterCurrency)
                    ->orWhere('quoteCurrency', $this->filterCurrency);
            });
        }

        // Sortierung
        $query->orderBy($this->sortField, $this->sortDirection);

        $trades = $query->paginate($this->perPage);

        // Statistiken
        $allTrades = TradeFx::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment)
            ->get();

        $totalTrades = $allTrades->count();
        $totalVolume = $allTrades->sum(function($trade) {
            return $trade->volume;
        });
        $totalCommissions = $allTrades->sum('commission');

        // Unique Currencies
        $currencies = TradeFx::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment)
            ->get()
            ->flatMap(function($trade) {
                return [$trade->baseCurrency, $trade->quoteCurrency];
            })
            ->unique()
            ->sort()
            ->values();

        return view('livewire.trades.xfx-trades', [
            'trades' => $trades,
            'totalTrades' => $totalTrades,
            'totalVolume' => $totalVolume,
            'totalCommissions' => $totalCommissions,
            'currencies' => $currencies,
        ]);
    }
}
