<?php

namespace App\Livewire\Trades;

use Livewire\Component;
use App\Models\TradeOpen;
use App\Models\TradeRaw;
use App\Traits\ManagesEnvironment;
use Illuminate\Support\Collection;

class OpenStocksDetail extends Component
{
    use ManagesEnvironment;

    public TradeOpen $trade;
    public $tradeId;

    public function mount($id)
    {
        $this->tradeId = $id;

        // Environment aus Session laden (über Trait)
        $this->mountManagesEnvironment();

        // Suche NUR im aktuell gewählten Environment
        $this->trade = TradeOpen::onEnvironment($this->selectedEnvironment)->find($id);

        // Wenn nicht gefunden, klare 404-Meldung
        if (!$this->trade) {
            abort(404, "Trade #{$id} nicht gefunden im {$this->selectedEnvironment} Environment");
        }
    }

    public function render()
    {
        // Verwende die richtige Connection basierend auf dem Trade Environment
        $connection = TradeOpen::getConnectionForEnvironment($this->trade->environment);

        // Finde zugehörige Raw Trades
        $relatedRawTrades = TradeRaw::on($connection)
            ->where('environment', $this->trade->environment)
            ->where(function($query) {
                $query->where('tradeID', $this->trade->tradeID)
                    ->orWhere('linkID', $this->trade->linkID);
            })
            ->orderBy('dateTime', 'desc')
            ->get();

        // Finde andere Trades mit gleichem linkID (für Strategien)
        $relatedTrades = collect();
        if ($this->trade->linkID) {
            $relatedTrades = TradeOpen::on($connection)
                ->where('environment', $this->trade->environment)
                ->where('linkID', $this->trade->linkID)
                ->where('id', '!=', $this->trade->id)
                ->get();
        }

        return view('livewire.trades.open-stocks-detail', [
            'relatedRawTrades' => $relatedRawTrades,
            'relatedTrades' => $relatedTrades,
        ]);
    }
}
