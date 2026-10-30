<?php

namespace App\Livewire\Log;

use App\Models\Campaign;
use App\Models\CampaignLedger;
use App\Models\TradePlan;
use App\Support\CampaignOpenings;
use App\Traits\ManagesEnvironment;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
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
        $plan = TradePlan::on($connection)
            ->where('id', $planId)
            ->where('environment', $this->selectedEnvironment)
            ->first();
        if (!$plan) {
            return;
        }
        $ledger = CampaignLedger::on($connection)->where('campaign_id', $campaignId)
            ->orderBy('dateTime')->orderBy('id')->get();
        $taken = TradePlan::on($connection)->where('campaign_id', $campaignId)->whereNotNull('opened_at')->get()
            ->map(fn($p) => $p->opened_at->format('Y-m-d H:i:s'))->all();
        $opening = CampaignOpenings::nearest(CampaignOpenings::fromLedger($ledger)['openings'], $plan->date_open, $taken);
        $plan->update(['campaign_id' => $campaignId, 'opened_at' => $opening['opened_at'] ?? null]);
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

        // Status und P&L verknüpfter Einträge kommen aus dem Ledger — daher Filter und Summen hier
        $entries = $this->withLedgerValues(
            TradePlan::on($connection)
                ->withCount('legs')
                ->where('environment', $this->selectedEnvironment)
                ->orderBy('date_open', 'desc')
                ->get(),
            $connection
        );

        $statistics = [
            'open_count'   => $entries->where('display_status', 'open')->count(),
            'closed_count' => $entries->where('display_status', 'closed')->count(),
            'realized_pnl' => $entries->where('display_status', 'closed')->sum('display_pnl'),
        ];

        $filtered = $entries
            ->when($this->filterStatus !== 'all', fn($c) => $c->where('display_status', $this->filterStatus))
            ->when($this->search, fn($c) => $c->filter(fn($p) => stripos($p->underlying, $this->search) !== false))
            ->values();

        $page = $this->getPage();
        $logs = new LengthAwarePaginator(
            $filtered->forPage($page, $this->perPage)->values(),
            $filtered->count(),
            $this->perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('livewire.log.log-list', [
            'logs'                 => $logs,
            'statistics'           => $statistics,
            'unloggedCampaigns'    => $unloggedCampaigns,
            'candidatesByCampaign' => $candidatesByCampaign,
        ]);
    }

    /** Setzt display_status, display_pnl und display_legs: bei verknüpften Einträgen aus dem Ledger. */
    private function withLedgerValues($plans, string $connection)
    {
        $ledgers = CampaignLedger::on($connection)
            ->whereIn('campaign_id', $plans->pluck('campaign_id')->filter()->unique())
            ->orderBy('dateTime')->orderBy('id')->get()
            ->groupBy('campaign_id');

        return $plans->each(function ($plan) use ($ledgers) {
            $openings = isset($ledgers[$plan->campaign_id])
                ? CampaignOpenings::fromLedger($ledgers[$plan->campaign_id])['openings'] : [];
            $opening = $plan->opened_at
                ? CampaignOpenings::find($openings, $plan->opened_at->format('Y-m-d H:i:s'))
                : ($openings[0] ?? null);

            $plan->display_status = $opening ? ($opening['state'] === 'closed' ? 'closed' : 'open') : $plan->status;
            $plan->display_pnl    = $opening ? $opening['pnl'] : $plan->realized_pnl;
            $plan->display_legs   = $opening ? count($opening['legs']) : $plan->legs_count;
        });
    }
}
