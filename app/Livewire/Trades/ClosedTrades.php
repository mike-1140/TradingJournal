<?php

namespace App\Livewire\Trades;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TradeClose;
use App\Models\TradeTagPivot;
use App\Models\Tag;
use App\Traits\ManagesEnvironment;

class ClosedTrades extends Component
{
    use WithPagination, ManagesEnvironment;

    // ✅ Pagination
    public $perPage = 50;

    // ✅ Filter Properties
    public $filterSymbol = '';
    public $filterAssetCategory = '';
    public $filterStartDate = '';
    public $filterEndDate = '';
    public $filterMinProfit = '';
    public $filterMaxProfit = '';
    public $filterProfitLoss = '';
    public $filterDaysHeld = '';

    // ✅ Sorting
    public $sortField = 'dateTimeClose';
    public $sortDirection = 'desc';

    // ✅ Modals
    public $showDeleteModal = false;
    public $showExportModal = false;
    public $selectedTradeId = null;

    // ✅ Export
    public $exportFormat = 'csv';
    public $exportDateRange = 'current';
    public $exportFromDate = '';
    public $exportToDate = '';

    protected $queryString = [
        'filterSymbol' => ['except' => ''],
        'filterAssetCategory' => ['except' => ''],
        'sortField' => ['except' => 'dateTimeClose'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        $this->mountManagesEnvironment();
    }

    // ✅ Reset Filters
    public function resetFilters()
    {
        $this->reset([
            'filterSymbol',
            'filterAssetCategory',
            'filterStartDate',
            'filterEndDate',
            'filterMinProfit',
            'filterMaxProfit',
            'filterProfitLoss',
            'filterDaysHeld',
        ]);
        $this->resetPage();
    }

    // ✅ Computed Properties
    public function getHasActiveFiltersProperty()
    {
        return !empty($this->filterSymbol)
            || !empty($this->filterAssetCategory)
            || !empty($this->filterStartDate)
            || !empty($this->filterEndDate)
            || !empty($this->filterMinProfit)
            || !empty($this->filterMaxProfit)
            || !empty($this->filterProfitLoss)
            || !empty($this->filterDaysHeld);
    }

    public function getActiveFilterCountProperty()
    {
        $count = 0;
        if (!empty($this->filterSymbol)) $count++;
        if (!empty($this->filterAssetCategory)) $count++;
        if (!empty($this->filterStartDate)) $count++;
        if (!empty($this->filterEndDate)) $count++;
        if (!empty($this->filterMinProfit)) $count++;
        if (!empty($this->filterMaxProfit)) $count++;
        if (!empty($this->filterProfitLoss)) $count++;
        if (!empty($this->filterDaysHeld)) $count++;
        return $count;
    }

    public function getExportTradeCountProperty()
    {
        // Berechne basierend auf exportDateRange
        return $this->buildQuery()->count();
    }

    // ✅ Sorting
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // ✅ Delete Actions
    public function confirmDelete($tradeId)
    {
        $this->selectedTradeId = $tradeId;
        $this->showDeleteModal = true;
    }

    public function deleteTrade()
    {
        if (!$this->selectedTradeId) {
            return;
        }

        $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        $trade = TradeClose::on($connection)
            ->where('id', $this->selectedTradeId)
            ->first();

        if ($trade) {
            $trade->delete();
            session()->flash('message', 'Trade deleted successfully!');
        }

        $this->showDeleteModal = false;
        $this->selectedTradeId = null;
    }

    // ✅ Export
    public function exportTrades()
    {
        // Implementierung für Export
        // z.B. CSV Download, Excel, etc.

        session()->flash('message', 'Export started! Download will begin shortly.');
        $this->showExportModal = false;
    }

    // ✅ Query Builder (wiederverwendbar)
    private function buildQuery()
    {
        if (!$this->selectedEnvironment) {
            $this->selectedEnvironment = session('trading_environment', 'PAPER');
        }

        $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        $query = TradeClose::on($connection)
            ->where('environment', $this->selectedEnvironment);

        // Apply Filters
        if ($this->filterSymbol) {
            $query->where('symbol', 'like', '%' . $this->filterSymbol . '%');
        }

        if ($this->filterAssetCategory) {
            $query->where('assetCategory', $this->filterAssetCategory);
        }

        if ($this->filterStartDate) {
            $query->whereDate('dateTimeClose', '>=', $this->filterStartDate);
        }

        if ($this->filterEndDate) {
            $query->whereDate('dateTimeClose', '<=', $this->filterEndDate);
        }

        if ($this->filterMinProfit !== '') {
            $query->where('profit', '>=', $this->filterMinProfit);
        }

        if ($this->filterMaxProfit !== '') {
            $query->where('profit', '<=', $this->filterMaxProfit);
        }

        if ($this->filterProfitLoss === 'profit') {
            $query->where('profit', '>', 0);
        } elseif ($this->filterProfitLoss === 'loss') {
            $query->where('profit', '<', 0);
        }

        if ($this->filterDaysHeld) {
            switch ($this->filterDaysHeld) {
                case '0-7':
                    $query->whereBetween('daysHeld', [0, 7]);
                    break;
                case '8-30':
                    $query->whereBetween('daysHeld', [8, 30]);
                    break;
                case '31-90':
                    $query->whereBetween('daysHeld', [31, 90]);
                    break;
                case '90+':
                    $query->where('daysHeld', '>', 90);
                    break;
            }
        }

        // Apply Sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    // ✅ Updater für Filter
    public function updatingFilterSymbol()
    {
        $this->resetPage();
    }

    public function updatingFilterAssetCategory()
    {
        $this->resetPage();
    }

    // ... andere updatingFilter* Methoden

    public function render()
    {
        $query = $this->buildQuery();
        $trades = $query->paginate($this->perPage);

        // ✅ Lade Tags für jeden Trade
        $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        foreach ($trades as $trade) {
            $tagIds = TradeTagPivot::on($connection)
                ->where('trade_id', $trade->tradeIDOpen)
                ->where('trade_type', 'closed')
                ->where('environment', $this->selectedEnvironment)
                ->pluck('tag_id');

            $trade->trade_tags = Tag::whereIn('id', $tagIds)->get();
        }

        return view('livewire.trades.closed-trades', [
            'trades' => $trades,
        ]);
    }
}
