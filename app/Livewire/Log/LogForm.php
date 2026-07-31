<?php

namespace App\Livewire\Log;

use App\Models\Campaign;
use App\Models\CampaignLedger;
use App\Models\TradePlan;
use App\Models\TradePlanLeg;
use App\Models\User;
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
        'fees_open'        => 'nullable|numeric',
        'ivp_open'         => 'nullable|integer|min:0|max:100',
        'ivr_open'         => 'nullable|integer|min:0|max:100',
        'legs.*.leg_type'  => 'required|max:10',
        'legs.*.dte'       => 'nullable|integer',
        'legs.*.strike'    => 'nullable|numeric',
        'legs.*.delta'     => 'nullable|numeric',
        'legs.*.premium'   => 'nullable|numeric',
        'legs.*.fees'      => 'nullable|numeric',
        'legs.*.max_risk'  => 'nullable|numeric',
        'realized_pnl'     => 'nullable|numeric',
        'date_close'       => 'nullable|date',
        'price_close'      => 'nullable|numeric',
        'ivp_close'        => 'nullable|integer|min:0|max:100',
        'ivr_close'        => 'nullable|integer|min:0|max:100',
        'premium_close'    => 'nullable|numeric',
        'fees_close'       => 'nullable|numeric',
        'actual_dte'       => 'nullable|integer',
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
                $this->prefillFromCampaign($this->campaignId);
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

    /**
     * Füllt Kopfdaten + Legs aus einer echten importierten Kampagne vor, damit der User
     * nur noch die qualitativen Felder (Rationale, Exit-Plan, ...) ergänzen muss statt
     * bereits bekannte Zahlen abzutippen.
     */
    private function prefillFromCampaign(int $campaignId): void
    {
        $env        = $this->quickEnvironment ?: $this->selectedEnvironment;
        $connection = TradePlan::getConnectionForEnvironment($env);

        $campaign = Campaign::on($connection)->find($campaignId);
        if (!$campaign) {
            $this->campaignId = null;
            return;
        }

        $ledger = CampaignLedger::on($connection)
            ->where('campaign_id', $campaignId)
            ->orderBy('dateTime')
            ->get();

        $this->underlying = $campaign->symbol;
        $this->date_open  = $campaign->start_date
            ? Carbon::parse($campaign->start_date)->format('Y-m-d')
            : now()->format('Y-m-d');
        $this->strategy = $campaign->strategy;

        $legTypeMap = [
            'OPT_PUT_SELL'  => 'SP',
            'OPT_CALL_SELL' => 'SC',
            'OPT_PUT_BUY'   => 'LP',
            'OPT_CALL_BUY'  => 'LC',
        ];
        $openingActions = [...array_keys($legTypeMap), 'STK_BUY', 'STK_SELL'];

        $this->legs = [];
        $stockOpenEntry = null;
        $openingEntries = $ledger->whereIn('action', $openingActions);
        $optionEntries  = $openingEntries->where('assetCategory', 'OPT')->values();
        $verticalMaxRisk = $this->calculateVerticalSpreadMaxRisk($optionEntries);

        foreach ($openingEntries as $entry) {
            if ($entry->assetCategory === 'STK') {
                $stockOpenEntry ??= $entry;
                continue;
            }

            $dte = ($entry->expiry && $campaign->start_date)
                ? Carbon::parse($campaign->start_date)->diffInDays(Carbon::parse($entry->expiry), false)
                : null;

            $this->legs[] = [
                'leg_type' => $legTypeMap[$entry->action] ?? '',
                'dte'      => $dte,
                'strike'   => $entry->strike !== null ? round((float) $entry->strike, 2) : null,
                'delta'    => null,
                'premium'  => $entry->tradePrice !== null ? round((float) $entry->tradePrice, 2) : null,
                'fees'     => $entry->commission !== null ? round(abs((float) $entry->commission), 2) : null,
                'max_risk' => $verticalMaxRisk,
            ];
        }

        if ($stockOpenEntry) {
            $this->price_open = round((float) $stockOpenEntry->tradePrice, 2);
        }

        $openingFees      = $openingEntries->sum('commission');
        $this->fees_open  = $openingFees != 0 ? round(abs((float) $openingFees), 2) : null;

        if ($campaign->status === 'CLOSED') {
            $this->status      = 'closed';
            $this->showClosing = true;
            $this->date_close  = $campaign->end_date
                ? Carbon::parse($campaign->end_date)->format('Y-m-d')
                : null;

            $closingEntries = $ledger->filter(fn($e) =>
                str_ends_with($e->action ?? '', '_CLOSE')
                || str_ends_with($e->action ?? '', '_PARTIAL')
                || in_array($e->action, ['OPTION_EXPIRATION', 'OPTION_EXERCISE', 'CASH_SETTLEMENT'], true)
            );

            $realizedPnl         = $ledger->sum('realized_pnl');
            $this->realized_pnl  = $realizedPnl != 0 ? round((float) $realizedPnl, 2) : null;
            $closingFees         = $closingEntries->sum('commission');
            $this->fees_close    = $closingFees != 0 ? round(abs((float) $closingFees), 2) : null;

            $stockCloseEntry = $closingEntries->firstWhere('assetCategory', 'STK');
            if ($stockCloseEntry) {
                $this->price_close = round((float) $stockCloseEntry->tradePrice, 2);
            }

            if ($campaign->start_date && $campaign->end_date) {
                $this->actual_dte = Carbon::parse($campaign->start_date)
                    ->diffInDays(Carbon::parse($campaign->end_date));
            }
        }
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
     * Verknüpft nachträglich mit einer bestehenden Kampagne — überschreibt keine bereits
     * eingegebenen Felder (im Unterschied zu prefillFromCampaign bei Neuanlage).
     */
    public function linkCampaign(int $campaignId): void
    {
        $this->campaignId = $campaignId;
        $this->campaignCandidates = [];
    }

    /**
     * Max Risiko ist bei einem reinen 2-Leg-Vertical-Spread eindeutig berechenbar
     * (Strike-Breite × Multiplier × Menge, abzüglich/zzgl. Netto-Cashflow inkl. Gebühren).
     * Bei allem anderen (Naked, Iron Condor, ungleiche Mengen, ...) bleibt es manuell,
     * da das Risiko dort nicht aus den reinen Eröffnungsdaten eindeutig hervorgeht.
     */
    private function calculateVerticalSpreadMaxRisk($optionEntries): ?float
    {
        if ($optionEntries->count() !== 2) {
            return null;
        }

        [$a, $b] = $optionEntries->all();

        if ($a->putCall !== $b->putCall || $a->putCall === null) {
            return null; // keine reine Vertical (z.B. Straddle/Strangle)
        }
        if ($a->buySell === $b->buySell) {
            return null; // gleiche Richtung, kein Spread
        }
        if (!$a->strike || !$b->strike || (float) $a->strike === (float) $b->strike) {
            return null;
        }
        if (abs((float) $a->inventory_delta) !== abs((float) $b->inventory_delta)) {
            return null; // ungleiche Mengen — Ratio-Spread, Risiko nicht eindeutig
        }

        $width      = abs((float) $a->strike - (float) $b->strike) * (float) ($a->multiplier ?: 100);
        $qty        = abs((float) $a->inventory_delta) ?: 1;
        $widthTotal = $width * $qty;
        $netCash    = (float) $a->netcash + (float) $b->netcash;

        if ($netCash > 0) {
            // Credit Spread: Max Risiko = Breite − erhaltener Credit
            return round($widthTotal - $netCash, 2);
        }
        if ($netCash < 0) {
            // Debit Spread: Max Risiko = gezahltes Debit
            return round(abs($netCash), 2);
        }

        return null;
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

        $headerData = [
            'environment'    => $env,
            'campaign_id'    => $this->campaignId,
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

    public function render()
    {
        if ($this->isQuick) {
            return view('livewire.log.log-form')->layout('layouts.minimal', [
                'quickEnv'    => $this->quickEnvironment ?: $this->selectedEnvironment,
                'quickPrefix' => $this->quickPrefix ?: (auth()->user()?->table_prefix ?? ''),
            ]);
        }

        return view('livewire.log.log-form');
    }
}
