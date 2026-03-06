<?php

namespace App\Livewire\Campaigns;

use App\Traits\FilterableByTime;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CampaignLedger;
use App\Traits\ManagesEnvironment;

class GlobalSymbolDetail extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $symbol;
    public $perPage = 20;

    protected $queryString = [
        'globalPage' => ['except' => 1, 'as' => 'gp'],
    ];

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount($symbol)
    {
        $this->mountFilterableByTime();
        $this->mountManagesEnvironment();
        $this->symbol = strtoupper($symbol);
        $this->resetPage('globalPage');
    }
    public function getActionBadgeClass($action): string
    {
        return match($action) {
            'TRADE_OPEN'          => 'bg-primary text-white',
            'TRADE_CLOSE_FULL'    => 'bg-success text-white',
            'TRADE_REVERSAL_OPEN' => 'bg-warning text-dark',
            'OPTION_EXPIRATION'   => 'bg-secondary text-white',
            'OPTION_EXERCISE'     => 'bg-purple text-white',
            'CASH_SETTLEMENT'     => 'bg-info text-dark',
            default               => 'bg-light text-dark border',
        };
    }

    public function buildNarrative($entry): string
    {
        $qty    = abs((float) $entry->inventory_delta);
        $qtyFmt = number_format($qty, 0, ',', '.');
        $symbol = ($entry->assetCategory === 'OPT')
            ? $this->symbol
            : ($entry->symbol ?? '');
        $dir    = $entry->buySell ?? '';

        $optDetail = '';
        if ((float)($entry->strike ?? 0) > 0) {
            $strike    = number_format((float) $entry->strike, 0);
            $putCall   = $entry->putCall ?? '';
            $expiryStr = ($entry->expiry && $entry->expiry->year > 1900)
                         ? $entry->expiry->format('d.m.y') : '';
            $optDetail = " {$strike}{$putCall}" . ($expiryStr ? " {$expiryStr}" : '');
        } elseif (!empty($entry->symbol) && preg_match('/^\S+\s+(\d{2})(\d{2})(\d{2})([PC])(\d{8})$/', $entry->symbol, $m)) {
            $parsedStrike  = number_format((int) $m[5] / 1000, 0);
            $parsedPutCall = $m[4];
            $parsedExpiry  = \Carbon\Carbon::createFromDate('20' . $m[1], $m[2], $m[3])->format('d.m.y');
            $optDetail     = " {$parsedStrike}{$parsedPutCall} {$parsedExpiry}";
        }

        $priceDesc = ($entry->tradePrice && (float) $entry->tradePrice != 0)
            ? ' @ ' . number_format((float) $entry->tradePrice, 2, ',', '.')
            : '';

        return match ($entry->action) {
            'TRADE_OPEN' => match ($entry->assetCategory) {
                'STK' => $dir === 'BUY'
                    ? "Kauf {$qtyFmt} {$symbol} Aktien{$priceDesc}"
                    : "Short {$qtyFmt} {$symbol} Aktien{$priceDesc}",
                'OPT' => $dir === 'SELL'
                    ? "Prämie kassiert: {$symbol}{$optDetail}{$priceDesc}"
                    : "Kauf {$qtyFmt} {$symbol}{$optDetail}{$priceDesc}",
                default => ($dir === 'BUY' ? 'Kauf' : 'Verkauf') . " {$symbol}{$priceDesc}",
            },
            'TRADE_CLOSE_FULL' => match ($entry->assetCategory) {
                'STK' => $dir === 'SELL'
                    ? "Verkauf {$qtyFmt} {$symbol} Aktien{$priceDesc}"
                    : "Cover {$qtyFmt} {$symbol} Aktien{$priceDesc}",
                'OPT' => "Rückkauf {$symbol}{$optDetail}{$priceDesc}",
                default => "Schließen {$symbol}{$priceDesc}",
            },
            'TRADE_REVERSAL_OPEN' => match ($entry->assetCategory) {
                'STK' => $dir === 'SELL'
                    ? "Reversal → Short {$qtyFmt} {$symbol}{$priceDesc}"
                    : "Reversal → Long {$qtyFmt} {$symbol}{$priceDesc}",
                default => "Reversal {$symbol}{$priceDesc}",
            },
            'OPTION_EXPIRATION' => "{$symbol}{$optDetail} verfallen wertlos",
            'OPTION_EXERCISE'   => "Option ausgeübt: {$symbol}{$optDetail}",
            'CASH_SETTLEMENT'   => "Cash Settlement: {$symbol}{$optDetail}",
            default             => ($entry->action ?? '?') . ' ' . $symbol,
        };
    }
    public function render()
    {
        // Query über das Symbol (via Relationship zur Campaign)
        $query = CampaignLedger::where('environment', $this->selectedEnvironment);
        $query = $this->applyTimeFilter($query, 'dateTime');

        $query = $query->whereHas('campaign', function($q) {
                $q->where('symbol', $this->symbol);
            });

        // Metriken aggregieren (Bedingte Summierung für Aktien/Optionen)
        $metrics = (clone $query)->selectRaw('
            SUM(netcash) as total_netcash,
            SUM(proceeds) as total_proceeds,
            SUM(commission) as total_commission,
            SUM(realized_pnl) as total_pnl,
            SUM(inventory_delta) as total_shares,
            SUM(CASE WHEN assetCategory = "STK" THEN inventory_delta ELSE 0 END) as shares_balance,
            SUM(CASE WHEN assetCategory = "OPT" THEN inventory_delta ELSE 0 END) as options_balance,
            COUNT(DISTINCT campaign_id) as campaign_count
        ')->first();

        $ledgerEntries = $query->orderBy('dateTime', 'asc')
            // ->paginate($this->perPage, ['*'], 'globalPage');
            ->paginate(100, ['*'], 'ledgerPage');

        return view('livewire.campaigns.global-symbol-detail', [
            'ledgerEntries' => $ledgerEntries,
            'metrics' => $metrics
        ]);
    }
}
