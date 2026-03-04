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

    public function changeEnvironment($environment)
    {
        $this->debugLog('Environment changed', ['to' => $environment]);
        $this->selectedEnvironment = $environment;
        session(['trading_environment' => $environment]);
        $this->resetPage();
    }

    public function updatingSearch()
    {
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

    public function xrender()
    {
        $connection = $this->filterStatus === 'open'
            ? TradeOpen::getConnectionForEnvironment($this->selectedEnvironment)
            : TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        if ($this->filterStatus === 'open') {
            $spreads = TradeOpen::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotNull('opt_strategyID') // ✅ NUR Trades MIT strategyID
                ->where('opt_strategyID', '!=', '') // ✅ Und nicht leer
                ->whereIn('assetCategory', ['OPT', 'FOP']);
        } else {
            $spreads = TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotNull('opt_strategyID') // ✅ NUR Trades MIT strategyID
                ->where('opt_strategyID', '!=', '') // ✅ Und nicht leer
                ->whereIn('assetCategory', ['OPT', 'FOP']);
        }

        // Filter nach Strategie-Typ
        if ($this->filterStrategy !== 'all') {
            $spreads->where('opt_strategyName', 'like', '%' . $this->filterStrategy . '%');
        }

        // Gruppiere nach strategyID
        $groupedSpreads = $spreads->get()
            ->filter(function($trade) {
                // ✅ Doppelte Sicherheit: Filtere nochmal
                return !empty($trade->opt_strategyID);
            })
            ->groupBy(function($trade) {
                return $trade->opt_strategyID . '_' . $trade->opt_strategyName;
            });

        // ✅ Wenn leer, gib leere Collection zurück
        if ($groupedSpreads->isEmpty()) {
            return view('livewire.spreads.spreads-list', [
                'groupedSpreads' => collect([]),
                'strategyTypes' => collect([]),
            ]);
        }

        // Berechne Statistiken für jeden Spread
        $spreadStats = $groupedSpreads->map(function($group) {
            $first = $group->first();

            // ✅ Prüfe nochmal ob strategyID existiert
            if (empty($first->opt_strategyID)) {
                return null;
            }

            return (object)[
                'opt_strategyID' => $first->opt_strategyID,
                'opt_strategyName' => $first->opt_strategyName,
                'underlyingSymbol' => $first->underlyingSymbol,
                'leg_count' => $group->count(),
                'earliest_date' => $group->min('dateTime'),
                'latest_date' => $group->max('dateTime'),
                // ... rest der Statistiken
            ];
        })->filter(); // ✅ Entferne null-Werte

        // Extrahiere eindeutige Strategie-Typen
        $strategyTypes = $spreadStats->pluck('opt_strategyName')
            ->unique()
            ->sort()
            ->values();

        return view('livewire.spreads.spreads-list', [
            'groupedSpreads' => $spreadStats,
            'strategyTypes' => $strategyTypes,
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
                ->whereNotNull('opt_strategyID')
                ->whereIn('assetCategory', ['OPT', 'FOP']);

            // Filter anwenden
            if ($this->search) {
                $baseQuery->where('underlyingSymbol', 'like', '%' . $this->search . '%');
            }
            if ($this->filterStrategy) {
                $baseQuery->where('opt_strategyName', $this->filterStrategy);
            }

            // Aggregieren
            $query = $baseQuery
                ->select(
                    'opt_strategyID',
                    'opt_strategyName',
                    'underlyingSymbol',
                    DB::raw('MIN(dateTime) as earliest_date'),
                    DB::raw('MAX(dateTime) as latest_date'),
                    DB::raw('COUNT(*) as leg_count'),
                    DB::raw('SUM(netCash) as total_premium'),
                    DB::raw('SUM(ibCommission) as total_commission')
                )
                ->groupBy('opt_strategyID', 'opt_strategyName', 'underlyingSymbol');

        } else { // closed
            $baseQuery = TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotNull('opt_strategyID')
                ->whereIn('assetCategory', ['OPT', 'FOP']);

            // Filter anwenden
            if ($this->search) {
                $baseQuery->where('underlyingSymbol', 'like', '%' . $this->search . '%');
            }
            if ($this->filterStrategy) {
                $baseQuery->where('opt_strategyName', $this->filterStrategy);
            }

            // Aggregieren
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
                    DB::raw('AVG(daysHeld) as avg_days_held')
                )
                ->groupBy('opt_strategyID', 'opt_strategyName', 'underlyingSymbol');
        }

        // Sortierung
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
                ->whereIn('assetCategory', ['OPT', 'FOP'])
                ->select('opt_strategyID', DB::raw('SUM(profit) as spread_profit'))
                ->groupBy('opt_strategyID');

            $closedSpreadsQuery = $this->applyTimeFilter($closedSpreadsQuery, 'dateTimeClose');
            $closedSpreads = $closedSpreadsQuery->get();

            $totalSpreads = $closedSpreads->count();
            $totalProfit = $closedSpreads->sum('spread_profit');
            $winningSpreads = $closedSpreads->where('spread_profit', '>', 0)->count();
            $losingSpreads = $closedSpreads->where('spread_profit', '<', 0)->count();
            $winRate = $totalSpreads > 0 ? round(($winningSpreads / $totalSpreads) * 100, 2) : 0;

            return [
                'total_spreads' => $totalSpreads,
                'total_profit' => $totalProfit,
                'winning_spreads' => $winningSpreads,
                'losing_spreads' => $losingSpreads,
                'win_rate' => $winRate,
            ];
        } else {
            $openSpreads = TradeOpen::on(TradeOpen::getConnectionForEnvironment($this->selectedEnvironment))
                ->where('environment', $this->selectedEnvironment)
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
            ];
        }
    }
}
