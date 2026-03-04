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
    public $limit = 6; // Standardwert: 6 Monate

    public function mount()
    {
        $this->mountManagesEnvironment();
    }

    public function changeOffset($delta)
    {
        $newOffset = $this->offset + $delta;

        // Verhindert Vorwärtsblättern über den aktuellen Zeitpunkt hinaus
        if ($newOffset > 0) {
            $this->offset = 0;
        } else {
            $this->offset = $newOffset;
        }
    }

    public function setViewType($type)
    {
        if (in_array($type, ['week', 'month', 'year'])) {
            $this->viewType = $type;
            $this->offset = 0; // Zurück zum aktuellen Zeitraum bei Ansichtswechsel
        }

        // Automatisches Setzen des Limits je nach Typ
        $this->limit = match($type) {
            'week'  => 13,
            'month' => 6,
            'year'  => 3,
            default => 6
        };
    }

// Falls du den "Heute" Button nutzt:
    public function resetOffset()
    {
        $this->offset = 0;
    }

    public function getViewTypeLabel()
    {
        return match($this->viewType) {
            'week' => 'Woche',
            'month' => 'Monat',
            'year' => 'Jahr',
            default => $this->viewType
        };
    }

    public function render()
    {
        $periods = [];
        $totalSum = [
            'pnl' => 0,
            'proceeds' => 0,
            'commission' => 0,
            'turnover' => 0,
            'trade_count' => 0
        ];
        $totalToFetch = $this->limit + 1;

        // Wir laden 4 Zeiträume, um für den 3. Zeitraum auch einen Vergleich zum 4. zu haben
        for ($i = 0; $i > -$totalToFetch; $i--) {
            $currentOffset = $this->offset + $i;
            $date = Carbon::now()->add($currentOffset, $this->viewType . 's');
            $start = (clone $date)->startOf($this->viewType);
            $end = (clone $date)->endOf($this->viewType);

            $periods[] = [
                'metrics' => $this->getMetrics($start, $end),
                'label' => $this->getPeriodLabel($start, $end),
                'offset' => $currentOffset
            ];
        }

        // Jetzt berechnen wir die Trends für die ersten 3
        $displayPeriods = [];
        for ($j = 0; $j < $this->limit; $j++) {
            $current = $periods[$j];
            $previous = $periods[$j + 1]; // Der Zeitraum davor

            // Summen-Logik (nur für die sichtbaren Zeiträume)
            $totalSum['pnl'] += $current['metrics']->pnl;
            $totalSum['proceeds'] += $current['metrics']->proceeds;
            $totalSum['commission'] += $current['metrics']->commission;
            $totalSum['turnover'] += $current['metrics']->turnover;
            $totalSum['trade_count'] += $current['metrics']->trade_count;

            $displayPeriods[] = [
                'label' => $current['label'],
                'metrics' => $current['metrics'],
                'pf' => $this->calculateProfitFactor($current['metrics']),
                'isCurrent' => ($current['offset'] === 0),
                // Trend-Berechnung
                'pnl_trend' => $current['metrics']->pnl <=> $previous['metrics']->pnl, // 1, 0, oder -1
            ];
        }

        return view('livewire.stats.stats-dashboard', [
            'periods' => $displayPeriods,
            'totals' => $totalSum
        ]);
    }

    private function getMetrics($start, $end)
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
                COUNT(id) as trade_count
            ')->first();
    }

    // Hilfsfunktion für das Blade, um den Profit Factor zu berechnen
    public function calculateProfitFactor($metrics)
    {
        if (!$metrics || $metrics->gross_loss == 0) {
            return $metrics && $metrics->gross_profit > 0 ? 99.9 : 0;
        }
        return $metrics->gross_profit / $metrics->gross_loss;
    }

    private function getPeriodLabel($start, $end)
    {
        return match($this->viewType) {
            'week' => $this->formatWeeklyRange($start, $end),
            'month' => $start->translatedFormat('F Y'),
            'year' => $start->format('Y'),
        };
    }

    private function formatWeeklyRange($start, $end)
    {
        $kw = $start->format('W'); // Kalenderwoche

        // Fall 1: Woche liegt komplett im gleichen Monat
        if ($start->month === $end->month) {
            return "KW $kw (" . $start->format('d.') . " - " . $end->translatedFormat('d. F Y') . ")";
        }

        // Fall 2: Monatswechsel innerhalb der Woche
        // Beispiel: 29. Jan. - 04. Feb. 2026
        return "KW $kw (" . $start->translatedFormat('d. M.') . " - " . $end->translatedFormat('d. M. Y') . ")";
    }
}
