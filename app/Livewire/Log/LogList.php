<?php

namespace App\Livewire\Log;

use App\Models\TradePlan;
use App\Traits\ManagesEnvironment;
use Livewire\Component;
use Livewire\WithPagination;

class LogList extends Component
{
    use WithPagination, ManagesEnvironment;

    public string $filterStatus = 'open';
    public string $search = '';
    public int $perPage = 25;

    protected $queryString = [
        'filterStatus' => ['except' => 'open'],
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

    public function render()
    {
        $connection = TradePlan::getConnectionForEnvironment($this->selectedEnvironment);

        $query = TradePlan::on($connection)
            ->withCount('legs')
            ->where('environment', $this->selectedEnvironment);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->search) {
            $query->where('underlying', 'like', '%' . $this->search . '%');
        }

        $logs = $query->orderBy('date_open', 'desc')->paginate($this->perPage);

        $baseStats = TradePlan::on($connection)->where('environment', $this->selectedEnvironment);
        $statistics = [
            'open_count'   => (clone $baseStats)->where('status', 'open')->count(),
            'closed_count' => (clone $baseStats)->where('status', 'closed')->count(),
            'realized_pnl' => (clone $baseStats)->where('status', 'closed')->sum('realized_pnl'),
        ];

        return view('livewire.log.log-list', [
            'logs'       => $logs,
            'statistics' => $statistics,
        ]);
    }
}
