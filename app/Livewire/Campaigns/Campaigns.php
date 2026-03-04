<?php

namespace App\Livewire\Campaigns;

use App\Traits\FilterableByTime;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Campaign;
use App\Models\CampaignLedger;
use App\Traits\ManagesEnvironment;
use Livewire\Attributes\On;

class Campaigns extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $search = '';
    public $selectedCampaignId = null;
    public $filterStatus = 'OPEN';
    public $perPage = 20;

    public function setStatus($status)
    {
        $this->filterStatus = $status;
        $this->selectedCampaignId = null;
        $this->resetPage();
    }

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => 'updateTimeRangeFromEvent',
    ];

    public function mount()
    {
        $this->mountFilterableByTime();
        $this->mountManagesEnvironment();
    }

    public function updateTimeRangeFromEvent($range)
    {
        $this->timeRange = $range;
        $this->resetPage();
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

    public function selectCampaign($id)
    {
        if ($this->selectedCampaignId === $id) {
            $this->selectedCampaignId = null;
        } else {
            $this->selectedCampaignId = $id;
            $this->resetPage('ledgerPage');

            // Dieser Befehl triggert das Javascript oben
            $this->dispatch('scroll-to-ledger');
        }
    }

    public function getActionBadgeClass($action)
    {
        return match ($action) {
            'BUY' => 'bg-primary text-white',
            'SELL' => 'bg-warning text-dark',
            'ASSIGNMENT' => 'bg-purple text-white',
            'EXPIRATION' => 'bg-secondary text-white',
            'VIRTUAL' => 'bg-info text-dark',
            default => 'bg-light text-dark border',
        };
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
        }], 'inventory_delta');

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
