<?php

namespace App\Livewire\Psychology;

use App\Models\PsychologyComplianceResult;
use App\Models\PsychologyEntry;
use App\Models\PsychologyRule;
use App\Models\PsychologyRuleset;
use App\Models\TradeClose;
use App\Traits\ManagesEnvironment;
use Carbon\Carbon;
use Livewire\Component;

class PsychologyForm extends Component
{
    use ManagesEnvironment;

    public ?int $entryId = null;

    // Auto-fill Kontext (aus URL-Parametern)
    public ?string $prefillTradeId    = null;
    public ?string $prefillStrategyId = null;
    public ?string $prefillTradeType  = null;

    // Erfassungs-Felder
    public string  $symbol       = '';
    public string  $tradeDate    = '';
    public ?string $pnl          = null;
    public ?int    $emotionPre   = null;
    public ?int    $emotionPost  = null;
    public ?string $motivation   = null;
    public ?string $marketContext = null;
    public ?string $lesson       = null;

    // Regelwerk + Compliance
    public ?int $selectedRulesetId = null;
    public array $complianceValues  = [];   // rule_id => bool
    public array $temptationValues  = [];   // rule_id => bool

    // Geladene Listen (Zustand, kein DB-Roundtrip pro Render)
    public array $rulesets = [];
    public array $rules    = [];

    protected $listeners = [
        'environment-changed' => 'refreshRulesets',
    ];

    protected $rules = [
        'symbol'      => 'required|max:100',
        'tradeDate'   => 'required|date',
        'pnl'         => 'nullable|numeric',
        'emotionPre'  => 'nullable|integer|min:1|max:5',
        'emotionPost' => 'nullable|integer|min:1|max:5',
        'lesson'      => 'nullable|max:1000',
    ];

    public function mount(?int $id = null): void
    {
        $this->mountManagesEnvironment();
        $this->tradeDate = now()->format('Y-m-d');
        $this->entryId   = $id;

        $this->loadRulesets();

        // Auto-fill aus URL-Parametern
        if ($tradeId = request()->query('trade_id')) {
            $this->prefillTradeId   = $tradeId;
            $this->prefillTradeType = request()->query('trade_type', 'closed');
            $this->autofillFromTrade();
        } elseif ($strategyId = request()->query('opt_strategyID')) {
            $this->prefillStrategyId = $strategyId;
            $this->prefillTradeType  = 'spread';
            $this->autofillFromSpread();
        }

        if ($id) {
            $this->loadEntry($id);
        }
    }

    // ── Daten laden ─────────────────────────────────────────────────────────

    private function loadRulesets(): void
    {
        $env        = $this->selectedEnvironment;
        $connection = PsychologyRuleset::getConnectionForEnvironment($env);

        $this->rulesets = PsychologyRuleset::on($connection)
            ->where('environment', $env)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->toArray();

        if (!$this->selectedRulesetId && count($this->rulesets) > 0) {
            $this->selectedRulesetId = $this->rulesets[0]['id'];
            $this->loadRules();
        }
    }

    private function loadRules(): void
    {
        if (!$this->selectedRulesetId) {
            $this->rules           = [];
            $this->complianceValues = [];
            $this->temptationValues = [];
            return;
        }

        $env        = $this->selectedEnvironment;
        $connection = PsychologyRule::getConnectionForEnvironment($env);

        $this->rules = PsychologyRule::on($connection)
            ->where('ruleset_id', $this->selectedRulesetId)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        foreach ($this->rules as $rule) {
            $id = $rule['id'];
            $this->complianceValues[$id] ??= false;
            $this->temptationValues[$id] ??= false;
        }
    }

    private function loadEntry(int $id): void
    {
        $env        = $this->selectedEnvironment;
        $connection = PsychologyEntry::getConnectionForEnvironment($env);

        $entry = PsychologyEntry::on($connection)
            ->with('complianceResults')
            ->findOrFail($id);

        $this->symbol          = $entry->symbol;
        $this->tradeDate       = Carbon::parse($entry->trade_date)->format('Y-m-d');
        $this->pnl             = $entry->pnl;
        $this->emotionPre      = $entry->emotion_pre;
        $this->emotionPost     = $entry->emotion_post;
        $this->motivation      = $entry->motivation;
        $this->marketContext   = $entry->market_context;
        $this->lesson          = $entry->lesson;
        $this->selectedRulesetId = $entry->ruleset_id;
        $this->prefillTradeId    = $entry->trade_id;
        $this->prefillStrategyId = $entry->opt_strategyID;
        $this->prefillTradeType  = $entry->trade_type;

        $this->loadRules();

        foreach ($entry->complianceResults as $result) {
            $this->complianceValues[$result->rule_id] = (bool) $result->complied;
            $this->temptationValues[$result->rule_id] = (bool) $result->tempted;
        }
    }

    private function autofillFromTrade(): void
    {
        $env        = $this->selectedEnvironment;
        $connection = TradeClose::getConnectionForEnvironment($env);
        $trade      = TradeClose::on($connection)->find((int) $this->prefillTradeId);

        if ($trade) {
            $this->symbol    = $trade->symbol ?? '';
            $this->pnl       = $trade->profit !== null ? number_format((float) $trade->profit, 2, '.', '') : null;
            $this->tradeDate = $trade->dateTimeClose
                ? Carbon::parse($trade->dateTimeClose)->format('Y-m-d')
                : now()->format('Y-m-d');
        }
    }

    private function autofillFromSpread(): void
    {
        $env        = $this->selectedEnvironment;
        $connection = TradeClose::getConnectionForEnvironment($env);
        $trades     = TradeClose::on($connection)
            ->where('opt_strategyID', $this->prefillStrategyId)
            ->get();

        if ($trades->isNotEmpty()) {
            $this->symbol    = $trades->first()->underlyingSymbol ?? $trades->first()->symbol ?? '';
            $this->pnl       = number_format((float) $trades->sum('profit'), 2, '.', '');
            $maxClose        = $trades->max('dateTimeClose');
            $this->tradeDate = $maxClose
                ? Carbon::parse($maxClose)->format('Y-m-d')
                : now()->format('Y-m-d');
        }
    }

    // ── Livewire-Hooks ───────────────────────────────────────────────────────

    public function updatedSelectedRulesetId(): void
    {
        $this->complianceValues = [];
        $this->temptationValues = [];
        $this->loadRules();
    }

    public function refreshRulesets(): void
    {
        $this->selectedRulesetId = null;
        $this->rules             = [];
        $this->complianceValues  = [];
        $this->temptationValues  = [];
        $this->loadRulesets();
    }

    // ── Toggle-Aktionen ──────────────────────────────────────────────────────

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
            $this->emotionPre = ($this->emotionPre === $value) ? null : $value;
        } else {
            $this->emotionPost = ($this->emotionPost === $value) ? null : $value;
        }
    }

    public function setMotivation(string $value): void
    {
        $this->motivation = ($this->motivation === $value) ? null : $value;
    }

    public function setMarketContext(string $value): void
    {
        $this->marketContext = ($this->marketContext === $value) ? null : $value;
    }

    // ── Speichern ────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->symbol = strtoupper(trim($this->symbol));
        $this->validate();

        $env        = $this->selectedEnvironment;
        $connection = PsychologyEntry::getConnectionForEnvironment($env);

        $data = [
            'trade_id'       => $this->prefillTradeId,
            'opt_strategyID' => $this->prefillStrategyId,
            'trade_type'     => $this->prefillTradeType,
            'ruleset_id'     => $this->selectedRulesetId,
            'symbol'         => $this->symbol,
            'trade_date'     => $this->tradeDate,
            'pnl'            => $this->pnl ?: null,
            'emotion_pre'    => $this->emotionPre,
            'emotion_post'   => $this->emotionPost,
            'motivation'     => $this->motivation,
            'market_context' => $this->marketContext,
            'lesson'         => $this->lesson ?: null,
            'environment'    => $env,
        ];

        if ($this->entryId) {
            $entry = PsychologyEntry::on($connection)->findOrFail($this->entryId);
            $entry->update($data);
            PsychologyComplianceResult::on($connection)
                ->where('entry_id', $entry->id)
                ->delete();
        } else {
            $entry = PsychologyEntry::on($connection)->create($data);
        }

        foreach ($this->rules as $rule) {
            $ruleId = $rule['id'];
            PsychologyComplianceResult::on($connection)->create([
                'entry_id' => $entry->id,
                'rule_id'  => $ruleId,
                'complied' => $this->complianceValues[$ruleId] ?? false,
                'tempted'  => $this->temptationValues[$ruleId] ?? false,
            ]);
        }

        $this->redirect(route('psychology.list'), navigate: true);
    }

    public function render()
    {
        return view('livewire.psychology.psychology-form');
    }
}
