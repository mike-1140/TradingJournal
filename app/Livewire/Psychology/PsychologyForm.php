<?php

namespace App\Livewire\Psychology;

use App\Models\PsychologyComplianceResult;
use App\Models\PsychologyEntry;
use App\Models\PsychologyRule;
use App\Models\PsychologyRuleset;
use App\Models\TradeClose;
use App\Models\TradeOpen;
use App\Traits\ManagesEnvironment;
use App\Traits\RequiresPsychologyModule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PsychologyForm extends Component
{
    use ManagesEnvironment, RequiresPsychologyModule;

    public bool $moduleInstalled = true;

    public ?int $entryId = null;

    // Auto-fill Kontext (aus URL-Parametern)
    public ?string $prefillTradeId    = null;
    public ?string $prefillStrategyId = null;
    public ?string $prefillTradeType  = null;

    // Manuelle Verknüpfungssuche — für nachträgliches/gezieltes Dokumentieren
    // unabhängig vom Zeitpunkt, unabhängig davon ob der Trade noch in den
    // normalen Listen auftaucht (z.B. bereits ausgeübte Legs)
    public array $linkCandidates = [];

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
    public array $rulesets  = [];
    public array $rulesList = [];  // umbenannt — $rules ist in Livewire reserviert

    protected $listeners = [
        'environment-changed' => 'refreshRulesets',
    ];

    public function rules(): array
    {
        return [
            'symbol'      => 'required|max:100',
            'tradeDate'   => 'required|date',
            'pnl'         => 'nullable|numeric',
            'emotionPre'  => 'nullable|integer|min:1|max:5',
            'emotionPost' => 'nullable|integer|min:1|max:5',
            'lesson'      => 'nullable|max:1000',
        ];
    }

    public function mount(?int $id = null): void
    {
        $this->mountManagesEnvironment();

        if ($this->psychologyModuleMissing()) {
            $this->moduleInstalled = false;
            return;
        }

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
            $this->prefillTradeType  = request()->query('trade_type', 'spread');
            $this->autofillFromSpread();
        }

        if ($id) {
            $this->loadEntry($id);
        }

        $this->refreshLinkCandidates();
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
            $this->rulesList           = [];
            $this->complianceValues = [];
            $this->temptationValues = [];
            return;
        }

        $env        = $this->selectedEnvironment;
        $connection = PsychologyRule::getConnectionForEnvironment($env);

        $this->rulesList = PsychologyRule::on($connection)
            ->where('ruleset_id', $this->selectedRulesetId)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        foreach ($this->rulesList as $rule) {
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
        $env = $this->selectedEnvironment;

        if ($this->prefillTradeType === 'open') {
            $connection = TradeOpen::getConnectionForEnvironment($env);
            $trade      = TradeOpen::on($connection)->find((int) $this->prefillTradeId);

            if ($trade) {
                $this->symbol    = $trade->symbol ?? '';
                $this->pnl       = null; // Trade noch offen, kein PnL
                $this->tradeDate = $trade->dateTime
                    ? Carbon::parse($trade->dateTime)->format('Y-m-d')
                    : now()->format('Y-m-d');
            }
            return;
        }

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
        $env = $this->selectedEnvironment;

        if ($this->prefillTradeType === 'open_spread') {
            $connection = TradeOpen::getConnectionForEnvironment($env);
            $trades     = TradeOpen::on($connection)
                ->where('opt_strategyID', $this->prefillStrategyId)
                ->where('processed', 0)
                ->get();

            if ($trades->isNotEmpty()) {
                $this->symbol    = $trades->first()->underlyingSymbol ?? $trades->first()->symbol ?? '';
                $this->pnl       = null; // Spread noch offen, kein PnL
                $maxOpen         = $trades->max('dateTime');
                $this->tradeDate = $maxOpen
                    ? Carbon::parse($maxOpen)->format('Y-m-d')
                    : now()->format('Y-m-d');
            }
            return;
        }

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

    // ── Manuelle Verknüpfung ─────────────────────────────────────────────────

    /**
     * Sucht offene und geschlossene Trades zum aktuellen Symbol — unabhängig von
     * Alter oder Sichtbarkeit in den normalen Listen (z.B. bereits ausgeübte/
     * zugeteilte Legs, die aus "Offene Positionen" rausgefiltert sind, aber
     * über ihre Detailseite weiterhin erreichbar bleiben).
     */
    private function refreshLinkCandidates(): void
    {
        $this->linkCandidates = [];
        if ($this->prefillTradeId || $this->prefillStrategyId || empty($this->symbol)) {
            return;
        }

        $env    = $this->selectedEnvironment;
        $symbol = strtoupper(trim($this->symbol));

        $openConn  = TradeOpen::getConnectionForEnvironment($env);
        $closeConn = TradeClose::getConnectionForEnvironment($env);

        $symbolMatch = fn ($q) => $q->where('symbol', 'like', $symbol . '%')->orWhere('underlyingSymbol', $symbol);
        $hasStrategy = fn ($q) => $q->whereNotNull('opt_strategyID')->where('opt_strategyID', '!=', '');
        $noStrategy  = fn ($q) => $q->whereNull('opt_strategyID')->orWhere('opt_strategyID', '');

        // Offene Spread-Gruppen (mehrere Legs derselben opt_strategyID) — als ein
        // Kandidat statt einzelner Legs, analog zur Spread-Detail-Seite.
        $openSpreads = TradeOpen::on($openConn)
            ->where('environment', $env)
            ->where($symbolMatch)
            ->where($hasStrategy)
            ->select('opt_strategyID', 'opt_strategyName',
                DB::raw('MAX(dateTime) as latest_date'), DB::raw('COUNT(*) as leg_count'))
            ->groupBy('opt_strategyID', 'opt_strategyName')
            ->orderByDesc('latest_date')
            ->limit(10)
            ->get();

        foreach ($openSpreads as $spread) {
            $this->linkCandidates[] = [
                'type'  => 'open_spread',
                'id'    => $spread->opt_strategyID,
                'label' => $this->describeSpread($spread, 'latest_date') . ' · eröffnet',
            ];
        }

        $opens = TradeOpen::on($openConn)
            ->where('environment', $env)
            ->where($symbolMatch)
            ->where($noStrategy)
            ->orderByDesc('dateTime')
            ->limit(15)
            ->get();

        foreach ($opens as $trade) {
            $this->linkCandidates[] = [
                'type'  => 'open',
                'id'    => $trade->id,
                'label' => $this->describeTrade($trade, 'dateTime') . ' · eröffnet',
            ];
        }

        // Geschlossene Spread-Gruppen
        $closedSpreads = TradeClose::on($closeConn)
            ->where('environment', $env)
            ->where($symbolMatch)
            ->where($hasStrategy)
            ->select('opt_strategyID', 'opt_strategyName',
                DB::raw('MAX(dateTimeClose) as latest_date'), DB::raw('COUNT(*) as leg_count'))
            ->groupBy('opt_strategyID', 'opt_strategyName')
            ->orderByDesc('latest_date')
            ->limit(10)
            ->get();

        foreach ($closedSpreads as $spread) {
            $this->linkCandidates[] = [
                'type'  => 'spread',
                'id'    => $spread->opt_strategyID,
                'label' => $this->describeSpread($spread, 'latest_date') . ' · geschlossen',
            ];
        }

        $closes = TradeClose::on($closeConn)
            ->where('environment', $env)
            ->where($symbolMatch)
            ->where($noStrategy)
            ->orderByDesc('dateTimeClose')
            ->limit(15)
            ->get();

        foreach ($closes as $trade) {
            $this->linkCandidates[] = [
                'type'  => 'closed',
                'id'    => $trade->id,
                'label' => $this->describeTrade($trade, 'dateTimeClose') . ' · geschlossen',
            ];
        }
    }

    private function describeTrade($trade, string $dateField): string
    {
        $date = $trade->{$dateField} ? Carbon::parse($trade->{$dateField})->format('d.m.Y') : '?';

        if ($trade->assetCategory === 'OPT' || $trade->assetCategory === 'FOP') {
            $strike  = $trade->strike ? number_format((float) $trade->strike, 2, ',', '') : '';
            $expiry  = $trade->expiry ? Carbon::parse($trade->expiry)->format('d.m.y') : '';
            return trim("{$strike}{$trade->putCall} exp.{$expiry} · {$date}");
        }

        return "Aktie · {$date}";
    }

    private function describeSpread($spread, string $dateField): string
    {
        $date = $spread->{$dateField} ? Carbon::parse($spread->{$dateField})->format('d.m.Y') : '?';
        $name = $spread->opt_strategyName ?: 'Spread';
        return "{$name} ({$spread->leg_count} Legs) · {$date}";
    }

    public function updatedSymbol(): void
    {
        $this->refreshLinkCandidates();
    }

    /**
     * Verknüpft einen bestehenden oder neuen Eintrag nachträglich mit einem
     * konkreten Trade oder einer Spread-Gruppe — überschreibt keine bereits
     * eingegebenen Felder (Emotion, Motivation, Lektion bleiben, wie
     * prefillFromCampaign in LogForm).
     */
    public function linkToTrade(string $type, string $id): void
    {
        if (in_array($type, ['open_spread', 'spread'], true)) {
            $this->prefillStrategyId = $id;
            $this->prefillTradeId    = null;
        } else {
            $this->prefillTradeId    = $id;
            $this->prefillStrategyId = null;
        }
        $this->prefillTradeType = $type;
        $this->linkCandidates   = [];
    }

    public function unlinkTrade(): void
    {
        $this->prefillTradeId    = null;
        $this->prefillStrategyId = null;
        $this->prefillTradeType  = null;
        $this->refreshLinkCandidates();
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
        $this->rulesList             = [];
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

        foreach ($this->rulesList as $rule) {
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
        if (!$this->moduleInstalled) {
            return $this->moduleNotInstalledView();
        }

        return view('livewire.psychology.psychology-form');
    }
}
