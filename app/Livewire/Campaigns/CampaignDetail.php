<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Traits\FilterableByTime;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CampaignLedger;
use App\Models\TradePlan;
use App\Traits\ManagesEnvironment;

class CampaignDetail extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    public $campaignId;
    public $perPage = 5; // Tage pro Seite
    public Campaign $campaign;
    public string $backUrl = '';

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => '$refresh',
    ];

    public function mount($campaignId)
    {
        $this->mountFilterableByTime();
        $this->mountManagesEnvironment();
        $this->campaignId = $campaignId;
        $this->campaign   = Campaign::findOrFail($campaignId);

        // Herkunfts-URL für Zurück-Button — nur interne URLs akzeptieren
        $previous        = url()->previous();
        $this->backUrl   = str_starts_with($previous, url('/'))
            ? $previous
            : route('campaigns.campaigns');
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
            ? ($this->campaign->symbol ?? $entry->symbol ?? '')
            : ($entry->symbol ?? '');
        $dir        = $entry->buySell ?? '';
        $isPartial  = str_ends_with($entry->action ?? '', '_PARTIAL');
        $teilSuffix = $isPartial ? ' (Teil)' : '';
        $sign       = (float) $entry->inventory_delta > 0 ? '+' : '';

        // Optionsdetails: strike aus DB wenn vorhanden, sonst OCC-Symbol parsen (z.B. bei OPTION_EXERCISE)
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
            'OPTION_EXPIRATION' => "{$symbol}{$optDetail} verfallen",
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
        $linkedPlan = TradePlan::where('campaign_id', $this->campaignId)
            ->where('environment', $this->selectedEnvironment)
            ->first();

        $query = CampaignLedger::where('campaign_id', $this->campaignId)
            ->where('environment', $this->selectedEnvironment);
        $query = $this->applyTimeFilter($query, 'dateTime');

        $metrics = (clone $query)->selectRaw('
            SUM(CASE WHEN action = "CASH_SETTLEMENT" THEN proceeds ELSE netcash END) as total_cashflow,
            SUM(commission) as total_commission,
            SUM(realized_pnl) as total_pnl,
            SUM(realized_pnl + commission) as total_net_pnl,
            SUM(CASE WHEN assetCategory = "STK" THEN inventory_delta ELSE 0 END) as shares_balance,
            SUM(CASE WHEN assetCategory = "OPT" THEN inventory_delta ELSE 0 END) as options_balance
        ')->first();

        // Alle Einträge (unpaginiert) für Chart und Tages-Zusammenfassung
        $allEntries = (clone $query)->orderBy('dateTime', 'asc')->get();

        // Tages-Zusammenfassung (absteigend: neuestes Datum zuerst)
        $dailySummaries = $allEntries
            ->groupBy(fn($e) => \Carbon\Carbon::parse($e->dateTime)->format('Y-m-d'))
            ->map(fn($day, $dateKey) => [
                'date'           => \Carbon\Carbon::parse($dateKey),
                'cashflow'       => $day->sum(fn($e) => $e->action === 'CASH_SETTLEMENT'
                    ? (float) $e->proceeds : (float) $e->netcash),
                'commission'     => $day->sum(fn($e) => (float) $e->commission),
                'pnl'            => $day->sum(fn($e) => (float) $e->realized_pnl),
                'net_pnl'        => $day->sum(fn($e) => (float) $e->realized_pnl + (float) $e->commission),
                'count'          => $day->count(),
                'has_settlement' => $day->contains('action', 'CASH_SETTLEMENT'),
                'has_expiration' => $day->contains('action', 'OPTION_EXPIRATION'),
            ])
            ->sortKeysDesc();

        // Chart-Daten: kumulativer P&L je Tag (nur Tage mit realisiertem P&L)
        $running   = 0.0;
        $chartData = [];

        $startLabel = $this->campaign->start_date
            ? \Carbon\Carbon::parse($this->campaign->start_date)->format('d.m.y')
            : null;
        if ($startLabel) {
            $chartData[] = ['label' => $startLabel, 'cumul' => 0.0];
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

        // Tages-basierte Pagination: immer ganze Tage anzeigen
        $daysPaginator = (clone $query)
            ->selectRaw('DATE(dateTime) as date_key')
            ->groupBy('date_key')
            ->orderByRaw('date_key DESC')
            ->paginate($this->perPage);

        $visibleDates = $daysPaginator->pluck('date_key');

        $ledgerEntries = (clone $query)
            ->whereIn(\DB::raw('DATE(dateTime)'), $visibleDates)
            ->orderByRaw('DATE(dateTime) DESC, TIME(dateTime) ASC')
            ->get();

        // STK-Startbestand pro Tag: aus allEntries aufsteigend vorberechnet
        $dayStartBalances = [];
        $stkBalance = 0.0;
        $allEntries
            ->groupBy(fn($e) => \Carbon\Carbon::parse($e->dateTime)->format('Y-m-d'))
            ->sortKeys()
            ->each(function ($dayEntries, $dateKey) use (&$stkBalance, &$dayStartBalances) {
                $dayStartBalances[$dateKey] = $stkBalance;
                foreach ($dayEntries->where('assetCategory', 'STK') as $e) {
                    $stkBalance += (float) $e->inventory_delta;
                }
            });

        return view('livewire.campaigns.campaign-detail', [
            'ledgerEntries'    => $ledgerEntries,
            'totalEntries'     => $allEntries->count(),
            'daysPaginator'    => $daysPaginator,
            'metrics'          => $metrics,
            'dayStartBalances' => $dayStartBalances,
            'dailySummaries'   => $dailySummaries,
            'chartData'        => $chartData,
            'linkedPlan'       => $linkedPlan,
        ]);
    }

    // Strike als Dezimalzahl nur mit nötigen Stellen (2.5 → "2,5", 5.0 → "5")
    private function formatStrike(float $strike): string
    {
        if (fmod($strike, 1) == 0) {
            return number_format($strike, 0);
        }
        return rtrim(number_format($strike, 3, ',', ''), '0');
    }
}
