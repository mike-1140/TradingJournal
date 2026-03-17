<?php

namespace App\Livewire\Tags;

use Livewire\Component;
use App\Models\Tag;
use App\Models\TradeClose;
use App\Models\TradeTagPivot;
use App\Traits\ManagesEnvironment;
use App\Traits\FilterableByTime;

class TagStatistics extends Component
{
    use ManagesEnvironment, FilterableByTime;

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount()
    {
        $this->mountFilterableByTime();
        $this->mountManagesEnvironment();
    }

    public function render()
    {
        $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        $tagStats = collect();

        foreach (Tag::active()->get() as $tag) {
            // Hole alle Trade-IDs (tradeIDOpen) mit diesem Tag aus Pivot-Tabelle
            $tradeIds = TradeTagPivot::on($connection)
                ->where('tag_id', $tag->id)
                ->where('trade_type', 'closed')
                ->where('environment', $this->selectedEnvironment)
                ->pluck('trade_id');

            if ($tradeIds->count() > 0) {
                // tradeIDOpen abgleichen + Zeitfilter anwenden
                $trades = $this->applyTimeFilter(
                    TradeClose::on($connection)
                        ->where('environment', $this->selectedEnvironment)
                        ->whereIn('tradeIDOpen', $tradeIds),
                    'dateTimeClose'
                )->get();

                if ($trades->count() > 0) {
                    $winningTrades = $trades->where('profit', '>', 0)->count();
                    $totalProfit = $trades->sum('profit');
                    $avgProfit = $trades->avg('profit');
                    $avgDaysHeld = $trades->avg('daysHeld');

                    $tagStats->push((object)[
                        'tag' => $tag,
                        'trade_count' => $trades->count(),
                        'win_rate' => round(($winningTrades / $trades->count()) * 100, 1),
                        'total_profit' => $totalProfit,
                        'avg_profit' => $avgProfit,
                        'avg_days_held' => round($avgDaysHeld, 1),
                    ]);
                }
            }
        }

        // Sortiere nach Trade Count
        $tagStats = $tagStats->sortByDesc('trade_count');

        return view('livewire.tags.tag-statistics', [
            'tagStats' => $tagStats,
        ]);
    }
}
