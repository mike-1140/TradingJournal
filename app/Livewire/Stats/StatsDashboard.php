<?php

namespace App\Livewire\Stats;

use Livewire\Component;
use App\Models\CampaignLedger;
use App\Traits\ManagesEnvironment;
use Carbon\Carbon;

class StatsDashboard extends Component
{
    use ManagesEnvironment;

    public $viewType = 'month';
    public $offset = 0;
    public $limit = 6;

    public function mount()
    {
        $this->mountManagesEnvironment();
    }

    public function changeOffset($delta)
    {
        // Tagesansicht: immer 7 Tage (1 Woche) vor/zurück
        $step = $this->viewType === 'day' ? 7 : 1;
        $newOffset = $this->offset + ($delta * $step);

        if ($newOffset > 0) {
            $this->offset = 0;
        } else {
            $this->offset = $newOffset;
        }
    }

    public function setViewType($type)
    {
        if (in_array($type, ['day', 'week', 'month', 'year'])) {
            $this->viewType = $type;
            $this->offset = 0;
        }

        $this->limit = match($type) {
            'day'   => 21,
            'week'  => 13,
            'month' => 6,
            'year'  => 3,
            default => 6
        };
    }

    public function resetOffset()
    {
        $this->offset = 0;
    }

    public function loadMore()
    {
        $this->limit += 7;
    }

    public function getViewTypeLabel(): string
    {
        return match($this->viewType) {
            'day'   => 'Woche',
            'week'  => 'Woche',
            'month' => 'Monat',
            'year'  => 'Jahr',
            default => $this->viewType
        };
    }

    public function render()
    {
        $periods = [];
        $totalSum = [
            'pnl'              => 0,
            'proceeds'         => 0,
            'commission'       => 0,
            'turnover'         => 0,
            'trade_count'      => 0,
            'win_count'        => 0,
            'loss_count'       => 0,
            'active_day_count' => 0,
        ];
        $totalToFetch = $this->limit + 1;

        for ($i = 0; $i > -$totalToFetch; $i--) {
            $currentOffset = $this->offset + $i;
            $date  = Carbon::now()->add($currentOffset, $this->viewType . 's');
            $start = (clone $date)->startOf($this->viewType);
            $end   = (clone $date)->endOf($this->viewType);

            $periods[] = [
                'metrics' => $this->getMetrics($start, $end),
                'label'   => $this->getPeriodLabel($start, $end),
                'offset'  => $currentOffset,
                'end'     => $end,
            ];
        }

        $displayPeriods = [];
        for ($j = 0; $j < $this->limit; $j++) {
            $current  = $periods[$j];
            $previous = $periods[$j + 1];

            $isWeekend   = $this->viewType === 'day' && $current['end']->isWeekend();
            $hasNoTrades = (int)($current['metrics']->trade_count ?? 0) === 0;
            $isInactive  = $this->viewType === 'day' && ($isWeekend || $hasNoTrades);

            if (!$isInactive) {
                $totalSum['pnl']              += $current['metrics']->pnl;
                $totalSum['proceeds']         += $current['metrics']->proceeds;
                $totalSum['commission']       += $current['metrics']->commission;
                $totalSum['turnover']         += $current['metrics']->turnover;
                $totalSum['trade_count']      += $current['metrics']->trade_count;
                $totalSum['win_count']        += $current['metrics']->win_count ?? 0;
                $totalSum['loss_count']       += $current['metrics']->loss_count ?? 0;
                $totalSum['active_day_count'] += $this->viewType === 'day' ? 1 : 0;
            }

            $displayPeriods[] = [
                'label'       => $current['label'],
                'metrics'     => $current['metrics'],
                'pf'          => $this->calculateProfitFactor($current['metrics']),
                'isCurrent'   => ($current['offset'] === 0),
                'pnl_diff'    => $this->viewType !== 'day'
                    ? ($current['metrics']->pnl ?? 0) - ($previous['metrics']->pnl ?? 0)
                    : null,
                'cumulative'  => $this->viewType === 'day'
                    ? $this->getCumulativePnl($current['end'])
                    : null,
                'avg_trade'   => $current['metrics']->trade_count > 0
                    ? $current['metrics']->pnl / $current['metrics']->trade_count
                    : 0,
                'isWeekend'   => $isWeekend,
                'hasNoTrades' => $hasNoTrades,
            ];
        }

        // Day-View: Diff gegen letzten Handelstag berechnen (überspringt WE + kein Handel)
        if ($this->viewType === 'day') {
            $lastTradingPnl = null;
            $extra = $periods[$this->limit] ?? null;
            if ($extra && (int)($extra['metrics']->trade_count ?? 0) > 0) {
                $lastTradingPnl = (float)($extra['metrics']->pnl ?? 0);
            }
            for ($k = count($displayPeriods) - 1; $k >= 0; $k--) {
                if (!$displayPeriods[$k]['isWeekend'] && !$displayPeriods[$k]['hasNoTrades']) {
                    $displayPeriods[$k]['pnl_diff'] = $lastTradingPnl !== null
                        ? (float)($displayPeriods[$k]['metrics']->pnl ?? 0) - $lastTradingPnl
                        : null;
                    $lastTradingPnl = (float)($displayPeriods[$k]['metrics']->pnl ?? 0);
                }
            }
        }

        return view('livewire.stats.stats-dashboard', [
            'periods' => $displayPeriods,
            'totals'  => $totalSum,
        ]);
    }

    private function getMetrics(Carbon $start, Carbon $end)
    {
        return CampaignLedger::where('environment', $this->selectedEnvironment)
            ->whereBetween('dateTime', [$start, $end])
            ->selectRaw('
                SUM(realized_pnl) as pnl,
                SUM(proceeds) as proceeds,
                SUM(commission) as commission,
                SUM(netcash) as turnover,
                SUM(CASE WHEN realized_pnl > 0 THEN realized_pnl ELSE 0 END) as gross_profit,
                SUM(CASE WHEN realized_pnl < 0 THEN ABS(realized_pnl) ELSE 0 END) as gross_loss,
                SUM(CASE WHEN realized_pnl > 0 THEN 1 ELSE 0 END) as win_count,
                SUM(CASE WHEN realized_pnl < 0 THEN 1 ELSE 0 END) as loss_count,
                COUNT(id) as trade_count
            ')->first();
    }

    private function getCumulativePnl(Carbon $end): float
    {
        $monthStart = (clone $end)->startOfMonth();
        return (float)(CampaignLedger::where('environment', $this->selectedEnvironment)
            ->whereBetween('dateTime', [$monthStart, $end])
            ->sum('realized_pnl') ?? 0);
    }

    public function calculateProfitFactor($metrics)
    {
        if (!$metrics || $metrics->gross_loss == 0) {
            return $metrics && $metrics->gross_profit > 0 ? 99.9 : 0;
        }
        return $metrics->gross_profit / $metrics->gross_loss;
    }

    private function getPeriodLabel(Carbon $start, Carbon $end): string
    {
        return match($this->viewType) {
            'day'   => $start->translatedFormat('D, d.m.Y'),
            'week'  => $this->formatWeeklyRange($start, $end),
            'month' => $start->translatedFormat('F Y'),
            'year'  => $start->format('Y'),
        };
    }

    private function formatWeeklyRange(Carbon $start, Carbon $end): string
    {
        $kw = $start->format('W');

        if ($start->month === $end->month) {
            return "KW $kw (" . $start->format('d.') . " - " . $end->translatedFormat('d. F Y') . ")";
        }

        return "KW $kw (" . $start->translatedFormat('d. M.') . " - " . $end->translatedFormat('d. M. Y') . ")";
    }
}
