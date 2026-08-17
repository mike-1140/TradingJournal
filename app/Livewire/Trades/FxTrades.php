<?php

namespace App\Livewire\Trades;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TradeFx;
use App\Traits\ManagesEnvironment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class FxTrades extends Component
{
    use WithPagination, ManagesEnvironment;

    public $search = '';
    public $sortField = 'dateTime';
    public $sortDirection = 'desc';
    public $perPage = 25;
    public $filterQuoteCurrency = '';

    protected $queryString = [
        'search'        => ['except' => ''],
        'sortField'     => ['except' => 'dateTime'],
        'sortDirection' => ['except' => 'desc'],
    ];

    protected $listeners = [
        'environment-changed' => '$refresh',
    ];

    public function mount()
    {
        $this->mountManagesEnvironment();
    }

    public function updatingSearch()              { $this->resetPage(); }
    public function updatingFilterQuoteCurrency()  { $this->resetPage(); }
    public function updatingPerPage()              { $this->resetPage(); }

    // Whitelist — sortField/sortDirection kommen vom Client (Livewire-Property + URL-Query)
    private const SORTABLE_FIELDS = ['dateTime', 'symbol', 'fxRate'];

    public function sortBy($field)
    {
        if (!in_array($field, self::SORTABLE_FIELDS, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Fasst Fills mit derselben ibOrderID zu einer Order zusammen (mehrere
     * Teilausfuehrungen einer einzigen IBKR-FX-Order landen sonst als lauter
     * einzelne, schwer lesbare Zeilen in der Liste). Fills ohne ibOrderID
     * (aeltere Importe) bleiben je ihr eigener Ein-Zeilen-"Block".
     */
    private function groupByOrder(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn ($row) => $row->ibOrderID ?: 'single-' . $row->id)
            ->map(function (Collection $fills) {
                $fills = $fills->sortBy('dateTime')->values();
                $baseAmount = $fills->sum('baseAmount');
                $quoteAmount = $fills->sum('quoteAmount');

                return (object) [
                    'ibOrderID'     => $fills->first()->ibOrderID,
                    'fills'         => $fills,
                    'isMulti'       => $fills->count() > 1,
                    'dateTime'      => $fills->min('dateTime'),
                    'symbol'        => $fills->first()->symbol,
                    'baseCurrency'  => $fills->first()->baseCurrency,
                    'quoteCurrency' => $fills->first()->quoteCurrency,
                    'baseAmount'    => $baseAmount,
                    'quoteAmount'   => $quoteAmount,
                    'fxRate'        => $baseAmount != 0 ? abs($quoteAmount / $baseAmount) : 0,
                    'commission'    => $fills->sum('commission'),
                ];
            })
            ->values();
    }

    public function render()
    {
        $query = TradeFx::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment);

        // Suchfilter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('symbol', 'like', '%' . $this->search . '%')
                    ->orWhere('tradeID', 'like', '%' . $this->search . '%')
                    ->orWhere('ibOrderID', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterQuoteCurrency) {
            $query->where('quoteCurrency', $this->filterQuoteCurrency);
        }

        $rows = $query->get();

        // Statistiken (auf Einzel-Fill-Ebene, unabhaengig von der Gruppierung)
        $totalCount = TradeFx::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment)
            ->count();
        $totalCommission = TradeFx::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment)
            ->sum('commission');

        // Dropdown-Daten
        $quoteCurrencies = TradeFx::onEnvironment($this->selectedEnvironment)
            ->where('environment', $this->selectedEnvironment)
            ->distinct()
            ->pluck('quoteCurrency')
            ->filter();

        // Gruppieren (nach ibOrderID) + sortieren
        $groups = $this->groupByOrder($rows);

        $sortField = in_array($this->sortField, self::SORTABLE_FIELDS, true) ? $this->sortField : 'dateTime';
        $groups = $this->sortDirection === 'desc'
            ? $groups->sortByDesc($sortField)->values()
            : $groups->sortBy($sortField)->values();

        // Manuelle Pagination der Gruppen (Eloquent-paginate() funktioniert nicht
        // mehr, sobald in PHP nachtraeglich gruppiert wird)
        $page = Paginator::resolveCurrentPage() ?: 1;
        $trades = new LengthAwarePaginator(
            $groups->forPage($page, $this->perPage),
            $groups->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.trades.fx-trades', [
            'trades' => $trades,
            'totalCount' => $totalCount,
            'totalCommission' => $totalCommission,
            'quoteCurrencies' => $quoteCurrencies,
        ]);
    }
}
