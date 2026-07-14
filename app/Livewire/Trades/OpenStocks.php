<?php

namespace App\Livewire\Trades;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TradeOpen;
use App\Services\YahooFinanceService;
use App\Traits\ManagesEnvironment;

class OpenStocks extends Component
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

    public function resetFilters()
    {
        $this->search = '';
        $this->filterStrategy = '';
        $this->resetPage();
    }

    public function updatedFilterAssetCategory()
    {
        $this->resetPage();
    }

    // Whitelist — sortField/sortDirection kommen vom Client (Livewire-Property + URL-Query)
    private const SORTABLE_FIELDS = ['dateTime', 'symbol', 'tradePrice'];

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

    public function render(YahooFinanceService $yahoo)
    {
        $query = TradeOpen::onEnvironment($this->selectedEnvironment)
            ->with('symbol')
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->where('assetCategory', 'STK');

//        dbg('Checkpoint 1');
//        // Zeigt die SQL-Query ohne sie auszuführen
//        dbg($query->toSql());
//        // Mit den Parametern
//        dbg($query->toRawSql());


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
        $sortField = in_array($this->sortField, self::SORTABLE_FIELDS, true) ? $this->sortField : 'dateTime';
        $query->orderBy($sortField, strtolower($this->sortDirection) === 'desc' ? 'desc' : 'asc');

        $trades = $query->paginate($this->perPage);

        // Statistiken
        $totalTrades = TradeOpen::onEnvironment($this->selectedEnvironment)
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->where('assetCategory', 'STK')
            ->count();

        $totalNetCash = TradeOpen::onEnvironment($this->selectedEnvironment)
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->where('assetCategory', 'STK')
            ->selectRaw('SUM(ABS(netCash)) as total')
            ->value('total');

        $totalCommissions = TradeOpen::onEnvironment($this->selectedEnvironment)
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->where('assetCategory', 'STK')
            ->sum('ibCommission');

        // Strategies für Filter
        $strategies = TradeOpen::onEnvironment($this->selectedEnvironment)
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->where('assetCategory', 'STK')
            ->distinct()
            ->whereNotNull('strategy')
            ->pluck('strategy')
            ->filter();

        foreach ($trades as $trade) {
            $trade->current_price  = $yahoo->quote($trade->symbol);
            $trade->daysHeld = Carbon::parse($trade->dateTime)
                ->diffInDays(Carbon::now());
        }
        // dbg($trades[0]);
        return view('livewire.trades.open-stocks', [
            'trades' => $trades,
            'totalTrades' => $totalTrades,
            'totalValue' => $totalNetCash,
            'totalCommissions' => $totalCommissions,
            'strategies' => $strategies,
        ]);
    }
}
