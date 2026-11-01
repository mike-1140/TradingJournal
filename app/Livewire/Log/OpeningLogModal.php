<?php

namespace App\Livewire\Log;

use App\Models\Campaign;
use App\Models\CampaignLedger;
use App\Models\PsychologyComplianceResult;
use App\Models\PsychologyEntry;
use App\Models\PsychologyRule;
use App\Models\PsychologyRuleset;
use App\Models\TradeOpen;
use App\Models\TradePlan;
use App\Support\CampaignOpenings;
use App\Support\GreeksLookup;
use App\Traits\ManagesEnvironment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Fenster zum Dokumentieren einer Eröffnung, geöffnet über den Knopf in der Buchungszeile. Reiter
 * Trade: Zahlen aus dem Ledger und die Eingaben; bestehende Zahlenspalten und Legs bleiben unberührt.
 * Reiter Psychologie: Eintrag entsteht nur, wenn dort etwas ausgefüllt ist.
 */
class OpeningLogModal extends Component
{
    use ManagesEnvironment;

    public bool $show = false;
    public string $tab = 'trade';
    public ?int $campaignId = null;
    public ?string $openedAt = null;
    public ?int $planId = null;

    public string $underlying = '';
    public ?string $strategy = null;
    public ?string $exit_plan = null;
    public ?string $rationale = null;
    public ?string $notes = null;
    public ?string $lessons_learned = null;
    public ?string $price_open = null;
    public ?string $ivp_open = null;
    public ?string $ivr_open = null;
    public ?string $price_close = null;
    public ?string $ivp_close = null;
    public ?string $ivr_close = null;

    // Psychologie
    public bool $psychologyAvailable = false;
    public ?int $psychologyId = null;
    public ?int $emotionPre = null;
    public ?int $emotionPost = null;
    public ?string $motivation = null;
    public ?string $marketContext = null;
    public ?int $selectedRulesetId = null;
    public array $rulesets = [];
    public array $rulesList = [];   // nicht $rules: in Livewire reserviert
    public array $complianceValues = [];
    public array $temptationValues = [];

    private const FIELDS = ['show', 'tab', 'campaignId', 'openedAt', 'planId', 'underlying', 'strategy', 'exit_plan',
        'rationale', 'notes', 'lessons_learned', 'price_open', 'ivp_open', 'ivr_open', 'price_close', 'ivp_close',
        'ivr_close', 'psychologyAvailable', 'psychologyId', 'emotionPre', 'emotionPost', 'motivation', 'marketContext',
        'selectedRulesetId', 'rulesets', 'rulesList', 'complianceValues', 'temptationValues'];

    protected $listeners = [
        'environment-changed' => 'close',
    ];

    protected $rules = [
        'strategy'    => 'nullable|max:50',
        'price_open'  => 'nullable|numeric',
        'ivp_open'    => 'nullable|integer|min:0|max:100',
        'ivr_open'    => 'nullable|integer|min:0|max:100',
        'price_close' => 'nullable|numeric',
        'ivp_close'   => 'nullable|integer|min:0|max:100',
        'ivr_close'   => 'nullable|integer|min:0|max:100',
        'emotionPre'  => 'nullable|integer|min:1|max:5',
        'emotionPost' => 'nullable|integer|min:1|max:5',
    ];

    public function mount(): void
    {
        $this->mountManagesEnvironment();
    }

    #[On('open-opening-log')]
    public function openFor(int $campaignId, string $openedAt): void
    {
        $this->close();

        $connection = $this->connection();
        $campaign   = Campaign::on($connection)->find($campaignId);
        if (!$campaign) {
            return;
        }
        $this->campaignId = $campaignId;
        $this->openedAt   = $openedAt;

        $openings = $this->openings();
        if (!CampaignOpenings::find($openings, $openedAt)) {
            $this->close();
            return;
        }

        $first = $openings[0]['opened_at'];
        $plan  = TradePlan::on($connection)->where('campaign_id', $campaignId)
            ->where('environment', $this->selectedEnvironment)->orderBy('id')->get()
            ->first(fn($p) => ($p->opened_at?->format('Y-m-d H:i:s') ?? $first) === $openedAt);

        $this->underlying = $campaign->symbol;
        if ($plan) {
            $this->planId = $plan->id;
            foreach (['strategy', 'exit_plan', 'rationale', 'notes', 'lessons_learned', 'price_open',
                      'ivp_open', 'ivr_open', 'price_close', 'ivp_close', 'ivr_close'] as $field) {
                $this->{$field} = $plan->{$field} === null ? null : (string) $plan->{$field};
            }
        } else {
            $this->strategy  = $campaign->strategy;
            $this->exit_plan = 'TP bei 50%, SL bei 2x Prämie';
        }

        $this->loadPsychology($connection);
        $this->show = true;
    }

    private function loadPsychology(string $connection): void
    {
        $this->psychologyAvailable = PsychologyEntry::tableExistsFor($connection);
        if (!$this->psychologyAvailable) {
            return;
        }

        $env = $this->selectedEnvironment;
        $this->rulesets = PsychologyRuleset::on($connection)->where('environment', $env)->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')->get()->toArray();

        $entry = PsychologyEntry::on($connection)->with('complianceResults')
            ->where('campaign_id', $this->campaignId)->where('opened_at', $this->openedAt)
            ->where('environment', $env)->first();

        if ($entry) {
            $this->psychologyId  = $entry->id;
            $this->emotionPre    = $entry->emotion_pre;
            $this->emotionPost   = $entry->emotion_post;
            $this->motivation    = $entry->motivation;
            $this->marketContext = $entry->market_context;
            $this->selectedRulesetId = $entry->ruleset_id;
        }
        $this->selectedRulesetId ??= $this->rulesets[0]['id'] ?? null;
        $this->loadRules();

        foreach ($entry?->complianceResults ?? [] as $result) {
            $this->complianceValues[$result->rule_id] = (bool) $result->complied;
            $this->temptationValues[$result->rule_id] = (bool) $result->tempted;
        }
    }

    private function loadRules(): void
    {
        $this->rulesList = $this->selectedRulesetId
            ? PsychologyRule::on($this->connection())->where('ruleset_id', $this->selectedRulesetId)
                ->orderBy('sort_order')->get()->toArray()
            : [];
    }

    public function close(): void
    {
        $this->resetValidation();
        $this->reset(self::FIELDS);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'psychology' ? 'psychology' : 'trade';
    }

    public function updatedSelectedRulesetId(): void
    {
        $this->complianceValues = [];
        $this->temptationValues = [];
        $this->loadRules();
    }

    public function toggleCompliance(int $ruleId): void
    {
        $this->complianceValues[$ruleId] = !($this->complianceValues[$ruleId] ?? false);
    }

    public function toggleTemptation(int $ruleId): void
    {
        $this->temptationValues[$ruleId] = !($this->temptationValues[$ruleId] ?? false);
    }

    public function setEmotion(string $type, int $value): void
    {
        if ($type === 'pre') {
            $this->emotionPre = $this->emotionPre === $value ? null : $value;
        } else {
            $this->emotionPost = $this->emotionPost === $value ? null : $value;
        }
    }

    public function setMotivation(string $value): void
    {
        $this->motivation = $this->motivation === $value ? null : $value;
    }

    public function setMarketContext(string $value): void
    {
        $this->marketContext = $this->marketContext === $value ? null : $value;
    }

    public function save(): void
    {
        $this->validate();

        $opening = CampaignOpenings::find($this->openings(), $this->openedAt);
        if (!$opening) {
            return;
        }

        $connection = $this->connection();
        DB::connection($connection)->transaction(function () use ($connection, $opening) {
            $this->savePlan($connection, $opening);
            if ($this->psychologyAvailable) {
                $this->savePsychology($connection, $opening);
            }
        });

        $this->close();
        $this->dispatch('opening-log-saved');
    }

    private function savePlan(string $connection, array $opening): void
    {
        $data = [
            'environment'     => $this->selectedEnvironment,
            'campaign_id'     => $this->campaignId,
            'opened_at'       => $this->openedAt,
            'underlying'      => $this->underlying,
            'date_open'       => Carbon::parse($opening['opened_at'])->format('Y-m-d'),
            'price_open'      => $this->price_open ?: null,
            'ivp_open'        => $this->ivp_open ?: null,
            'ivr_open'        => $this->ivr_open ?: null,
            'price_close'     => $this->price_close ?: null,
            'ivp_close'       => $this->ivp_close ?: null,
            'ivr_close'       => $this->ivr_close ?: null,
            'strategy'        => $this->strategy ?: null,
            'exit_plan'       => $this->exit_plan ?: null,
            'rationale'       => $this->rationale ?: null,
            'notes'           => $this->notes ?: null,
            'lessons_learned' => $this->lessons_learned ?: null,
            'status'          => $opening['state'] === 'closed' ? 'closed' : 'open',
        ];

        if ($this->planId) {
            TradePlan::on($connection)->findOrFail($this->planId)->update($data);
        } else {
            TradePlan::on($connection)->create($data);
        }
    }

    /**
     * Nichts ausgefüllt → kein Eintrag, ein vorhandener entfällt. Regeln nur, wenn mindestens eine
     * angekreuzt ist: das vorausgewählte Regelwerk allein ist keine Aussage über die Einhaltung.
     */
    private function savePsychology(string $connection, array $opening): void
    {
        $answered = in_array(true, $this->complianceValues, true) || in_array(true, $this->temptationValues, true);
        $filled   = $answered || $this->emotionPre || $this->emotionPost || $this->motivation || $this->marketContext;

        if ($this->psychologyId) {
            PsychologyComplianceResult::on($connection)->where('entry_id', $this->psychologyId)->delete();
        }
        if (!$filled) {
            if ($this->psychologyId) {
                PsychologyEntry::on($connection)->whereKey($this->psychologyId)->delete();
            }
            return;
        }

        $data = [
            'ruleset_id'     => $answered ? $this->selectedRulesetId : null,
            'symbol'         => $this->underlying,
            'trade_date'     => Carbon::parse($opening['opened_at'])->format('Y-m-d'),
            'pnl'            => $opening['state'] === 'closed' ? $opening['pnl'] : null,
            'emotion_pre'    => $this->emotionPre,
            'emotion_post'   => $this->emotionPost,
            'motivation'     => $this->motivation,
            'market_context' => $this->marketContext,
            'environment'    => $this->selectedEnvironment,
            'campaign_id'    => $this->campaignId,
            'opened_at'      => $this->openedAt,
        ];

        if ($this->psychologyId) {
            $entry = PsychologyEntry::on($connection)->findOrFail($this->psychologyId);
            $entry->update($data);
        } else {
            $entry = PsychologyEntry::on($connection)->create($data + $this->tradeReference($connection, $opening));
        }

        if ($answered) {
            foreach ($this->rulesList as $rule) {
                PsychologyComplianceResult::on($connection)->create([
                    'entry_id' => $entry->id,
                    'rule_id'  => $rule['id'],
                    'complied' => $this->complianceValues[$rule['id']] ?? false,
                    'tempted'  => $this->temptationValues[$rule['id']] ?? false,
                ]);
            }
        }
    }

    /** Bezug für die Karte auf den Trade- und Spread-Detailseiten. */
    private function tradeReference(string $connection, array $opening): array
    {
        $sourceId = $opening['legs'][0]['entry']->source_id ?? null;
        $tranche  = $sourceId ? TradeOpen::on($connection)->find($sourceId) : null;
        if (!$tranche) {
            return [];
        }

        return $tranche->opt_strategyID
            ? ['opt_strategyID' => $tranche->opt_strategyID, 'trade_type' => 'open_spread']
            : ['trade_id' => (string) $tranche->id, 'trade_type' => 'open'];
    }

    private function connection(): string
    {
        return TradePlan::getConnectionForEnvironment($this->selectedEnvironment);
    }

    private function openings(): array
    {
        return CampaignOpenings::fromLedger(
            CampaignLedger::on($this->connection())->where('campaign_id', $this->campaignId)
                ->orderBy('dateTime')->orderBy('id')->get()
        )['openings'];
    }

    public function render()
    {
        $opening = $this->show ? CampaignOpenings::find($this->openings(), $this->openedAt) : null;
        $details = null;
        if ($opening) {
            $legs = $this->planId
                ? TradePlan::on($this->connection())->with('legs')->find($this->planId)?->legs ?? []
                : [];
            $details = (new GreeksLookup($this->connection()))->detailsFor($opening, $legs);
        }

        return view('livewire.log.opening-log-modal', ['opening' => $opening, 'details' => $details]);
    }
}
