<?php

namespace App\Livewire\Trades;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TradeRaw;
use App\Traits\ManagesEnvironment;

class RawData extends Component
{
    use WithPagination, ManagesEnvironment;

    public $search = '';
    public $sortField = 'dateTime';
    public $sortDirection = 'desc';
    public $perPage = 25;
    public $filterProcessed = ''; // all, processed, unprocessed
    public $filterAssetCategory = '';
    public $filterBuySell = '';

    protected $queryString = [
        'search'        => ['except' => ''],
        'sortField'     => ['except' => 'dateTime'],
        'sortDirection' => ['except' => 'desc'],
    ];

    protected $listeners = [
        'environment-changed' => '$refresh',
    ];

    public function mount()
    {
        $this->mountManagesEnvironment();
    }

    public function updatingSearch()           { $this->resetPage(); }
    public function updatingFilterProcessed()  { $this->resetPage(); }
    public function updatingFilterAssetCategory() { $this->resetPage(); }
    public function updatingFilterBuySell()    { $this->resetPage(); }
    public function updatingPerPage()          { $this->resetPage(); }

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
        // Verwende die richtige Datenbank basierend auf dem Environment
        $query = TradeRaw::onEnvironment($this->selectedEnvironment)
            ->with('symbol')
            ->where('environment', $this->selectedEnvironment);

        // Suchfilter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('symbol', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('tradeID', 'like', '%' . $this->search . '%')
                    ->orWhere('ibOrderID', 'like', '%' . $this->search . '%');
            });
        }

        // Filter
        if ($this->filterProcessed === 'processed') {
            $query->where('processed', 1);
        } elseif ($this->filterProcessed === 'unprocessed') {
            $query->where('processed', 0);
        }

        if ($this->filterAssetCategory) {
            $query->where('assetCategory', $this->filterAssetCategory);
        }

        if ($this->filterBuySell) {
            $query->where('buySell', $this->filterBuySell);
        }

        // Sortierung
        $query->orderBy($this->sortField, $this->sortDirection);

        $trades = $query->paginate($this->perPage);

        // Statistiken
        $totalRaw = TradeRaw::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment)
            ->count();
        $processedCount = TradeRaw::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment)
            ->where('processed', 1)
            ->count();
        $unprocessedCount = TradeRaw::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment)
            ->where('processed', 0)
            ->count();
        $totalNetCash = TradeRaw::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment)
            ->sum('netCash');

        // Dropdown-Daten
        $assetCategories = TradeRaw::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment)
            ->distinct()
            ->pluck('assetCategory')
            ->filter();

        return view('livewire.trades.raw-data', [
            'trades' => $trades,
            'totalRaw' => $totalRaw,
            'processedCount' => $processedCount,
            'unprocessedCount' => $unprocessedCount,
            'totalNetCash' => $totalNetCash,
            'assetCategories' => $assetCategories,
        ]);
    }
}
