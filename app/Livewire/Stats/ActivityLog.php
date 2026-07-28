<?php

namespace App\Livewire\Stats;

use App\Models\CampaignLedger;
use App\Traits\ManagesEnvironment;
use Carbon\Carbon;
use Livewire\Component;

class ActivityLog extends Component
{
    use ManagesEnvironment;

    // Rollierendes Fenster: die letzten $days Tage bis heute. Wächst per loadMore() in 7er-Schritten.
    public int $days = 7;

    // Rückmeldung zur zuletzt geladenen Woche, falls sie keine Buchungen enthielt
    // (sonst wirkt der Button bei handelsfreien Wochen so, als würde er nichts tun)
    public ?string $emptyWeekLabel = null;

    protected $listeners = [
        'environment-changed' => '$refresh',
    ];

    public function mount()
    {
        $this->mountManagesEnvironment();
    }

    public function loadMore(): void
    {
        $addedRangeEnd = Carbon::today()->subDays($this->days)->endOfDay();
        $this->days += 7;
        $addedRangeStart = Carbon::today()->subDays($this->days - 1)->startOfDay();

        $hasNewEntries = CampaignLedger::where('environment', $this->selectedEnvironment)
            ->whereBetween('dateTime', [$addedRangeStart, $addedRangeEnd])
            ->exists();

        $this->emptyWeekLabel = $hasNewEntries
            ? null
            : $addedRangeStart->format('d.m.Y') . ' – ' . $addedRangeEnd->format('d.m.Y');
    }

    public function resetDays(): void
    {
        $this->days = 7;
        $this->emptyWeekLabel = null;
    }

    // identisch zu App\Livewire\Campaigns\CampaignDetail::getActionBadgeClass()
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

    // wie CampaignDetail::buildNarrative(), aber ohne Campaign-Kontext (mehrere Ticker gemischt) -> nutzt immer $entry->symbol
    public function buildNarrative($entry): string
    {
        $qty    = abs((float) $entry->inventory_delta);
        $qtyFmt = number_format($qty, 0, ',', '.');
        $symbol = $entry->symbol ?? '';
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
            $parsedExpiry  = Carbon::createFromDate('20' . $m[1], $m[2], $m[3])->format('d.m.y');
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

    private function formatStrike(float $strike): string
    {
        if (fmod($strike, 1) == 0) {
            return number_format($strike, 0);
        }
        return rtrim(number_format($strike, 3, ',', ''), '0');
    }

    public function render()
    {
        $end   = Carbon::today()->endOfDay();
        $start = (clone $end)->subDays($this->days - 1)->startOfDay();

        $query = CampaignLedger::where('environment', $this->selectedEnvironment)
            ->whereBetween('dateTime', [$start, $end]);

        $metrics = (clone $query)->selectRaw('
            COUNT(id) as total_entries,
            COUNT(DISTINCT symbol) as symbol_count,
            SUM(CASE WHEN action = "CASH_SETTLEMENT" THEN proceeds ELSE netcash END) as total_cashflow,
            SUM(commission) as total_commission,
            SUM(realized_pnl) as total_pnl,
            SUM(realized_pnl + commission) as total_net_pnl
        ')->first();

        // Ledger-Tabelle: neuestes Datum zuerst, innerhalb eines Tages chronologisch.
        // id als Tiebreaker, da mehrere Buchungen denselben Zeitstempel haben können
        // (z.B. mehrere OPTION_EXERCISE/STK-Events zur selben Sekunde) — sonst sortiert
        // MySQL Ties nicht stabil und die Reihenfolge kann zwischen Requests wechseln.
        $ledgerEntries = (clone $query)
            ->orderByRaw('DATE(dateTime) DESC, TIME(dateTime) ASC, id ASC')
            ->get();

        return view('livewire.stats.activity-log', [
            'ledgerEntries' => $ledgerEntries,
            'totalEntries'  => (clone $query)->count(),
            'metrics'       => $metrics,
            'periodStart'   => $start,
            'periodEnd'     => $end,
            'days'          => $this->days,
        ]);
    }
}
