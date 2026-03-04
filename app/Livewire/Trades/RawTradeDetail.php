<?php

namespace App\Livewire\Trades;

use Livewire\Component;
use App\Models\TradeRaw;
use App\Traits\ManagesEnvironment;
use Illuminate\Support\Collection;

class RawTradeDetail extends Component
{
    use ManagesEnvironment;

    public TradeRaw $trade;
    public $tradeId;

    public function mount($id)
    {
        $this->tradeId = $id;

        // Environment aus Session laden (über Trait)
        $this->mountManagesEnvironment();

        // Suche NUR im aktuell gewählten Environment
        $this->trade = TradeRaw::onEnvironment($this->selectedEnvironment)->find($id);

        // Wenn nicht gefunden, klare 404-Meldung
        if (!$this->trade) {
            abort(404, "Trade #{$id} nicht gefunden im {$this->selectedEnvironment} Environment");
        }
    }

    public function render()
    {
        // Verwende die richtige Connection basierend auf dem Trade Environment
        $connection = TradeRaw::getConnectionForEnvironment($this->trade->environment);

        // Finde andere Raw Trades mit gleichem tradeID oder linkID
        $relatedRawTrades = collect();
        if ($this->trade->tradeID || $this->trade->linkID) {
            $relatedRawTrades = TradeRaw::on($connection)
                ->where('environment', $this->trade->environment)
                ->where(function($query) {
                    if ($this->trade->tradeID) {
                        $query->where('tradeID', $this->trade->tradeID);
                    }
                    if ($this->trade->linkID) {
                        $query->orWhere('linkID', $this->trade->linkID);
                    }
                })
                ->where('id', '!=', $this->trade->id)
                ->orderBy('dateTime', 'desc')
                ->get();
        }

        return view('livewire.trades.raw-trade-detail', [
            'relatedRawTrades' => $relatedRawTrades,
        ]);
    }
}
