<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Traits\FilterableByTime;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CampaignLedger;
use App\Traits\ManagesEnvironment;

class CampaignDetail extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $campaignId;
    public $perPage = 20;
    public Campaign $campaign;

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount($campaignId)
    {
        $this->mountFilterableByTime();
        $this->mountManagesEnvironment();
        $this->campaignId = $campaignId;
        $this->campaign = Campaign::findOrFail($campaignId);
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
            ? ($this->campaign->symbol ?? $entry->symbol ?? '')
            : ($entry->symbol ?? '');
        $dir    = $entry->buySell ?? '';

        // Optionsdetails: strike aus DB wenn vorhanden, sonst OCC-Symbol parsen (z.B. bei OPTION_EXERCISE)
        $optDetail = '';
        if ((float)($entry->strike ?? 0) > 0) {
            $strike    = number_format((float) $entry->strike, 0);
            $putCall   = $entry->putCall ?? '';
            $expiryStr = ($entry->expiry && $entry->expiry->year > 1900)
                         ? $entry->expiry->format('d.m.y') : '';
            $optDetail = " {$strike} {$putCall}" . ($expiryStr ? " {$expiryStr}" : '');
        } elseif (!empty($entry->symbol) && preg_match('/^\S+\s+(\d{2})(\d{2})(\d{2})([PC])(\d{8})$/', $entry->symbol, $m)) {
            $parsedStrike  = number_format((int) $m[5] / 1000, 0);
            $parsedPutCall = $m[4];
            $parsedExpiry  = \Carbon\Carbon::createFromDate('20' . $m[1], $m[2], $m[3])->format('d.m.y');
            $optDetail     = " {$parsedStrike} {$parsedPutCall} {$parsedExpiry}";
        }

        $priceDesc = ($entry->tradePrice && (float) $entry->tradePrice != 0)
            ? ' @ ' . number_format((float) $entry->tradePrice, 2, ',', '.')
            : '';

        return match ($entry->action) {
            'TRADE_OPEN' => match ($entry->assetCategory) {
                'STK' => $dir === 'BUY'
                    ? "Kauf {$qtyFmt} - {$symbol} Aktien{$priceDesc}"
                    : "Short {$qtyFmt} - {$symbol} Aktien{$priceDesc}",
                'OPT' => $dir === 'SELL'
                    ? "Prämie kassiert: {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}"
                    : "Kauf {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
                default => ($dir === 'BUY' ? 'Kauf' : 'Verkauf') . " {$symbol}{$priceDesc}",
            },
            'TRADE_CLOSE_FULL' => match ($entry->assetCategory) {
                'STK' => $dir === 'SELL'
                    ? "Verkauf {$qtyFmt} - {$symbol} Aktien{$priceDesc}"
                    : "Cover {$qtyFmt} - {$symbol} Aktien{$priceDesc}",
                'OPT' => "Rückkauf {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
                default => "Schließen {$qtyFmt}x - {$symbol}{$priceDesc}",
            },
            'TRADE_REVERSAL_OPEN' => match ($entry->assetCategory) {
                'STK' => $dir === 'SELL'
                    ? "Reversal → Short {$qtyFmt} - {$symbol}{$priceDesc}"
                    : "Reversal → Long {$qtyFmt} - {$symbol}{$priceDesc}",
                default => "Reversal {$symbol}{$priceDesc}",
            },
            'OPTION_EXPIRATION' => "{$symbol}{$optDetail} verfallen",
            'OPTION_EXERCISE'   => "Option ausgeübt: {$symbol}{$optDetail}",
            'CASH_SETTLEMENT'   => "Cash Settlement: {$symbol}{$optDetail}",
            default             => ($entry->action ?? '?') . ' ' . $symbol,
        };
    }

    public function render()
    {
        $query = CampaignLedger::where('campaign_id', $this->campaignId)
            ->where('environment', $this->selectedEnvironment);
        $query = $this->applyTimeFilter($query, 'dateTime');

        $metrics = (clone $query)->selectRaw('
            SUM(CASE WHEN action = "CASH_SETTLEMENT" THEN proceeds ELSE netcash END) as total_cashflow,
            SUM(commission) as total_commission,
            SUM(realized_pnl) as total_pnl,
            SUM(CASE WHEN assetCategory = "STK" THEN inventory_delta ELSE 0 END) as shares_balance,
            SUM(CASE WHEN assetCategory = "OPT" THEN inventory_delta ELSE 0 END) as options_balance
        ')->first();

        $ledgerEntries = $query->orderBy('dateTime', 'asc')
            ->paginate($this->perPage);

        // Laufender Aktien-Bestand: Summe aller STK-Einträge vor der ersten Zeile dieser Seite
        $startingStockBalance = 0;
        if ($ledgerEntries->count() > 0) {
            $startingStockBalance = (float) ((clone $query)
                ->where('assetCategory', 'STK')
                ->where('id', '<', $ledgerEntries->first()->id)
                ->sum('inventory_delta') ?? 0);
        }

        return view('livewire.campaigns.campaign-detail', [
            'ledgerEntries'        => $ledgerEntries,
            'metrics'              => $metrics,
            'startingStockBalance' => $startingStockBalance,
        ]);
    }
}
