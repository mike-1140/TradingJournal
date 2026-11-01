<?php

namespace App\Livewire\Log;

use App\Models\Campaign;
use App\Models\CampaignLedger;
use App\Models\TradePlan;
use App\Models\TradePlanLeg;
use App\Support\CampaignOpenings;
use App\Traits\ManagesEnvironment;
use Livewire\Component;

/**
 * Bearbeiten eines Eintrags, der zu keiner Eröffnung gehört: ohne Kampagne oder mit einem Zeitpunkt,
 * der nicht mehr im Ledger steht. Alle anderen Einträge laufen über OpeningLogModal; alte Links
 * darauf führen zur Kampagne.
 */
class LogForm extends Component
{
    use ManagesEnvironment;

    public ?int $planId = null;
    public ?int $campaignId = null;
    public ?string $openedAt = null;
    public array $campaignCandidates = [];

    // Kopfdaten
    public string $underlying  = '';
    public string $date_open   = '';
    public ?string $price_open = null;
    public ?string $fees_open  = null;
    public ?string $ivp_open   = null;
    public ?string $ivr_open   = null;
    public ?string $strategy      = null;
    public ?string $exit_plan     = null;
    public ?string $rationale     = null;
    public string $status         = 'open';

    // Closing-Daten
    public ?string $date_close    = null;
    public ?string $price_close   = null;
    public ?string $ivp_close     = null;
    public ?string $ivr_close     = null;
    public ?string $premium_close = null;
    public ?string $fees_close    = null;
    public ?string $realized_pnl  = null;
    public ?string $actual_dte = null;
    public ?string $notes      = null;

    // Legs
    public array $legs = [];

    public bool $showClosing = false;

    protected $listeners = [
        'environment-changed' => '$refresh',
    ];

    protected $rules = [
        'underlying'       => 'required|max:20',
        'date_open'        => 'required|date',
        'strategy'         => 'nullable|max:50',
        'exit_plan'        => 'nullable',
        'rationale'        => 'nullable',
        'price_open'       => 'nullable|numeric',
        'fees_open'        => 'nullable|numeric|min:0',
        'ivp_open'         => 'nullable|integer|min:0|max:100',
        'ivr_open'         => 'nullable|integer|min:0|max:100',
        'legs.*.leg_type'  => 'required|max:10',
        'legs.*.dte'       => 'nullable|integer',
        'legs.*.strike'    => 'nullable|numeric',
        'legs.*.delta'     => 'nullable|numeric',
        'legs.*.premium'   => 'nullable|numeric',
        'legs.*.fees'      => 'nullable|numeric|min:0',
        'legs.*.max_risk'  => 'nullable|numeric',
        'realized_pnl'     => 'nullable|numeric',
        'date_close'       => 'nullable|date',
        'price_close'      => 'nullable|numeric',
        'ivp_close'        => 'nullable|integer|min:0|max:100',
        'ivr_close'        => 'nullable|integer|min:0|max:100',
        'premium_close'    => 'nullable|numeric',
        'fees_close'       => 'nullable|numeric|min:0',
        'actual_dte'       => 'nullable|integer',
    ];

    protected $messages = [
        'fees_open.min'   => 'Gebühren bitte als positive Zahl eintragen.',
        'fees_close.min'  => 'Gebühren bitte als positive Zahl eintragen.',
        'legs.*.fees.min' => 'Gebühren bitte als positive Zahl eintragen.',
    ];

    public function mount(int $id): void
    {
        $this->mountManagesEnvironment();
        $this->planId = $id;
        $this->loadPlan($id);

        if ($this->campaignId && $this->belongsToOpening()) {
            $this->redirect(route('campaigns.campaign.detail', $this->campaignId), navigate: true);
            return;
        }

        $this->refreshCampaignCandidates();
    }

    private function connection(): string
    {
        return TradePlan::getConnectionForEnvironment($this->selectedEnvironment);
    }

    private function loadPlan(int $id): void
    {
        $plan = TradePlan::on($this->connection())->with('legs')->findOrFail($id);

        $this->campaignId  = $plan->campaign_id;
        $this->openedAt    = $plan->opened_at?->format('Y-m-d H:i:s');
        $this->underlying  = $plan->underlying;
        $this->date_open   = $plan->date_open->format('Y-m-d');
        $this->price_open  = $plan->price_open;
        $this->fees_open   = $plan->fees_open;
        $this->ivp_open    = $plan->ivp_open;
        $this->ivr_open    = $plan->ivr_open;
        $this->strategy  = $plan->strategy;
        $this->exit_plan = $plan->exit_plan;
        $this->rationale = $plan->rationale;
        $this->status    = $plan->status;
        $this->date_close    = $plan->date_close?->format('Y-m-d');
        $this->price_close   = $plan->price_close;
        $this->ivp_close     = $plan->ivp_close;
        $this->ivr_close     = $plan->ivr_close;
        $this->premium_close = $plan->premium_close;
        $this->fees_close    = $plan->fees_close;
        $this->realized_pnl  = $plan->realized_pnl;
        $this->actual_dte = $plan->actual_dte;
        $this->notes      = $plan->notes;

        $this->showClosing = $plan->status === 'closed';

        $this->legs = $plan->legs->map(fn($leg) => [
            'leg_type' => $leg->leg_type,
            'dte'      => $leg->dte,
            'strike'   => $leg->strike,
            'delta'    => $leg->delta,
            'premium'  => $leg->premium,
            'fees'     => $leg->fees,
            'max_risk' => $leg->max_risk,
        ])->toArray();
    }

    private function openings(int $campaignId): array
    {
        return CampaignOpenings::fromLedger(
            CampaignLedger::on($this->connection())->where('campaign_id', $campaignId)
                ->orderBy('dateTime')->orderBy('id')->get()
        )['openings'];
    }

    /** Ohne opened_at (vor der Migration) gehört der Eintrag zur ersten Eröffnung. */
    private function belongsToOpening(): bool
    {
        $openings = $this->openings($this->campaignId);

        return $this->openedAt
            ? CampaignOpenings::find($openings, $this->openedAt) !== null
            : $openings !== [];
    }

    public function updatedUnderlying(): void
    {
        $this->refreshCampaignCandidates();
    }

    /**
     * Sucht passende Kampagnen für den aktuellen Underlying — unabhängig von Alter/Status,
     * damit auch länger zurückliegende oder bereits geschlossene Kampagnen nachträglich
     * verknüpft werden können.
     */
    private function refreshCampaignCandidates(): void
    {
        $this->campaignCandidates = [];
        if ($this->campaignId || empty($this->underlying)) {
            return;
        }

        $this->campaignCandidates = Campaign::on($this->connection())
            ->where('environment', $this->selectedEnvironment)
            ->where('symbol', strtoupper($this->underlying))
            ->orderByDesc('start_date')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'status'     => $c->status,
                'start_date' => optional($c->start_date)->format('d.m.Y'),
                'end_date'   => optional($c->end_date)->format('d.m.Y'),
            ])
            ->toArray();
    }

    /**
     * Verknüpft mit einer bestehenden Kampagne: der Eintrag gehört dann zur noch nicht
     * kommentierten Eröffnung, die seinem Datum am nächsten liegt, und ist dort weiter zu bearbeiten.
     */
    public function linkCampaign(int $campaignId): void
    {
        $taken = TradePlan::on($this->connection())->where('campaign_id', $campaignId)
            ->whereNotNull('opened_at')->get()
            ->map(fn($p) => $p->opened_at->format('Y-m-d H:i:s'))->all();
        $opening = CampaignOpenings::nearest($this->openings($campaignId), $this->date_open ?: now(), $taken);
        if (!$opening) {
            return;
        }

        TradePlan::on($this->connection())->findOrFail($this->planId)
            ->update(['campaign_id' => $campaignId, 'opened_at' => $opening['opened_at']]);

        $this->redirect(route('campaigns.campaign.detail', $campaignId), navigate: true);
    }

    public function addLeg(): void
    {
        $this->legs[] = [
            'leg_type' => '',
            'dte'      => '',
            'strike'   => '',
            'delta'    => '',
            'premium'  => '',
            'fees'     => '',
            'max_risk' => '',
        ];
    }

    public function removeLeg(int $index): void
    {
        unset($this->legs[$index]);
        $this->legs = array_values($this->legs);
    }

    public function toggleClose(): void
    {
        if ($this->status === 'open') {
            $this->status = 'closed';
            $this->showClosing = true;
            if (!$this->date_close) {
                $this->date_close = now()->format('Y-m-d');
            }
        } else {
            $this->status = 'open';
            $this->showClosing = false;
        }
    }

    public function savePlan(): void
    {
        $this->underlying = strtoupper(trim($this->underlying));
        $this->validate();

        $connection = $this->connection();
        $plan = TradePlan::on($connection)->findOrFail($this->planId);
        $plan->update([
            'underlying'     => $this->underlying,
            'date_open'      => $this->date_open,
            'price_open'     => $this->price_open ?: null,
            'fees_open'      => $this->fees_open ?: null,
            'ivp_open'       => $this->ivp_open ?: null,
            'ivr_open'       => $this->ivr_open ?: null,
            'strategy'       => $this->strategy ?: null,
            'exit_plan'      => $this->exit_plan ?: null,
            'rationale'      => $this->rationale ?: null,
            'status'         => $this->status,
            'date_close'     => $this->date_close ?: null,
            'price_close'    => $this->price_close ?: null,
            'ivp_close'      => $this->ivp_close ?: null,
            'ivr_close'      => $this->ivr_close ?: null,
            'premium_close'  => $this->premium_close ?: null,
            'fees_close'     => $this->fees_close ?: null,
            'realized_pnl'   => $this->realized_pnl ?: null,
            'actual_dte'     => $this->actual_dte ?: null,
            'notes'          => $this->notes ?: null,
        ]);

        TradePlanLeg::on($connection)->where('plan_id', $plan->id)->delete();
        foreach (array_values($this->legs) as $index => $leg) {
            if (!empty($leg['leg_type'])) {
                TradePlanLeg::on($connection)->create([
                    'plan_id'    => $plan->id,
                    'leg_type'   => $leg['leg_type'],
                    'dte'        => $leg['dte'] ?: null,
                    'strike'     => $leg['strike'] ?: null,
                    'delta'      => $leg['delta'] ?: null,
                    'premium'    => $leg['premium'] ?: null,
                    'fees'       => $leg['fees'] ?: null,
                    'max_risk'   => $leg['max_risk'] ?: null,
                    'sort_order' => $index,
                ]);
            }
        }

        $this->redirect(route('log.list'), navigate: true);
    }

    public function render()
    {
        return view('livewire.log.log-form');
    }
}
