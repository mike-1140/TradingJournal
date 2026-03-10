<?php

namespace App\Livewire\Spreads;

use App\Traits\FilterableByTime;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TradeClose;
use App\Models\TradeOpen;
use App\Traits\ManagesEnvironment;
use Illuminate\Support\Facades\DB;

class SpreadsList extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $search = '';
    public $filterStrategy = '';
    public $filterStatus = 'closed'; // open, closed, all
    public $sortField = 'latest_date';
    public $sortDirection = 'desc';
    public $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStrategy' => ['except' => ''],
        'filterStatus' => ['except' => 'closed'],
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

    public function render()
    {
        $spreads = $this->getSpreads();
        $strategies = $this->getAvailableStrategies();
        $statistics = $this->getStatistics();

        return view('livewire.spreads.spreads-list', [
            'spreads' => $spreads,
            'strategies' => $strategies,
            'statistics' => $statistics,
        ]);
    }

    private function getSpreads()
    {
        $connection = $this->filterStatus === 'open'
            ? TradeOpen::getConnectionForEnvironment($this->selectedEnvironment)
            : TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        if ($this->filterStatus === 'open') {
            $baseQuery = TradeOpen::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->where('processed', 0)
                ->whereNotNull('opt_strategyID')
                ->where('opt_strategyID', '!=', '')
                ->whereIn('assetCategory', ['OPT', 'FOP']);

            if ($this->search) {
                $baseQuery->where('underlyingSymbol', 'like', '%' . $this->search . '%');
            }
            if ($this->filterStrategy) {
                $baseQuery->where('opt_strategyName', $this->filterStrategy);
            }

            $query = $baseQuery
                ->select(
                    'opt_strategyID',
                    'opt_strategyName',
                    'underlyingSymbol',
                    DB::raw('MIN(dateTime) as earliest_date'),
                    DB::raw('MAX(dateTime) as latest_date'),
                    DB::raw('COUNT(*) as leg_count'),
                    DB::raw('SUM(netCash) as total_premium'),
                    DB::raw('SUM(ibCommission) as total_commission'),
                    DB::raw("SUM(CASE WHEN buySell = 'SELL' THEN netCash ELSE 0 END) as premium_sell"),
                    DB::raw("SUM(CASE WHEN buySell = 'BUY' THEN netCash ELSE 0 END) as premium_buy"),
                    DB::raw("GROUP_CONCAT(CASE WHEN putCall = 'PUT' THEN ROUND(strike, 0) ELSE NULL END ORDER BY strike ASC SEPARATOR '/') as put_strikes"),
                    DB::raw("GROUP_CONCAT(CASE WHEN putCall = 'CALL' THEN ROUND(strike, 0) ELSE NULL END ORDER BY strike ASC SEPARATOR '/') as call_strikes"),
                    DB::raw('MIN(expiry) as expiry'),
                )
                ->groupBy('opt_strategyID', 'opt_strategyName', 'underlyingSymbol');

        } else { // closed
            $baseQuery = TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotNull('opt_strategyID')
                ->where('opt_strategyID', '!=', '')
                ->whereIn('assetCategory', ['OPT', 'FOP']);

            $baseQuery = $this->applyTimeFilter($baseQuery, 'dateTimeClose');

            if ($this->search) {
                $baseQuery->where('underlyingSymbol', 'like', '%' . $this->search . '%');
            }
            if ($this->filterStrategy) {
                $baseQuery->where('opt_strategyName', $this->filterStrategy);
            }

            $query = $baseQuery
                ->select(
                    'opt_strategyID',
                    'opt_strategyName',
                    'underlyingSymbol',
                    DB::raw('MIN(dateTimeOpen) as earliest_date'),
                    DB::raw('MAX(dateTimeClose) as latest_date'),
                    DB::raw('COUNT(*) as leg_count'),
                    DB::raw('SUM(profit) as total_profit'),
                    DB::raw('SUM(ibCommission) as total_commission'),
                    DB::raw('AVG(daysHeld) as avg_days_held'),
                    DB::raw("SUM(CASE WHEN buySellOpen = 'SELL' THEN profit ELSE 0 END) as profit_sell"),
                    DB::raw("SUM(CASE WHEN buySellOpen = 'BUY' THEN profit ELSE 0 END) as profit_buy"),
                    DB::raw("GROUP_CONCAT(CASE WHEN putCall = 'P' THEN ROUND(strike, 0) ELSE NULL END ORDER BY strike ASC SEPARATOR '/') as put_strikes"),
                    DB::raw("GROUP_CONCAT(CASE WHEN putCall = 'C' THEN ROUND(strike, 0) ELSE NULL END ORDER BY strike ASC SEPARATOR '/') as call_strikes"),
                    DB::raw('MIN(expiry) as expiry'),
                )
                ->groupBy('opt_strategyID', 'opt_strategyName', 'underlyingSymbol');
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    private function getAvailableStrategies()
    {
        $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        if ($this->filterStatus === 'open') {
            return TradeOpen::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotNull('opt_strategyName')
                ->distinct()
                ->pluck('opt_strategyName')
                ->filter()
                ->sort();
        } else {
            return TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotNull('opt_strategyName')
                ->distinct()
                ->pluck('opt_strategyName')
                ->filter()
                ->sort();
        }
    }

    private function getStatistics()
    {
        $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        if ($this->filterStatus === 'closed') {
            $closedSpreadsQuery = TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotNull('opt_strategyID')
                ->where('opt_strategyID', '!=', '')
                ->whereIn('assetCategory', ['OPT', 'FOP'])
                ->select('opt_strategyID', DB::raw('SUM(profit) as spread_profit'))
                ->groupBy('opt_strategyID');

            $closedSpreadsQuery = $this->applyTimeFilter($closedSpreadsQuery, 'dateTimeClose');
            $closedSpreads = $closedSpreadsQuery->get();

            $totalSpreads = $closedSpreads->count();
            $totalProfit = $closedSpreads->sum('spread_profit');
            $winningSpreads = $closedSpreads->where('spread_profit', '>', 0)->count();
            $losingSpreads = $closedSpreads->where('spread_profit', '<', 0)->count();
            $winRate = $totalSpreads > 0 ? round(($winningSpreads / $totalSpreads) * 100, 1) : 0;
            $avgProfit = $totalSpreads > 0 ? $totalProfit / $totalSpreads : 0;

            return [
                'total_spreads' => $totalSpreads,
                'total_profit' => $totalProfit,
                'winning_spreads' => $winningSpreads,
                'losing_spreads' => $losingSpreads,
                'win_rate' => $winRate,
                'avg_profit' => $avgProfit,
            ];
        } else {
            $openSpreads = TradeOpen::on(TradeOpen::getConnectionForEnvironment($this->selectedEnvironment))
                ->where('environment', $this->selectedEnvironment)
                ->where('processed', 0)
                ->whereNotNull('opt_strategyID')
                ->whereIn('assetCategory', ['OPT', 'FOP'])
                ->select('opt_strategyID', DB::raw('SUM(netCash) as spread_premium'))
                ->groupBy('opt_strategyID')
                ->get();

            return [
                'total_spreads' => $openSpreads->count(),
                'total_premium' => $openSpreads->sum('spread_premium'),
                'winning_spreads' => 0,
                'losing_spreads' => 0,
                'win_rate' => 0,
                'avg_profit' => 0,
            ];
        }
    }
}
