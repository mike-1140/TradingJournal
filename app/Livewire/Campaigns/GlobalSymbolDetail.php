<?php

namespace App\Livewire\Campaigns;

use App\Traits\FilterableByTime;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CampaignLedger;
use App\Traits\ManagesEnvironment;

class GlobalSymbolDetail extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $symbol;
    public $perPage = 20;

    protected $queryString = [
        'globalPage' => ['except' => 1, 'as' => 'gp'],
    ];

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount($symbol)
    {
        $this->mountFilterableByTime();
        $this->mountManagesEnvironment();
        $this->symbol = strtoupper($symbol);
        $this->resetPage('globalPage');
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
        // Query über das Symbol (via Relationship zur Campaign)
        $query = CampaignLedger::where('environment', $this->selectedEnvironment);
        $query = $this->applyTimeFilter($query, 'dateTime');

        $query = $query->whereHas('campaign', function($q) {
                $q->where('symbol', $this->symbol);
            });

        // Metriken aggregieren (Bedingte Summierung für Aktien/Optionen)
        $metrics = (clone $query)->selectRaw('
            SUM(netcash) as total_netcash,
            SUM(proceeds) as total_proceeds,
            SUM(commission) as total_commission,
            SUM(realized_pnl) as total_pnl,
            SUM(inventory_delta) as total_shares,
            SUM(CASE WHEN assetCategory = "STK" THEN inventory_delta ELSE 0 END) as shares_balance,
            SUM(CASE WHEN assetCategory = "OPT" THEN inventory_delta ELSE 0 END) as options_balance,
            COUNT(DISTINCT campaign_id) as campaign_count
        ')->first();

        $ledgerEntries = $query->orderBy('dateTime', 'desc')
            // ->paginate($this->perPage, ['*'], 'globalPage');
            ->paginate(100, ['*'], 'ledgerPage');

        return view('livewire.campaigns.global-symbol-detail', [
            'ledgerEntries' => $ledgerEntries,
            'metrics' => $metrics
        ]);
    }
}
