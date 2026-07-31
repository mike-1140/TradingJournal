<?php

namespace App\Livewire\Log;

use App\Models\Campaign;
use App\Models\TradePlan;
use App\Traits\ManagesEnvironment;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class LogList extends Component
{
    use WithPagination, ManagesEnvironment;

    public string $filterStatus = 'all';
    public string $search = '';
    public int $perPage = 25;

    // "Nicht dokumentiert"-Sektion: Kampagnen ohne Log-Eintrag
    public int $unloggedWindowDays = 30;

    protected $queryString = [
        'filterStatus' => ['except' => 'all'],
        'search'       => ['except' => ''],
    ];

    protected $listeners = [
        'environment-changed' => '$refresh',
    ];

    public function mount()
    {
        $this->mountManagesEnvironment();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function deleteEntry(int $id): void
    {
        $connection = TradePlan::getConnectionForEnvironment($this->selectedEnvironment);
        $plan = TradePlan::on($connection)->findOrFail($id);
        $plan->legs()->delete();
        $plan->delete();
    }

    /**
     * Verknüpft einen bereits vorab geplanten (unverknüpften) Log-Eintrag nachträglich
     * mit einer echten importierten Kampagne — nur nach Bestätigung durch den User,
     * kein automatisches Linking.
     */
    public function linkPlanToCampaign(int $planId, int $campaignId): void
    {
        $connection = TradePlan::getConnectionForEnvironment($this->selectedEnvironment);
        TradePlan::on($connection)
            ->where('id', $planId)
            ->where('environment', $this->selectedEnvironment)
            ->update(['campaign_id' => $campaignId]);
    }

    public function render()
    {
        $connection = TradePlan::getConnectionForEnvironment($this->selectedEnvironment);

        // --- "Nicht dokumentiert"-Sektion ---
        $linkedCampaignIds = TradePlan::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->whereNotNull('campaign_id')
            ->pluck('campaign_id');

        $cutoff = Carbon::now()->subDays($this->unloggedWindowDays);

        $unloggedCampaigns = Campaign::on($connection)
            ->where('environment', $this->selectedEnvironment)
            ->whereNotIn('id', $linkedCampaignIds)
            ->where(function ($q) use ($cutoff) {
                $q->where('status', '!=', 'CLOSED')
                    ->orWhere('start_date', '>=', $cutoff);
            })
            ->orderByDesc('start_date')
            ->get();

        // Für jede unlogged Kampagne: unverknüpfte Vorab-Pläne im Zeitfenster als Matching-Vorschlag
        $candidatesByCampaign = [];
        foreach ($unloggedCampaigns as $c) {
            if (!$c->start_date) {
                $candidatesByCampaign[$c->id] = collect();
                continue;
            }
            $candidatesByCampaign[$c->id] = TradePlan::on($connection)
                ->where('environment', $this->selectedEnvironment)
                ->whereNull('campaign_id')
                ->where('underlying', $c->symbol)
                ->whereBetween('date_open', [
                    Carbon::parse($c->start_date)->subDays(5)->format('Y-m-d'),
                    Carbon::parse($c->start_date)->addDays(5)->format('Y-m-d'),
                ])
                ->get();
        }

        $query = TradePlan::on($connection)
            ->withCount('legs')
            ->where('environment', $this->selectedEnvironment);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->search) {
            $query->where('underlying', 'like', '%' . $this->search . '%');
        }

        $logs = $query->orderBy('date_open', 'desc')->paginate($this->perPage);

        $baseStats = TradePlan::on($connection)->where('environment', $this->selectedEnvironment);
        $statistics = [
            'open_count'   => (clone $baseStats)->where('status', 'open')->count(),
            'closed_count' => (clone $baseStats)->where('status', 'closed')->count(),
            'realized_pnl' => (clone $baseStats)->where('status', 'closed')->sum('realized_pnl'),
        ];

        return view('livewire.log.log-list', [
            'logs'                 => $logs,
            'statistics'           => $statistics,
            'unloggedCampaigns'    => $unloggedCampaigns,
            'candidatesByCampaign' => $candidatesByCampaign,
        ]);
    }
}
