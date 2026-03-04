<?php

namespace App\Livewire\Trades;

use App\Models\Tag;
use App\Models\TradeTagPivot;
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
        $query->orderBy($this->sortField, $this->sortDirection);

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

        // ✅ Lade Tags für jeden Trade
        $connection = TradeOpen::getConnectionForEnvironment($this->selectedEnvironment);
        foreach ($trades as $trade) {
            $tagIds = TradeTagPivot::on($connection)
                ->where('trade_id', $trade->tradeID)
                ->where('trade_type', 'open')
                ->where('environment', $this->selectedEnvironment)
                ->pluck('tag_id');

            $trade->trade_tags = Tag::whereIn('id', $tagIds)->get();
            // aktuellen Kurs laden
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
