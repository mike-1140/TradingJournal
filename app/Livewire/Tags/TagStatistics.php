<?php

namespace App\Livewire\Tags;

use Livewire\Component;
use App\Models\Tag;
use App\Models\TradeClose;
use App\Models\TradeTagPivot;
use App\Traits\ManagesEnvironment;
use Illuminate\Support\Facades\DB;

class TagStatistics extends Component
{
    use ManagesEnvironment;

    public function mount()
    {
        $this->mountManagesEnvironment();
    }

    public function render()
    {
        if (!$this->selectedEnvironment) {
            $this->selectedEnvironment = session('trading_environment', 'PAPER');
        }

        $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);

        // ✅ NEUE LOGIK: Starte von TradeTagPivot (ist in Trading-DB)
        $tagStats = collect();

        foreach (Tag::active()->get() as $tag) {
            // ✅ Hole alle Trade-IDs mit diesem Tag aus Pivot-Tabelle
            $tradeIds = TradeTagPivot::on($connection)
                ->where('tag_id', $tag->id)
                ->where('trade_type', 'closed')
                ->where('environment', $this->selectedEnvironment)
                ->pluck('trade_id');

            if ($tradeIds->count() > 0) {
                // ✅ Hole Trades basierend auf den IDs
                $trades = TradeClose::on($connection)
                    ->where('environment', $this->selectedEnvironment)
                    ->whereIn('tradeIDClose', $tradeIds)
                    ->get();

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
