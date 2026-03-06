<?php

namespace App\Livewire\Campaigns;

use App\Traits\FilterableByTime;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Campaign;
use App\Traits\ManagesEnvironment;
use Livewire\Attributes\On;

class Campaigns extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $search = '';
    public $filterStatus = 'OPEN';
    public $perPage = 20;

    public function setStatus($status)
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount()
    {
        $this->mountFilterableByTime();
        $this->mountManagesEnvironment();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[On('clear-search')]
    public function resetFilters()
    {
        $this->reset(['search']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Campaign::query()
            ->where('environment', $this->selectedEnvironment);
        $query = $this->applyTimeFilter($query, 'end_date');

        // NEU: Logik für Active, Pending und Closed zusammenführen
        if ($this->filterStatus === 'OPEN') {
            // Zeigt beides: Active und Pending
            $query->whereIn('status', ['ACTIVE', 'PENDING']);
        } elseif ($this->filterStatus === 'CLOSED') {
            $query->where('status', 'CLOSED');
        } elseif ($this->filterStatus !== 'ALL' && !empty($this->filterStatus)) {
            // Falls du doch mal einzeln filterst (z.B. über URL-Parameter)
            $query->where('status', $this->filterStatus);
        }

        // NEU: Wir laden das Summen-Feld "total_pnl" für jede Karte direkt mit
        // Wir filtern auch hier nach dem environment, damit die Summe stimmt
        $query->withSum(['ledgerEntries as total_pnl' => function($q) {
            $q->where('environment', $this->selectedEnvironment);
        }], 'realized_pnl')
            ->withSum(['ledgerEntries as shares_balance' => function($q) {
            $q->where('environment', $this->selectedEnvironment);
        }], 'inventory_delta')
            ->withSum(['ledgerEntries as total_proceeds' => function($q) {
            $q->where('environment', $this->selectedEnvironment);
        }], 'proceeds')
            ->withSum(['ledgerEntries as total_commission' => function($q) {
            $q->where('environment', $this->selectedEnvironment);
        }], 'commission');

        // In Campaigns.php render()
        $globalView = !empty($this->search) && strlen($this->search) >= 2;

        // Sortierung und Pagination für Kampagnen
        $campaigns = $query->orderBy('symbol', 'asc')
            ->orderBy('start_date', 'desc')
            ->paginate($this->perPage);

        return view('livewire.campaigns.campaigns-index', [
            'campaigns' => $campaigns,
            'globalView' => $globalView,
            'searchTerm' => strtoupper($this->search)
        ]);
    }
}
