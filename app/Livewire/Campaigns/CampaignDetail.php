<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Traits\FilterableByTime;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CampaignLedger;
use App\Traits\ManagesEnvironment;

class CampaignDetail extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $campaignId;
    public $perPage = 20;
    public Campaign $campaign; // Wir speichern das ganze Modell

    // Das 'lp' (ledger page) sorgt für eine saubere URL und State-Tracking
    protected $queryString = [
        'ledgerPage' => ['except' => 1, 'as' => 'lp'],
    ];

    public function mount($campaignId)
    {
        // flow_log('Mount - LedgerPage:', [$campaignId]);
        $this->mountManagesEnvironment();
        $this->campaignId = $campaignId;
        $this->campaign = Campaign::findOrFail($campaignId);
        $this->resetPage('ledgerPage');
    }

    /**
     * Diese Methode wird von Livewire aufgerufen, wenn die $campaignId
     * von außen (durch den Parent) geändert wird.
     */
    public function updatedCampaignId()
    {
        // flow_log("Reset Ledger Page");

        $this->resetPage('ledgerPage');
    }

    public function getActionBadgeClass($action)
    {
        return match($action) {
            'BUY'        => 'bg-primary text-white',
            'SELL'       => 'bg-warning text-dark',
            'ASSIGNMENT' => 'bg-purple text-white',
            'EXPIRATION' => 'bg-secondary text-white',
            'VIRTUAL'    => 'bg-info text-dark',
            default      => 'bg-light text-dark border',
        };
    }

    public function render()
    {
        // 1. Die Basis-Query für diese Kampagne
        $query = CampaignLedger::where('campaign_id', $this->campaignId)
            ->where('environment', $this->selectedEnvironment);
        $query = $this->applyTimeFilter($query, 'dateTimeClose');

        // 2. Metriken berechnen
        // Wir nutzen SUM(CASE WHEN...) für die spezifischen Asset-Kategorien
        $metrics = (clone $query)->selectRaw('
            SUM(netcash) as total_netcash,
            SUM(proceeds) as total_proceeds,
            SUM(commission) as total_commission,
            SUM(realized_pnl) as total_pnl,
            SUM(inventory_delta) as total_shares,
            SUM(CASE WHEN assetCategory = "STK" THEN inventory_delta ELSE 0 END) as shares_balance,
            SUM(CASE WHEN assetCategory = "OPT" THEN inventory_delta ELSE 0 END) as options_balance
        ')->first();

        // 3. Paginierung für die Tabelle
        $ledgerEntries = $query->orderBy('dateTime', 'desc')
            // ->paginate($this->perPage, ['*'], 'ledgerPage');
            ->paginate(100, ['*'], 'ledgerPage');

        return view('livewire.campaigns.campaign-detail', [
            'ledgerEntries' => $ledgerEntries,
            'metrics' => $metrics
        ]);
    }
}
