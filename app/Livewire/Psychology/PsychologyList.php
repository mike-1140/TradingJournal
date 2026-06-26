<?php

namespace App\Livewire\Psychology;

use App\Models\PsychologyEntry;
use App\Traits\ManagesEnvironment;
use Livewire\Component;
use Livewire\WithPagination;

class PsychologyList extends Component
{
    use WithPagination, ManagesEnvironment;

    public string  $filterMotivation = '';
    public string  $search           = '';
    public int     $perPage          = 25;

    protected $queryString = [
        'filterMotivation' => ['except' => ''],
        'search'           => ['except' => ''],
    ];

    protected $listeners = [
        'environment-changed' => '$refresh',
    ];

    public function mount(): void
    {
        $this->mountManagesEnvironment();
    }

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterMotivation(): void { $this->resetPage(); }

    public function delete(int $id): void
    {
        $connection = PsychologyEntry::getConnectionForEnvironment($this->selectedEnvironment);
        $entry = PsychologyEntry::on($connection)->findOrFail($id);
        PsychologyComplianceResult::on($connection)->where('entry_id', $id)->delete();
        $entry->delete();
    }

    public function render()
    {
        $env        = $this->selectedEnvironment;
        $connection = PsychologyEntry::getConnectionForEnvironment($env);

        $query = PsychologyEntry::on($connection)
            ->with('complianceResults')
            ->where('environment', $env);

        if ($this->filterMotivation) {
            $query->where('motivation', $this->filterMotivation);
        }

        if ($this->search) {
            $query->where('symbol', 'like', '%' . $this->search . '%');
        }

        $entries = $query->orderBy('trade_date', 'desc')->paginate($this->perPage);

        $base = PsychologyEntry::on($connection)->where('environment', $env);
        $stats = [
            'total'      => (clone $base)->count(),
            'total_pnl'  => (clone $base)->sum('pnl'),
            'wins'       => (clone $base)->where('pnl', '>', 0)->count(),
            'with_tempt' => (clone $base)->whereHas('complianceResults', fn ($q) => $q->where('tempted', 1))->count(),
        ];

        return view('livewire.psychology.psychology-list', [
            'entries' => $entries,
            'stats'   => $stats,
        ]);
    }
}
