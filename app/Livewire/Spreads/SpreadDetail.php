<?php

namespace App\Livewire\Spreads;

use App\Traits\FilterableByTime;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use App\Models\TradeClose;
use App\Models\TradeOpen;
use App\Models\PsychologyEntry;
use App\Traits\ManagesEnvironment;
use Livewire\WithPagination;

class SpreadDetail extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    #[Locked]
    public $opt_strategyID;
    public $perPage = 20;
    public $status = 'closed'; // open or closed

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount($opt_strategyID)
    {
        $this->mountFilterableByTime();
        $this->mountManagesEnvironment();
        $this->opt_strategyID = $opt_strategyID;

        // Status aus Query-String holen
        $this->status = request()->query('status', 'closed');

    }

    public function changeEnvironment($environment)
    {
        $this->selectedEnvironment = $environment;
        session(['trading_environment' => $environment]);
        // Kein Reload nötig - computed properties laden automatisch
    }

    /**
     * ✅ Computed Property für Legs
     */
    #[Computed]
    public function legs()
    {
        if (!$this->selectedEnvironment) {
            $this->selectedEnvironment = session('trading_environment', 'PAPER');
        }

        if ($this->status === 'open') {
            $legsQuery = TradeOpen::onEnvironment($this->selectedEnvironment)
                ->where('environment', $this->selectedEnvironment)
                ->where('processed', 0)
                ->where('opt_strategyID', $this->opt_strategyID);
            $legs = $legsQuery->get();
        } else {
            $legsQuery = TradeClose::onEnvironment($this->selectedEnvironment)
                ->where('environment', $this->selectedEnvironment)
                ->where('opt_strategyID', $this->opt_strategyID);
            $legsQuery = $this->applyTimeFilter($legsQuery, 'dateTimeClose');
            $legs = $legsQuery->get();
        }

        if ($legs->isEmpty()) {
            abort(404, 'Spread nicht gefunden');
        }

        // Sortiere nach Strike
        return $legs->sortBy(function($leg) {
            return $leg->strike ?? 0;
        });
    }

    /**
     * ✅ Computed Property für Spread Info
     */
    #[Computed]
    public function spread()
    {
        $legs = $this->legs;

        return [
            'opt_strategyName' => $legs->first()->opt_strategyName,
            'underlyingSymbol' => $legs->first()->underlyingSymbol,
            'leg_count' => $legs->count(),
        ];
    }

    /**
     * Stock-Trades aus Assignment/Exercise eines Legs (Zwangskauf/-verkauf zum Strike,
     * meist sofort glattgestellt), verknüpft über linkID (siehe xml_proj: book_trades.py
     * + migrate_backfill_assignment_linkid.py). Eigene Query, da anderer assetCategory
     * (STK) als die Options-Legs und daher nicht über opt_strategyID auffindbar.
     */
    #[Computed]
    public function assignmentTrades()
    {
        if ($this->status !== 'closed') {
            return collect();
        }

        $linkIds = $this->legs->pluck('linkID')->filter()->unique()->values();
        if ($linkIds->isEmpty()) {
            return collect();
        }

        return TradeClose::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment)
            ->where('assetCategory', 'STK')
            ->whereIn('linkID', $linkIds)
            ->get();
    }

    /**
     * ✅ Computed Property für Statistiken
     */
    #[Computed]
    public function statistics()
    {
        $legs = $this->legs;

        if ($this->status === 'closed') {
            $legsProfit = (float) $legs->sum('profit');
            $legsCommission = (float) $legs->sum('ibCommission');
            $assignmentProfit = (float) $this->assignmentTrades->sum('profit');
            $assignmentCommission = (float) $this->assignmentTrades->sum('ibCommission');

            return [
                'total_profit' => $legsProfit + $assignmentProfit,
                'total_commission' => $legsCommission + $assignmentCommission,
                'net_profit' => ($legsProfit + $assignmentProfit) - ($legsCommission + $assignmentCommission),
                'avg_days_held' => $legs->avg('daysHeld'),
                'earliest_open' => $legs->min('dateTimeOpen'),
                'latest_close' => $legs->max('dateTimeClose'),
                'total_premium_received' => $legs->where('buySellOpen', 'SELL')->sum('netCashOpen'),
                'total_premium_paid' => abs($legs->where('buySellOpen', 'BUY')->sum('netCashOpen')),
                'options_profit' => $legsProfit,
                'assignment_stock_profit' => $assignmentProfit,
                'assignment_stock_commission' => $assignmentCommission,
            ];
        } else {
            return [
                'total_premium' => $legs->sum('netCash'),
                'total_commission' => $legs->sum('ibCommission'),
                'earliest_date' => $legs->min('dateTime'),
                'premium_received' => $legs->where('buySell', 'SELL')->sum('netCash'),
                'premium_paid' => abs($legs->where('buySell', 'BUY')->sum('netCash')),
            ];
        }
    }

    public function render()
    {
        $connection = TradeClose::getConnectionForEnvironment($this->selectedEnvironment);
        $psychEntry = PsychologyEntry::tableExistsFor($connection)
            ? PsychologyEntry::on($connection)
                ->where('opt_strategyID', $this->opt_strategyID)
                ->where('trade_type', $this->status === 'open' ? 'open_spread' : 'spread')
                ->where('environment', $this->selectedEnvironment)
                ->with('complianceResults.rule')
                ->first()
            : null;

        return view('livewire.spreads.spread-detail', [
            'psychEntry' => $psychEntry,
        ]);
    }

}
