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
        $this->mountManagesEnvironment();
        $this->symbol = strtoupper($symbol);
        $this->resetPage('globalPage');
    }
    public function getActionBadgeClass($action): string
    {
        if (str_ends_with($action, '_CLOSE') || str_ends_with($action, '_PARTIAL')) {
            return 'bg-success text-white';
        }
        return match($action) {
            'OPT_PUT_SELL', 'OPT_CALL_SELL',
            'TRADE_OPEN'                      => 'bg-primary text-white',
            'STK_BUY', 'OPT_PUT_BUY',
            'OPT_CALL_BUY'                    => 'bg-info text-dark',
            'STK_SELL'                        => 'bg-warning text-dark',
            'TRADE_CLOSE_FULL',
            'TRADE_CLOSE_PARTIAL'             => 'bg-success text-white',
            'TRADE_REVERSAL_OPEN'             => 'bg-warning text-dark',
            'OPTION_EXPIRATION'               => 'bg-secondary text-white',
            'OPTION_EXERCISE'                 => 'bg-purple text-white',
            'CASH_SETTLEMENT'                 => 'bg-info text-dark',
            'STOCK_SPLIT'                     => 'bg-dark text-white',
            default                           => 'bg-light text-dark border',
        };
    }

    public function buildNarrative($entry): string
    {
        $qty    = abs((float) $entry->inventory_delta);
        $qtyFmt = number_format($qty, 0, ',', '.');
        $symbol = ($entry->assetCategory === 'OPT')
            ? $this->symbol
            : ($entry->symbol ?? '');
        $dir        = $entry->buySell ?? '';
        $isPartial  = str_ends_with($entry->action ?? '', '_PARTIAL');
        $teilSuffix = $isPartial ? ' (Teil)' : '';
        $sign       = (float) $entry->inventory_delta > 0 ? '+' : '';

        $optDetail = '';
        if ((float)($entry->strike ?? 0) > 0) {
            $strike    = $this->formatStrike((float) $entry->strike);
            $putCall   = $entry->putCall ?? '';
            $expiryStr = ($entry->expiry && $entry->expiry->year > 1900)
                         ? $entry->expiry->format('d.m.y') : '';
            $optDetail = " {$strike} {$putCall}" . ($expiryStr ? " {$expiryStr}" : '');
        } elseif (!empty($entry->symbol) && preg_match('/^\S+\s+(\d{2})(\d{2})(\d{2})([PC])(\d{8})$/', $entry->symbol, $m)) {
            $parsedStrike  = $this->formatStrike((int) $m[5] / 1000);
            $parsedPutCall = $m[4];
            $parsedExpiry  = \Carbon\Carbon::createFromDate('20' . $m[1], $m[2], $m[3])->format('d.m.y');
            $optDetail     = " {$parsedStrike} {$parsedPutCall} {$parsedExpiry}";
        }

        $priceDesc = ($entry->tradePrice && (float) $entry->tradePrice != 0)
            ? ' @ ' . number_format((float) $entry->tradePrice, 2, ',', '.')
            : '';

        return match ($entry->action) {
            // --- Aktien: Eröffnung ---
            'STK_BUY'                            => "Kauf {$qtyFmt} - {$symbol} Aktien{$priceDesc}",
            'STK_SELL'                           => "Short {$qtyFmt} - {$symbol} Aktien{$priceDesc}",
            // --- Aktien: Schließung ---
            'STK_SELL_CLOSE', 'STK_SELL_PARTIAL' => "Verkauf{$teilSuffix} {$qtyFmt} - {$symbol} Aktien{$priceDesc}",
            'STK_BUY_CLOSE',  'STK_BUY_PARTIAL'  => "Cover{$teilSuffix} {$qtyFmt} - {$symbol} Aktien{$priceDesc}",
            // --- Optionen: Eröffnung ---
            'OPT_PUT_SELL'                       => "Put Prämie: {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
            'OPT_PUT_BUY'                        => "Put Kauf: {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
            'OPT_CALL_SELL'                      => "Call Prämie: {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
            'OPT_CALL_BUY'                       => "Call Kauf: {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
            // --- Optionen: Rückkauf (Buy-to-Close einer Short-Position) ---
            'OPT_PUT_BUY_CLOSE',  'OPT_PUT_BUY_PARTIAL'  => "Put Rückkauf{$teilSuffix}: {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
            'OPT_CALL_BUY_CLOSE', 'OPT_CALL_BUY_PARTIAL' => "Call Rückkauf{$teilSuffix}: {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
            // --- Optionen: Sell-to-Close einer Long-Position ---
            'OPT_PUT_SELL_CLOSE',  'OPT_PUT_SELL_PARTIAL'  => "Put Schließen{$teilSuffix}: {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
            'OPT_CALL_SELL_CLOSE', 'OPT_CALL_SELL_PARTIAL' => "Call Schließen{$teilSuffix}: {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
            // --- Options-Events ---
            'OPTION_EXPIRATION' => "{$symbol}{$optDetail} verfallen wertlos",
            'OPTION_EXERCISE'   => "Option ausgeübt: {$symbol}{$optDetail}",
            'CASH_SETTLEMENT'   => "Cash Settlement: {$symbol}{$optDetail}",
            // --- Split & Reversal ---
            'STOCK_SPLIT'       => "Aktien-Split: {$symbol} ({$sign}{$qtyFmt})",
            'TRADE_REVERSAL_OPEN' => match ($entry->assetCategory) {
                'STK' => $dir === 'SELL'
                    ? "Reversal → Short {$qtyFmt} - {$symbol}{$priceDesc}"
                    : "Reversal → Long {$qtyFmt} - {$symbol}{$priceDesc}",
                default => "Reversal {$symbol}{$priceDesc}",
            },
            // --- Legacy-Actions (Fallback für Daten vor DB-Rebuild) ---
            'TRADE_OPEN' => match ($entry->assetCategory) {
                'STK' => $dir === 'BUY'
                    ? "Kauf {$qtyFmt} - {$symbol} Aktien{$priceDesc}"
                    : "Short {$qtyFmt} - {$symbol} Aktien{$priceDesc}",
                'OPT' => $dir === 'SELL'
                    ? "Prämie kassiert: {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}"
                    : "Kauf {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
                default => ($dir === 'BUY' ? 'Kauf' : 'Verkauf') . " {$symbol}{$priceDesc}",
            },
            'TRADE_CLOSE_FULL', 'TRADE_CLOSE_PARTIAL' => match ($entry->assetCategory) {
                'STK' => $dir === 'SELL'
                    ? "Verkauf {$qtyFmt} - {$symbol} Aktien{$priceDesc}"
                    : "Cover {$qtyFmt} - {$symbol} Aktien{$priceDesc}",
                'OPT' => "Rückkauf {$qtyFmt}x - {$symbol}{$optDetail}{$priceDesc}",
                default => "Schließen {$qtyFmt}x - {$symbol}{$priceDesc}",
            },
            default => ($entry->action ?? '?') . ' ' . $symbol,
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
            SUM(realized_pnl + commission) as total_net_pnl,
            SUM(inventory_delta) as total_shares,
            SUM(CASE WHEN assetCategory = "STK" THEN inventory_delta ELSE 0 END) as shares_balance,
            SUM(CASE WHEN assetCategory = "OPT" THEN inventory_delta ELSE 0 END) as options_balance,
            COUNT(DISTINCT campaign_id) as campaign_count
        ')->first();

        // Alle Einträge unpaginiert für den Chart
        $allEntries = (clone $query)->orderBy('dateTime', 'asc')->get();

        // Chart-Daten: kumulativer P&L je Tag (über alle Kampagnen des Symbols)
        $running   = 0.0;
        $chartData = [];

        $firstEntry = $allEntries->first();
        if ($firstEntry) {
            $chartData[] = [
                'label' => \Carbon\Carbon::parse($firstEntry->dateTime)->format('d.m.y'),
                'cumul' => 0.0,
            ];
        }

        $allEntries
            ->filter(fn($e) => (float) $e->realized_pnl != 0)
            ->groupBy(fn($e) => \Carbon\Carbon::parse($e->dateTime)->format('Y-m-d'))
            ->each(function ($day, $dateKey) use (&$running, &$chartData) {
                $running    += $day->sum(fn($e) => (float) $e->realized_pnl);
                $chartData[] = [
                    'label' => \Carbon\Carbon::parse($dateKey)->format('d.m.y'),
                    'cumul' => round($running, 2),
                ];
            });

        $ledgerEntries = $query->orderBy('dateTime', 'asc')
            ->paginate(100, ['*'], 'ledgerPage');

        return view('livewire.campaigns.global-symbol-detail', [
            'ledgerEntries' => $ledgerEntries,
            'metrics'       => $metrics,
            'chartData'     => $chartData,
        ]);
    }

    private function formatStrike(float $strike): string
    {
        if (fmod($strike, 1) == 0) {
            return number_format($strike, 0);
        }
        return rtrim(number_format($strike, 3, ',', ''), '0');
    }
}
