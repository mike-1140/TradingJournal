<?php

namespace App\Livewire\Symbols;

use App\Traits\UsesTradingDatabase;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Symbol;
use App\Models\TradeOpen;
use App\Models\TradeClose;
use Illuminate\Support\Facades\DB;
use App\Traits\ManagesEnvironment;
use App\Traits\FilterableByTime;

class SymbolList extends Component
{
    use WithPagination, ManagesEnvironment, UsesTradingDatabase, FilterableByTime;

    public $search = '';
    public $sortField = 'symbol';
    public $sortDirection = 'asc';
    public $perPage = 25;
    public $filterAssetCategory = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedEnvironment' => ['except' => 'MONEY'],
        'sortField' => ['except' => 'symbol'],
        'sortDirection' => ['except' => 'asc'],
    ];

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => 'updateTimeRangeFromEvent',
    ];

    public function mount()
    {
        $this->mountFilterableByTime();
        $this->mountManagesEnvironment();
    }

    public function updateTimeRangeFromEvent($range)
    {
        $this->timeRange = $range;
        $this->resetPage();
    }

    public function changeEnvironment($environment)
    {
        $this->selectedEnvironment = $environment;
        session(['trading_environment' => $environment]);

        // Globales Event
        $this->dispatch('environmentChanged', environment: $environment);

        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedEnvironment()
    {
        session(['trading_environment' => $this->selectedEnvironment]);
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
        // Symbole sollten in beiden Datenbanken existieren
        // Wir verwenden die entsprechende Connection
        $connection = Symbol::getConnectionForEnvironment($this->selectedEnvironment);

        $query = Symbol::on($connection)
            ->where('environment', $this->selectedEnvironment);

        // Suchfilter
        if ($this->search) {
            $query->where('symbol', 'like', '%' . $this->search . '%');
        }

        // Asset Category Filter
        if ($this->filterAssetCategory) {
            $query->where('assetCategory', $this->filterAssetCategory);
        }

        // Sortierung
        $query->orderBy($this->sortField, $this->sortDirection);

        $symbols = $query->paginate($this->perPage);

        // Statistiken hinzufügen
        foreach ($symbols as $symbol) {
            $symbol->open_trades_count = TradeOpen::on($connection)
                ->where('symID', $symbol->id)
                ->where('environment', $this->selectedEnvironment)
                ->count();

            $closedTrades = $this->applyTimeFilter(
                TradeClose::on($connection)
                    ->where('symID', $symbol->id)
                    ->where('environment', $this->selectedEnvironment),
                'dateTimeClose'
            )->get();

            $symbol->closed_trades_count = $closedTrades->count();
            $symbol->total_profit = $closedTrades->sum('profit');
            $symbol->win_rate = $closedTrades->count() > 0
                ? round(($closedTrades->where('profit', '>', 0)->count() / $closedTrades->count()) * 100, 1)
                : 0;
        }

        // Gesamt-Statistiken
        $totalSymbols = Symbol::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->count();

        $activeSymbols = Symbol::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->whereExists(function ($query) use ($connection) {
                $query->select(DB::raw(1))
                    ->from('trades_open')
                    ->whereColumn('trades_open.symID', 'symbols.id')
                    ->where('trades_open.environment', $this->selectedEnvironment);
            })
            ->count();

        // Asset Categories für Filter
        $assetCategories = Symbol::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->distinct()
            ->pluck('assetCategory')
            ->filter();

        return view('livewire.symbols.symbol-list', [
            'symbols' => $symbols,
            'totalSymbols' => $totalSymbols,
            'activeSymbols' => $activeSymbols,
            'assetCategories' => $assetCategories,
        ]);
    }
}
