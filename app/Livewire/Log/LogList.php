<?php

namespace App\Livewire\Log;

use App\Models\CampaignLedger;
use App\Models\PsychologyComplianceResult;
use App\Models\PsychologyEntry;
use App\Models\TradePlan;
use App\Support\CampaignOpenings;
use App\Traits\ManagesEnvironment;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class LogList extends Component
{
    use WithPagination, ManagesEnvironment;

    public string $filterStatus = 'all';
    public string $search = '';
    public int $perPage = 25;

    protected $queryString = [
        'filterStatus' => ['except' => 'all'],
        'search'       => ['except' => ''],
    ];

    protected $listeners = [
        'environment-changed' => '$refresh',
        'opening-log-saved'   => '$refresh',
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
        // Psychologie derselben Eröffnung gehört zum Eintrag
        if ($plan->campaign_id && $plan->opened_at && PsychologyEntry::tableExistsFor($connection)) {
            $entries = PsychologyEntry::on($connection)->where('campaign_id', $plan->campaign_id)
                ->where('opened_at', $plan->opened_at)->pluck('id');
            PsychologyComplianceResult::on($connection)->whereIn('entry_id', $entries)->delete();
            PsychologyEntry::on($connection)->whereIn('id', $entries)->delete();
        }
        $plan->legs()->delete();
        $plan->delete();
    }

    public function render()
    {
        $connection = TradePlan::getConnectionForEnvironment($this->selectedEnvironment);

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

        $psychology = PsychologyEntry::tableExistsFor($connection)
            ? PsychologyEntry::on($connection)->with('complianceResults')
                ->where('environment', $this->selectedEnvironment)->orderBy('trade_date', 'desc')->get()
            : collect();
        $entryKey = fn($campaignId, $openedAt) => $campaignId . '|' . $openedAt;
        $planKeys = $entries->filter(fn($p) => $p->opening_at)->map(fn($p) => $entryKey($p->campaign_id, $p->opening_at))->flip();
        $psychologyByKey = $psychology->filter(fn($e) => $e->opened_at)
            ->keyBy(fn($e) => $entryKey($e->campaign_id, $e->opened_at->format('Y-m-d H:i:s')));

        return view('livewire.log.log-list', [
            'logs'       => $logs,
            'psychologyByKey' => $psychologyByKey,
            'unassignedPsychology' => $psychology->reject(fn($e) => $e->opened_at
                && $planKeys->has($entryKey($e->campaign_id, $e->opened_at->format('Y-m-d H:i:s'))))->values(),
            'statistics' => $statistics,
        ]);
    }

    /** Setzt display_status, display_pnl, display_legs und opening_at: bei verknüpften Einträgen aus dem Ledger. */
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
            $plan->opening_at     = $opening['opened_at'] ?? null;
        });
    }
}
