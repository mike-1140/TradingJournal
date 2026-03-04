<?php

namespace App\Livewire\Trades;

use Livewire\Component;
use App\Models\TradeClose;
use App\Models\TradeRaw;
use App\Traits\ManagesEnvironment;
use Illuminate\Support\Collection;

class ClosedOptionsDetail extends Component
{
    use ManagesEnvironment;

    public TradeClose $trade;
    public $tradeId;

    public function mount($id)
    {
        $this->tradeId = $id;

        // Environment aus Session laden (über Trait)
        $this->mountManagesEnvironment();

        // Suche NUR im aktuell gewählten Environment
        $this->trade = TradeClose::onEnvironment($this->selectedEnvironment)->find($id);

        // Wenn nicht gefunden, klare 404-Meldung
        if (!$this->trade) {
            abort(404, "Trade #{$id} nicht gefunden im {$this->selectedEnvironment} Environment");
        }
    }

    public function getBreadcrumbsProperty()
    {
        return [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'speedometer2'],
            ['label' => 'Closed Trades', 'route' => 'trades.closed', 'icon' => 'check-circle'],
            ['label' => $this->trade->symbol, 'icon' => 'info-circle'],
        ];
    }

    public function render()
    {
        // Verwende die richtige Connection basierend auf dem Trade Environment
        $connection = TradeClose::getConnectionForEnvironment($this->trade->environment);

        // Finde zugehörige Raw Trades (Opening und Closing)
        $relatedRawTrades = TradeRaw::on($connection)
            ->where('environment', $this->trade->environment)
            ->where(function($query) {
                $query->where('tradeID', $this->trade->tradeIDOpen)
                    ->orWhere('tradeID', $this->trade->tradeIDClose);

                // Falls linkID vorhanden, auch diese Trades einbeziehen
                if ($this->trade->linkID) {
                    $query->orWhere('linkID', $this->trade->linkID);
                }
            })
            ->orderBy('dateTime', 'desc')
            ->get();

        // Finde andere Trades mit gleichem linkID (für Strategien)
        $relatedTrades = collect();
        if ($this->trade->linkID) {
            $relatedTrades = TradeClose::on($connection)
                ->where('environment', $this->trade->environment)
                ->where('linkID', $this->trade->linkID)
                ->where('id', '!=', $this->trade->id)
                ->get();
        }

        return view('livewire.trades.closed-trade-detail', [
            'relatedRawTrades' => $relatedRawTrades,
            'relatedTrades' => $relatedTrades,
        ]);
    }
}
