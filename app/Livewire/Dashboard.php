<?php

namespace App\Livewire;

use App\Services\UserSwitchService;
use App\Traits\UsesTradingDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Models\TradeOpen;
use App\Models\TradeClose;
use App\Traits\ManagesEnvironment;
use Illuminate\Support\Facades\DB;
use \App\Traits\FilterableByTime;

class Dashboard extends Component
{
    use ManagesEnvironment, UsesTradingDatabase, FilterableByTime;

    // ✅ Nur primitive Werte als Properties
    public $openTradesCount;
    public $closedTradesCount;
    public $fxTradesCount;
    public $totalProfit;
    public $winRate;
    public $avgDaysHeld;
    public $totalCommissions;
    public $fxVolume;
    public $fxCommissions;
    public $spreadsCount;
    public $spreadsProfit;
    public $users;
    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed' => 'updateTimeRangeFromEvent'
    ];

    public function switchUser($id)
    {
        // flow_trace();
        UserSwitchService::switchUser($id);

        // Hard refresh, damit alles neu geladen wird
        return redirect()->route('dashboard');
    }

    // Diese Methode wird aufgerufen, wenn das Signal kommt
    public function updateTimeRangeFromEvent($range)
    {
        $this->timeRange = $range; // Den lokalen Wert im Dashboard syncen
        $this->loadStatistics();   // Die Karten (Profit, Winrate) neu berechnen
        // Ein $refresh passiert automatisch nach Methodenaufruf in Livewire
    }
    public function mount()
    {
        // ZUERST den Zeitraum aus der Session holen (via Trait Methode)
        $this->mountFilterableByTime();

        $this->users = \App\Models\User::all();
        $this->mountManagesEnvironment();
        $this->loadStatistics(); // Hier wird jetzt der korrekte Zeitraum genutzt
    }

    public function changeEnvironment($environment)
    {
        $this->updatedSelectedEnvironment($environment);
        $this->loadStatistics();

        // Sende das Event an alle Livewire-Komponenten
        $this->dispatch('environment-changed', environment: $environment);
    }

    // Im Dashboard.php
    public function setTimeRange($range)
    {
        $this->timeRange = $range;
        $this->loadStatistics(); // Manuelles Update der Stats
    }

    public function loadStatistics()
    {
//        flow_log('Dashboard::loadStatistics() called', [
//            'selectedEnvironment' => $this->selectedEnvironment,
//            'session' => session('trading_environment'),
//        ]);

        if (!$this->selectedEnvironment) {
            $this->selectedEnvironment = session('trading_environment', 'PAPER');
        }

        // ✅ Verwende explizite Connection überall
        $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        try {
            // Open Trades
            $this->openTradesCount = TradeOpen::on($connection)
                ->where('processed', 0)
                ->where('environment', $this->selectedEnvironment)
                ->count();

           //  flow_log('Open trades loaded', ['count' => $this->openTradesCount]);

            // Closed Trades Statistiken
//            $closedTrades = TradeClose::on($connection)
//                ->where('environment', $this->selectedEnvironment)
//                ->get();

            $query = TradeClose::on($connection)->where('environment', $this->selectedEnvironment);
            $query = $this->applyTimeFilter($query, 'dateTimeClose');
            $closedTrades = $query->get();

            $this->closedTradesCount = $closedTrades->count();
            $this->totalProfit = $closedTrades->sum('profit');
            $this->totalCommissions = $closedTrades->sum('ibCommission');

            // Win Rate
            $winningTrades = $closedTrades->where('profit', '>', 0)->count();
            $this->winRate = $this->closedTradesCount > 0
                ? round(($winningTrades / $this->closedTradesCount) * 100, 2)
                : 0;

            // Durchschnittliche Haltedauer
            $this->avgDaysHeld = $closedTrades->avg('daysHeld')
                ? round($closedTrades->avg('daysHeld'), 1)
                : 0;


            // Spreads Statistiken - NUR die Zahlen, NICHT die Collection!
            $spreadsData = TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotNull('opt_strategyID')
                ->whereIn('assetCategory', ['OPT', 'FOP'])
                ->select('opt_strategyID', DB::raw('SUM(profit) as spread_profit'))
                ->groupBy('opt_strategyID')
                ->get();

            $this->spreadsCount = $spreadsData->count();
            $this->spreadsProfit = $spreadsData->sum('spread_profit');

//            flow_log('All statistics loaded successfully', [
//                'openTrades' => $this->openTradesCount,
//                'closedTrades' => $this->closedTradesCount,
//                'spreads' => $this->spreadsCount,
//            ]);

        } catch (\Exception $e) {
            flow_log('Dashboard::loadStatistics() error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    public function render()
    {
        if (!$this->selectedEnvironment) {
            $this->selectedEnvironment = session('trading_environment', 'PAPER');
        }

        // ✅ Hole Connection explizit
        $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        // Letzte 10 geschlossene Trades
        $recentClosedTradesQuery = TradeClose::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->orderBy('dateTimeClose', 'desc');
        $recentClosedTradesQuery = $this->applyTimeFilter($recentClosedTradesQuery, 'dateTimeClose');
        $recentClosedTrades = $recentClosedTradesQuery
            ->limit(10)
            ->get();

        // Aktuelle offene Positionen
        $currentOpenTrades = TradeOpen::on($connection)
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->orderBy('dateTime', 'desc')
            ->limit(10)
            ->get();

        // Performance nach Symbol (Top 5 Gewinner)
        $topSymbolsQuery = TradeClose::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->select('symbol', DB::raw('SUM(profit) as total_profit'), DB::raw('COUNT(*) as trade_count'))
            ->groupBy('symbol')
            ->orderBy('total_profit', 'desc');
        $topSymbolsQuery = $this->applyTimeFilter($topSymbolsQuery, 'dateTimeClose');
        $topSymbols = $topSymbolsQuery
            ->limit(5)
            ->get();

        // Performance nach Strategie
        $strategyPerformanceQuery = TradeClose::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->whereNotNull('strategy')
            ->select('strategy', DB::raw('SUM(profit) as total_profit'), DB::raw('COUNT(*) as trade_count'))
            ->groupBy('strategy')
            ->orderBy('total_profit', 'desc');
        $strategyPerformanceQuery = $this->applyTimeFilter($strategyPerformanceQuery, 'dateTimeClose');
        $strategyPerformance = $strategyPerformanceQuery
            ->limit(5)
            ->get();

        // ✅ Top Spreads HIER laden, nicht in loadStatistics()!
        $topSpreadsQuery = TradeClose::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->whereNotNull('opt_strategyID')
            ->whereIn('assetCategory', ['OPT', 'FOP'])
            ->select('opt_strategyID', 'opt_strategyName', DB::raw('SUM(profit) as spread_profit'))
            ->groupBy('opt_strategyID', 'opt_strategyName')
            ->orderBy('spread_profit', 'desc');
        $topSpreadsQuery = $this->applyTimeFilter($topSpreadsQuery, 'dateTimeClose');
        $topSpreads = $topSpreadsQuery
            ->limit(5)
            ->get();

        return view('livewire.dashboard', [
            'recentClosedTrades' => $recentClosedTrades,
            'currentOpenTrades' => $currentOpenTrades,
            'topSymbols' => $topSymbols,
            'strategyPerformance' => $strategyPerformance,
            'topSpreads' => $topSpreads, // ✅ Jetzt hier übergeben!
        ]);
    }
}
