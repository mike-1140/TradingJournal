<?php

namespace App\Livewire\Log;

use App\Models\Campaign;
use App\Models\CampaignLedger;
use App\Models\TradePlan;
use App\Models\TradePlanLeg;
use App\Models\User;
use App\Support\CampaignOpenings;
use App\Support\GreeksLookup;
use App\Traits\ManagesEnvironment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LogForm extends Component
{
    use ManagesEnvironment;

    public ?int $planId = null;
    public ?int $campaignId = null;
    /** Eröffnung innerhalb der Kampagne, die dieser Eintrag kommentiert — die Zahlen kommen aus dem Ledger */
    public ?string $openedAt = null;
    public array $campaignCandidates = [];

    // Quick-Entry-Modus
    public bool   $isQuick          = false;
    public string $quickPrefix      = '';
    public string $quickEnvironment = ''; // separater Env-Override — wird nicht von mountManagesEnvironment() zurückgesetzt

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

    public function mount(?int $id = null): void
    {
        $this->mountManagesEnvironment();
        $this->date_open = now()->format('Y-m-d');

        if (request()->routeIs('log.quick')) {
            $this->isQuick = true;

            if ($env = request()->query('env')) {
                // Livewire ruft mountManagesEnvironment() nochmals nach mount() auf und
                // würde selectedEnvironment zurücksetzen → quickEnvironment als separaten Speicher nutzen
                $this->quickEnvironment = strtoupper($env);
            }
            if ($prefix = request()->query('prefix')) {
                $this->quickPrefix = $prefix;
            }

            $this->applyQuickContext();
        }

        if ($id) {
            $this->planId = $id;
            $this->loadPlan($id);
        } else {
            $this->exit_plan = 'TP bei 50%, SL bei 2x Prämie';

            if ($campaignId = request()->query('campaign_id')) {
                $this->campaignId = (int) $campaignId;
                $this->prefillFromOpening($this->campaignId, request()->query('opened_at'));
            }
        }

        $this->refreshCampaignCandidates();
    }

    public function hydrate(): void
    {
        // Middleware setzt Prefix für Session-Environment — wir überschreiben für Quick-Mode
        if ($this->isQuick) {
            $this->applyQuickContext();
        }
    }

    private function applyQuickContext(): void
    {
        // quickPrefix kommt aus der URL bzw. vom Client — nur Prefixe zulassen,
        // die einem User in der users-Tabelle gehören, sonst auf eigenen Prefix zurückfallen
        if ($this->quickPrefix !== '' && !User::where('table_prefix', $this->quickPrefix)->exists()) {
            $this->quickPrefix = '';
        }

        $env        = $this->quickEnvironment ?: $this->selectedEnvironment;
        $prefix     = $this->quickPrefix ?: (auth()->user()?->table_prefix ?? '');
        $connection = TradePlan::getConnectionForEnvironment($env);
        Config::set("database.connections.{$connection}.prefix", $prefix);
        DB::purge($connection);
    }

    private function loadPlan(int $id): void
    {
        $env        = $this->quickEnvironment ?: $this->selectedEnvironment;
        $connection = TradePlan::getConnectionForEnvironment($env);
        $plan = TradePlan::on($connection)->with('legs')->findOrFail($id);

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

        // Eintrag von vor der Migration bzw. ohne Wert: gehört zur ersten Eröffnung
        if ($this->campaignId && !$this->openedAt) {
            $this->openedAt = $this->campaignOpenings()['openings'][0]['opened_at'] ?? null;
        }
    }

    /**
     * Neuer Kommentar zu einer Eröffnung der Kampagne: ohne Angabe die erste noch nicht
     * kommentierte. Gibt es dazu schon einen Eintrag, wird dieser bearbeitet.
     */
    private function prefillFromOpening(int $campaignId, ?string $openedAt): void
    {
        $env        = $this->quickEnvironment ?: $this->selectedEnvironment;
        $connection = TradePlan::getConnectionForEnvironment($env);

        $campaign = Campaign::on($connection)->find($campaignId);
        if (!$campaign) {
            $this->campaignId = null;
            return;
        }

        $openings = $this->campaignOpenings()['openings'];
        $taken    = $this->takenOpenings($connection, $campaignId);
        $opening  = CampaignOpenings::find($openings, $openedAt)
            ?? collect($openings)->first(fn($o) => !in_array($o['opened_at'], $taken, true))
            ?? ($openings[0] ?? null);

        $this->underlying = $campaign->symbol;
        $this->strategy   = $campaign->strategy;
        $this->date_open  = $opening
            ? Carbon::parse($opening['opened_at'])->format('Y-m-d')
            : ($campaign->start_date ? Carbon::parse($campaign->start_date)->format('Y-m-d') : now()->format('Y-m-d'));
        $this->openedAt   = $opening['opened_at'] ?? null;

        if ($this->openedAt && in_array($this->openedAt, $taken, true)) {
            $existing = TradePlan::on($connection)->where('campaign_id', $campaignId)
                ->where('opened_at', $this->openedAt)->first();
            if ($existing) {
                $this->planId = $existing->id;
                $this->loadPlan($existing->id);
            }
        }
    }

    /** Eröffnungszeitpunkte der Kampagne, zu denen es schon einen Eintrag gibt. */
    private function takenOpenings(string $connection, int $campaignId): array
    {
        return TradePlan::on($connection)->where('campaign_id', $campaignId)
            ->whereNotNull('opened_at')->get()
            ->map(fn($p) => $p->opened_at->format('Y-m-d H:i:s'))->all();
    }

    /** Ergebnis von CampaignOpenings::fromLedger() für die verknüpfte Kampagne. */
    private function campaignOpenings(): array
    {
        $env        = $this->quickEnvironment ?: $this->selectedEnvironment;
        $connection = TradePlan::getConnectionForEnvironment($env);

        return CampaignOpenings::fromLedger(
            CampaignLedger::on($connection)->where('campaign_id', $this->campaignId)
                ->orderBy('dateTime')->orderBy('id')->get()
        );
    }

    /** Die kommentierte Eröffnung, oder null (keine Verknüpfung bzw. Zeitpunkt nicht im Ledger). */
    private function linkedOpening(): ?array
    {
        if (!$this->campaignId || !$this->openedAt) {
            return null;
        }
        return CampaignOpenings::find($this->campaignOpenings()['openings'], $this->openedAt);
    }

    public function updatedUnderlying(): void
    {
        $this->refreshCampaignCandidates();
    }

    /**
     * Sucht passende Kampagnen für den aktuellen Underlying — unabhängig von Alter/Status,
     * damit auch länger zurückliegende oder bereits geschlossene Kampagnen nachträglich
     * verknüpft werden können (die "nicht dokumentiert"-Liste zeigt nur die letzten 30 Tage).
     */
    private function refreshCampaignCandidates(): void
    {
        $this->campaignCandidates = [];
        if ($this->campaignId || empty($this->underlying)) {
            return;
        }

        $env        = $this->quickEnvironment ?: $this->selectedEnvironment;
        $connection = TradePlan::getConnectionForEnvironment($env);

        $this->campaignCandidates = Campaign::on($connection)
            ->where('environment', $env)
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
     * Verknüpft nachträglich mit einer bestehenden Kampagne: gehört zur noch nicht
     * kommentierten Eröffnung, die dem Eintragsdatum am nächsten liegt.
     */
    public function linkCampaign(int $campaignId): void
    {
        $env        = $this->quickEnvironment ?: $this->selectedEnvironment;
        $connection = TradePlan::getConnectionForEnvironment($env);

        $this->campaignId = $campaignId;
        $this->campaignCandidates = [];
        $opening = CampaignOpenings::nearest(
            $this->campaignOpenings()['openings'],
            $this->date_open ?: now(),
            $this->takenOpenings($connection, $campaignId)
        );
        $this->openedAt = $opening['opened_at'] ?? null;
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

        $env        = $this->quickEnvironment ?: $this->selectedEnvironment;
        $connection = TradePlan::getConnectionForEnvironment($env);

        if ($opening = $this->linkedOpening()) {
            $this->saveComment($connection, $env, $opening);
            $this->redirect(route('log.list'), navigate: true);
            return;
        }

        $headerData = [
            'environment'    => $env,
            'campaign_id'    => $this->campaignId,
            'opened_at'      => $this->openedAt,
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
            'actual_dte'      => $this->actual_dte ?: null,
            'notes'           => $this->notes ?: null,
        ];

        if ($this->planId) {
            $plan = TradePlan::on($connection)->findOrFail($this->planId);
            $plan->update($headerData);
        } else {
            $plan = TradePlan::on($connection)->create($headerData);
        }

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

    /**
     * Eintrag zu einer Eröffnung: nur der Kommentar und von Hand eingetragene Kurs-/IV-Werte; alle
     * übrigen Zahlen kommen aus dem Ledger, bestehende Spalten und Legs bleiben unberührt.
     */
    private function saveComment(string $connection, string $env, array $opening): void
    {
        $data = [
            'environment' => $env,
            'campaign_id' => $this->campaignId,
            'opened_at'   => $this->openedAt,
            'underlying'  => $this->underlying,
            'date_open'   => Carbon::parse($opening['opened_at'])->format('Y-m-d'),
            'price_open'  => $this->price_open ?: null,
            'ivp_open'    => $this->ivp_open ?: null,
            'ivr_open'    => $this->ivr_open ?: null,
            'price_close' => $this->price_close ?: null,
            'ivp_close'   => $this->ivp_close ?: null,
            'ivr_close'   => $this->ivr_close ?: null,
            'strategy'    => $this->strategy ?: null,
            'exit_plan'   => $this->exit_plan ?: null,
            'rationale'   => $this->rationale ?: null,
            'notes'       => $this->notes ?: null,
            'status'      => $opening['state'] === 'closed' ? 'closed' : 'open',
        ];

        if ($this->planId) {
            TradePlan::on($connection)->findOrFail($this->planId)->update($data);
        } else {
            TradePlan::on($connection)->create($data);
        }
    }

    public function render()
    {
        $env     = $this->quickEnvironment ?: $this->selectedEnvironment;
        $opening = $this->linkedOpening();
        $details = $opening
            ? (new GreeksLookup(TradePlan::getConnectionForEnvironment($env)))->detailsFor($opening, $this->legs)
            : null;
        $data = ['opening' => $opening, 'details' => $details];

        if ($this->isQuick) {
            return view('livewire.log.log-form', $data)->layout('layouts.minimal', [
                'quickEnv'    => $this->quickEnvironment ?: $this->selectedEnvironment,
                'quickPrefix' => $this->quickPrefix ?: (auth()->user()?->table_prefix ?? ''),
            ]);
        }

        return view('livewire.log.log-form', $data);
    }
}
