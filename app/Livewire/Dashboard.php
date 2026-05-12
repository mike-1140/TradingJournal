<?php

namespace App\Livewire;

use App\Services\UserSwitchService;
use App\Traits\UsesTradingDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Models\Campaign;
use App\Models\TradeOpen;
use App\Models\TradeClose;
use App\Traits\ManagesEnvironment;
use Carbon\Carbon;
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

    // KPI-Boxen: fixe Perioden
    public $monthProfit;
    public $prevMonthProfit;
    public $ytdProfit;
    public $activeCampaignsCount;
    public $capitalAtRisk;
    public $avgProfitPerTrade;
    protected $listeners = [
        'time-range-changed' => 'onTimeRangeChanged',
    ];

    public function switchUser($id)
    {
        // flow_trace();
        UserSwitchService::switchUser($id);

        // Hard refresh, damit alles neu geladen wird
        return redirect()->route('dashboard');
    }

    public function onTimeRangeChanged()
    {
        unset($this->startDate);
        unset($this->endDate);
        $this->loadStatistics();
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


            // P&L aktueller Monat (fix, unabhängig vom Zeitraumfilter)
            $monthStart = Carbon::now()->startOfMonth();
            $monthEnd   = Carbon::now()->endOfMonth();
            $this->monthProfit = (float) TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereBetween('dateTimeClose', [$monthStart, $monthEnd])
                ->sum('profit');

            // P&L Vormonat (für Trend-Pfeil)
            $prevMonthStart = Carbon::now()->subMonth()->startOfMonth();
            $prevMonthEnd   = Carbon::now()->subMonth()->endOfMonth();
            $this->prevMonthProfit = (float) TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereBetween('dateTimeClose', [$prevMonthStart, $prevMonthEnd])
                ->sum('profit');

            // P&L YTD (fix, immer ab 01.01.)
            $this->ytdProfit = (float) TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->where('dateTimeClose', '>=', Carbon::now()->startOfYear())
                ->sum('profit');

            // Aktive Kampagnen + gebundenes Kapital
            $this->activeCampaignsCount = Campaign::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->where('status', 'ACTIVE')
                ->count();

            $this->capitalAtRisk = abs((float) TradeOpen::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->where('processed', 0)
                ->where('netCash', '<', 0)
                ->sum('netCash'));

            // Ø P&L pro Trade (zeitraumgefiltert)
            $this->avgProfitPerTrade = $this->closedTradesCount > 0
                ? round($this->totalProfit / $this->closedTradesCount, 2)
                : 0;

            // Spreads Statistiken - NUR die Zahlen, NICHT die Collection!
            $spreadsData = TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotNull('opt_strategyID')
                ->where('opt_strategyID', '!=', '')
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

        // Aktuelle offene Positionen
        $currentOpenTrades = TradeOpen::on($connection)
            ->where('processed', 0)
            ->where('environment', $this->selectedEnvironment)
            ->orderBy('dateTime', 'desc')
            ->limit(10)
            ->get();

        // ===== CHART-DATEN =====

        // 1. Kumulativer P&L YTD (monatlich)
        $ytdRows = TradeClose::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->where('dateTimeClose', '>=', Carbon::now()->startOfYear())
            ->selectRaw("DATE_FORMAT(dateTimeClose, '%Y-%m-01') as month, SUM(profit) as profit")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $running = 0;
        $chartCumulative = $ytdRows->map(function ($row) use (&$running) {
            $running += $row->profit;
            return ['x' => Carbon::parse($row->month)->translatedFormat('M y'), 'y' => round($running, 2)];
        })->values()->toArray();

        // 2. Monatlicher P&L (letzte 12 Monate)
        $chartMonthly = TradeClose::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->where('dateTimeClose', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(dateTimeClose, '%Y-%m-01') as month, ROUND(SUM(profit), 2) as profit")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($row) => ['x' => Carbon::parse($row->month)->translatedFormat('M y'), 'y' => (float) $row->profit])
            ->values()
            ->toArray();

        // 3. P&L nach Spreads (zeitraumgefiltert, alle Strategien)
        $strategyRows = $this->applyTimeFilter(
            TradeClose::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNotNull('opt_strategyName')
                ->where('opt_strategyName', '!=', '')
                ->whereIn('assetCategory', ['OPT', 'FOP']),
            'dateTimeClose'
        )->selectRaw("opt_strategyName as strategy, ROUND(SUM(profit), 2) as profit")
         ->groupBy('opt_strategyName')
         ->orderBy('profit', 'desc')
         ->get();

        $chartStrategyLabels = $strategyRows->pluck('strategy')->toArray();
        // Absolutwerte für Donut-Slicegröße, echte Werte für Tooltip (via chartStrategyActual)
        $chartStrategyValues = $strategyRows->pluck('profit')->map(fn($v) => round(abs((float) $v), 2))->toArray();
        $chartStrategyActual = $strategyRows->pluck('profit')->map(fn($v) => (float) $v)->toArray();

        // Chart-Update an Alpine-Komponenten senden (für Environment-/Zeitraum-Wechsel)
        $this->dispatch('charts-data',
            cumulative:      $chartCumulative,
            monthly:         $chartMonthly,
            strategyLabels:  $chartStrategyLabels,
            strategyValues:  $chartStrategyValues,
            strategyActual:  $chartStrategyActual,
        );

        // Trend P&L Monat vs. Vormonat
        if ($this->prevMonthProfit != 0) {
            $trendPct  = round((($this->monthProfit - $this->prevMonthProfit) / abs($this->prevMonthProfit)) * 100, 1);
            $monthTrend = [
                'direction' => $this->monthProfit >= $this->prevMonthProfit ? 'up' : 'down',
                'label'     => number_format(abs($trendPct), 1, ',', '.') . '% vs. Vormonat',
            ];
        } elseif ($this->monthProfit != 0) {
            $monthTrend = ['direction' => $this->monthProfit > 0 ? 'up' : 'down', 'label' => 'Kein Vormonat'];
        } else {
            $monthTrend = ['direction' => 'neutral', 'label' => 'Noch keine Trades'];
        }

        // Zeitraum-Label für P&L Zeitraum KPI
        $zeitraumLabel = $this->startDate
            ? 'Seit ' . $this->startDate->format('d.m.Y')
            : 'Alle Daten';

        return view('livewire.dashboard', [
            'currentOpenTrades'   => $currentOpenTrades,
            'monthTrend'          => $monthTrend,
            'zeitraumLabel'       => $zeitraumLabel,
            'chartCumulative'     => $chartCumulative,
            'chartMonthly'        => $chartMonthly,
            'chartStrategyLabels' => $chartStrategyLabels,
            'chartStrategyValues' => $chartStrategyValues,
            'chartStrategyActual' => $chartStrategyActual,
        ]);
    }
}
