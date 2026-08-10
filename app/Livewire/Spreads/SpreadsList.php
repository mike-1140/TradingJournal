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
    public $filterStrategyGroup = ''; // 'verticals' → Bull Put / Bear Put / Bear Call / Bull Call
    public $filterStatus = 'closed'; // open, closed
    public $sortField = 'latest_date';
    public $sortDirection = 'desc';
    public $perPage = 25;

    protected $queryString = [
        'search'               => ['except' => ''],
        'filterStrategy'       => ['except' => ''],
        'filterStrategyGroup'  => ['except' => ''],
        'filterStatus'         => ['except' => 'closed'],
    ];

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount()
    {
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
        $this->filterStrategyGroup = '';
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

    private function applyStrategyFilter($query)
    {
        if ($this->filterStrategyGroup === 'verticals') {
            $verticals = ['Bull Put Spread', 'Bear Put Spread', 'Bear Call Spread', 'Bull Call Spread'];
            if ($this->filterStrategy && in_array($this->filterStrategy, $verticals)) {
                return $query->where('opt_strategyName', $this->filterStrategy);
            }
            return $query->whereIn('opt_strategyName', $verticals);
        }
        if ($this->filterStrategy) {
            return $query->where('opt_strategyName', $this->filterStrategy);
        }
        return $query;
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
            $baseQuery = $this->applyStrategyFilter($baseQuery);

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
                    DB::raw('(MAX(strike) - MIN(strike)) as spread_width'),
                    DB::raw('MIN(multiplier) as multiplier'),
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
            $baseQuery = $this->applyStrategyFilter($baseQuery);

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
                    DB::raw('(MAX(strike) - MIN(strike)) as spread_width'),
                    DB::raw('SUM(netCashOpen) as net_credit_open'),
                    DB::raw('MIN(multiplier) as multiplier'),
                    DB::raw("GROUP_CONCAT(DISTINCT NULLIF(linkID, '')) as link_ids"),
                )
                ->groupBy('opt_strategyID', 'opt_strategyName', 'underlyingSymbol');
        }

        // Whitelist — sortField/sortDirection kommen vom Client; nur Aliase, die in beiden Selects existieren
        $sortable  = ['latest_date', 'earliest_date', 'expiry', 'leg_count'];
        $sortField = in_array($this->sortField, $sortable, true) ? $this->sortField : 'latest_date';
        $query->orderBy($sortField, strtolower($this->sortDirection) === 'asc' ? 'asc' : 'desc');

        $spreads = $query->paginate($this->perPage);

        if ($this->filterStatus === 'closed') {
            $this->mergeAssignmentStockPnl($spreads->items(), $connection, 'total_profit', 'total_commission');
        }

        return $spreads;
    }

    /**
     * Bei Assignment/Exercise entsteht neben den Options-Legs ein Stock-Trade
     * (Zwangskauf/-verkauf zum Strike, meist sofort glattgestellt), der über die
     * gemeinsame linkID mit dem auslösenden Options-Leg verknüpft ist (siehe xml_proj:
     * book_trades.py + migrate_backfill_assignment_linkid.py). Der reine Options-P&L
     * einer Spread-Gruppe ignoriert diesen Stock-Trade komplett — hier wird er
     * nachträglich eingerechnet, damit ein per Assignment glattgestellter Spread nicht
     * künstlich profitabel wirkt (Beispiel: Bull Put Spread zeigt +118 aus den reinen
     * Options-Legs, tatsächliches Ergebnis inkl. Aktienzuteilung ist -382).
     *
     * $rows: iterable von Objekten mit ->link_ids (Komma-Liste) und ->{$profitField}.
     */
    private function mergeAssignmentStockPnl(iterable $rows, string $connection, string $profitField, ?string $commissionField = null): void
    {
        $rows = collect($rows);
        $linkIds = $rows->pluck('link_ids')
            ->filter()
            ->flatMap(fn($ids) => explode(',', $ids))
            ->unique()
            ->values();

        if ($linkIds->isEmpty()) {
            return;
        }

        $stockPnl = TradeClose::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->where('assetCategory', 'STK')
            ->whereIn('linkID', $linkIds)
            ->select('linkID', DB::raw('SUM(profit) as profit'), DB::raw('SUM(ibCommission) as commission'))
            ->groupBy('linkID')
            ->get()
            ->keyBy('linkID');

        if ($stockPnl->isEmpty()) {
            return;
        }

        foreach ($rows as $row) {
            if (empty($row->link_ids)) {
                continue;
            }

            $assignmentProfit = 0.0;
            $assignmentCommission = 0.0;
            foreach (explode(',', $row->link_ids) as $linkId) {
                if (isset($stockPnl[$linkId])) {
                    $assignmentProfit += (float) $stockPnl[$linkId]->profit;
                    $assignmentCommission += (float) $stockPnl[$linkId]->commission;
                }
            }

            if ($assignmentProfit != 0.0 || $assignmentCommission != 0.0) {
                $row->assignment_stock_profit = $assignmentProfit;
                $row->{$profitField} = (float) $row->{$profitField} + $assignmentProfit;
                if ($commissionField) {
                    $row->{$commissionField} = (float) $row->{$commissionField} + $assignmentCommission;
                }
            }
        }
    }

    private function getAvailableStrategies()
    {
        $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        if ($this->filterStrategyGroup === 'verticals') {
            return collect(['Bull Put Spread', 'Bear Put Spread', 'Bear Call Spread', 'Bull Call Spread'])->sort();
        }

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
                ->whereIn('assetCategory', ['OPT', 'FOP']);

            $closedSpreadsQuery = $this->applyTimeFilter($closedSpreadsQuery, 'dateTimeClose');
            $closedSpreadsQuery = $this->applyStrategyFilter($closedSpreadsQuery);

            $closedSpreads = $closedSpreadsQuery
                ->select('opt_strategyID', DB::raw('SUM(profit) as spread_profit'),
                    DB::raw("GROUP_CONCAT(DISTINCT NULLIF(linkID, '')) as link_ids"))
                ->groupBy('opt_strategyID')
                ->get();

            $this->mergeAssignmentStockPnl($closedSpreads, $connection, 'spread_profit');

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
            $openQuery = TradeOpen::on(TradeOpen::getConnectionForEnvironment($this->selectedEnvironment))
                ->where('environment', $this->selectedEnvironment)
                ->where('processed', 0)
                ->whereNotNull('opt_strategyID')
                ->whereIn('assetCategory', ['OPT', 'FOP']);

            $openQuery  = $this->applyStrategyFilter($openQuery);
            $openSpreads = $openQuery
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
