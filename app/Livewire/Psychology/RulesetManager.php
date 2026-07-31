<?php

namespace App\Livewire\Psychology;

use App\Models\PsychologyRuleset;
use App\Models\PsychologyRule;
use App\Traits\ManagesEnvironment;
use App\Traits\RequiresPsychologyModule;
use Livewire\Component;

class RulesetManager extends Component
{
    use ManagesEnvironment, RequiresPsychologyModule;

    // Auswahl
    public ?int $selectedRulesetId = null;

    // Ruleset-Formular (neu + bearbeiten)
    public bool $showRulesetForm = false;
    public ?int $editingRulesetId = null;
    public string $rsName         = '';
    public string $rsStrategyType = '';
    public string $rsDescription  = '';
    public bool   $rsIsActive     = true;

    // Regel-Formular (neu + bearbeiten)
    public ?int $editingRuleId  = null;
    public string $rKey         = '';
    public string $rLabel       = '';
    public string $rDescription = '';
    public string $rErrorType   = '';
    public int    $rSortOrder   = 0;
    public bool   $showRuleForm = false;

    protected $listeners = [
        'environment-changed' => 'resetAll',
    ];

    public function mount(): void
    {
        $this->mountManagesEnvironment();
    }

    public function resetAll(): void
    {
        $this->selectedRulesetId = null;
        $this->cancelRulesetForm();
        $this->cancelRuleForm();
    }

    // ── Ruleset CRUD ──────────────────────────────────────────────────────────

    public function startNewRuleset(): void
    {
        $this->editingRulesetId = null;
        $this->rsName           = '';
        $this->rsStrategyType   = '';
        $this->rsDescription    = '';
        $this->rsIsActive       = true;
        $this->showRulesetForm  = true;
    }

    public function startEditRuleset(int $id): void
    {
        $conn    = PsychologyRuleset::getConnectionForEnvironment($this->selectedEnvironment);
        $ruleset = PsychologyRuleset::on($conn)->findOrFail($id);

        $this->editingRulesetId = $id;
        $this->rsName           = $ruleset->name;
        $this->rsStrategyType   = $ruleset->strategy_type ?? '';
        $this->rsDescription    = $ruleset->description ?? '';
        $this->rsIsActive       = (bool) $ruleset->is_active;
        $this->showRulesetForm  = true;
    }

    public function saveRuleset(): void
    {
        $this->validate([
            'rsName' => 'required|max:100',
            'rsStrategyType' => 'nullable|max:50',
            'rsDescription'  => 'nullable|max:500',
        ]);

        $conn = PsychologyRuleset::getConnectionForEnvironment($this->selectedEnvironment);
        $data = [
            'name'          => $this->rsName,
            'strategy_type' => $this->rsStrategyType ?: null,
            'description'   => $this->rsDescription ?: null,
            'is_active'     => $this->rsIsActive,
            'environment'   => $this->selectedEnvironment,
        ];

        if ($this->editingRulesetId) {
            PsychologyRuleset::on($conn)->findOrFail($this->editingRulesetId)->update($data);
        } else {
            $maxOrder = PsychologyRuleset::on($conn)->where('environment', $this->selectedEnvironment)->max('sort_order') ?? 0;
            $data['sort_order'] = $maxOrder + 1;
            PsychologyRuleset::on($conn)->create($data);
        }

        $this->cancelRulesetForm();
    }

    public function deleteRuleset(int $id): void
    {
        $conn = PsychologyRuleset::getConnectionForEnvironment($this->selectedEnvironment);
        PsychologyRuleset::on($conn)->findOrFail($id)->delete();
        if ($this->selectedRulesetId === $id) {
            $this->selectedRulesetId = null;
        }
    }

    public function toggleActive(int $id): void
    {
        $conn    = PsychologyRuleset::getConnectionForEnvironment($this->selectedEnvironment);
        $ruleset = PsychologyRuleset::on($conn)->findOrFail($id);
        $ruleset->update(['is_active' => !$ruleset->is_active]);
    }

    public function selectRuleset(int $id): void
    {
        $this->selectedRulesetId = ($this->selectedRulesetId === $id) ? null : $id;
        $this->cancelRuleForm();
    }

    public function cancelRulesetForm(): void
    {
        $this->showRulesetForm  = false;
        $this->editingRulesetId = null;
        $this->rsName           = '';
        $this->rsStrategyType   = '';
        $this->rsDescription    = '';
        $this->rsIsActive       = true;
    }

    // ── Regel CRUD ────────────────────────────────────────────────────────────

    public function startNewRule(): void
    {
        $this->editingRuleId = null;
        $this->rKey          = '';
        $this->rLabel        = '';
        $this->rDescription  = '';
        $this->rErrorType    = '';
        $this->rSortOrder    = 0;
        $this->showRuleForm  = true;
    }

    public function startEditRule(int $id): void
    {
        $conn = PsychologyRule::getConnectionForEnvironment($this->selectedEnvironment);
        $rule = PsychologyRule::on($conn)->findOrFail($id);

        $this->editingRuleId = $id;
        $this->rKey          = $rule->rule_key;
        $this->rLabel        = $rule->label;
        $this->rDescription  = $rule->description ?? '';
        $this->rErrorType    = $rule->error_type ?? '';
        $this->rSortOrder    = $rule->sort_order ?? 0;
        $this->showRuleForm  = true;
    }

    public function saveRule(): void
    {
        $this->validate([
            'rLabel'    => 'required|max:200',
            'rKey'      => 'required|max:50',
            'rErrorType' => 'nullable|in:greed,fear,hope,impulse,discipline',
        ]);

        $conn = PsychologyRule::getConnectionForEnvironment($this->selectedEnvironment);
        $data = [
            'ruleset_id'  => $this->selectedRulesetId,
            'rule_key'    => $this->rKey,
            'label'       => $this->rLabel,
            'description' => $this->rDescription ?: null,
            'error_type'  => $this->rErrorType ?: null,
            'sort_order'  => $this->rSortOrder,
        ];

        if ($this->editingRuleId) {
            PsychologyRule::on($conn)->findOrFail($this->editingRuleId)->update($data);
        } else {
            PsychologyRule::on($conn)->create($data);
        }

        $this->cancelRuleForm();
    }

    public function deleteRule(int $id): void
    {
        $conn = PsychologyRule::getConnectionForEnvironment($this->selectedEnvironment);
        PsychologyRule::on($conn)->findOrFail($id)->delete();
    }

    public function cancelRuleForm(): void
    {
        $this->showRuleForm  = false;
        $this->editingRuleId = null;
        $this->rKey          = '';
        $this->rLabel        = '';
        $this->rDescription  = '';
        $this->rErrorType    = '';
        $this->rSortOrder    = 0;
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        if ($this->psychologyModuleMissing()) {
            return $this->moduleNotInstalledView();
        }

        $conn     = PsychologyRuleset::getConnectionForEnvironment($this->selectedEnvironment);
        $rulesets = PsychologyRuleset::on($conn)
            ->where('environment', $this->selectedEnvironment)
            ->withCount('rules')
            ->orderBy('sort_order')
            ->get();

        $selectedRules = null;
        if ($this->selectedRulesetId) {
            $ruleConn      = PsychologyRule::getConnectionForEnvironment($this->selectedEnvironment);
            $selectedRules = PsychologyRule::on($ruleConn)
                ->where('ruleset_id', $this->selectedRulesetId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return view('livewire.psychology.ruleset-manager', [
            'rulesets'      => $rulesets,
            'selectedRules' => $selectedRules,
        ]);
    }
}
