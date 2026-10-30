<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Leitet aus den Ledger-Buchungen einer Kampagne die Eröffnungen ab — Grundlage für den
 * Kommentar (Log) je Eröffnung. Eine Eröffnung sind alle zum selben Zeitpunkt eröffneten Legs
 * (Put, Spread, Iron Condor) bzw. ein Aktienkauf; Aktien aus einer Zuteilung zählen nicht, weil
 * sie keine eigene Entscheidung sind.
 *
 * Schließungen werden je Kontrakt (conid) nach Menge der ältesten offenen Eröffnung zugeordnet.
 * Aktien-P&L steht nur in der Kampagnen-Summe; eine Zuordnung zu Put oder Call wäre willkürlich.
 * Ausnahme: In einer reinen Aktien-Kampagne trägt die erste Eröffnung die ganze Summe.
 */
class CampaignOpenings
{
    private const LEG_TYPES = [
        'OPT_PUT_SELL'  => 'SP',
        'OPT_CALL_SELL' => 'SC',
        'OPT_PUT_BUY'   => 'LP',
        'OPT_CALL_BUY'  => 'LC',
    ];

    /**
     * @param Collection $ledger Buchungen einer Kampagne, aufsteigend nach dateTime, id
     * @return array{openings: array, option_pnl: float, stock_pnl: float, total_pnl: float, shares_open: float}
     */
    public static function fromLedger(Collection $ledger): array
    {
        $exerciseTimes = $ledger->where('action', 'OPTION_EXERCISE')
            ->map(fn($e) => self::timeKey($e->dateTime))->unique()->all();

        $openings  = [];
        $lots      = [];   // Kontrakt → offene Tranchen in Eröffnungsreihenfolge
        $optionPnl = 0.0;
        $stockPnl  = 0.0;
        $shares    = 0.0;

        foreach ($ledger as $entry) {
            $action = $entry->action ?? '';
            $time   = self::timeKey($entry->dateTime);
            $qty    = (float) $entry->inventory_delta;

            if ($entry->assetCategory === 'STK') {
                $stockPnl += (float) $entry->realized_pnl;
                $shares   += $qty;
                if (in_array($action, ['STK_BUY', 'STK_SELL'], true) && !in_array($time, $exerciseTimes, true)) {
                    $openings[$time] ??= self::newOpening($time, 'stock');
                    $openings[$time]['shares'] += $qty;
                    $openings[$time]['stock_price'] = round((float) $entry->tradePrice, 2);
                    $openings[$time]['fees_open'] += abs((float) $entry->commission);
                }
                continue;
            }
            if ($entry->assetCategory !== 'OPT') {
                continue;
            }

            $optionPnl += (float) $entry->realized_pnl;
            $key = $entry->conid ?: $entry->symbol;

            if (isset(self::LEG_TYPES[$action])) {
                $openings[$time] ??= self::newOpening($time, 'option');
                $openings[$time]['type'] = 'option';
                $multiplier = (float) ($entry->multiplier ?: 100);
                $openings[$time]['legs'][] = [
                    'leg_type'   => self::LEG_TYPES[$action],
                    'strike'     => $entry->strike !== null ? (float) $entry->strike : null,
                    'expiry'     => $entry->expiry ? Carbon::parse($entry->expiry)->toDateString() : null,
                    'price'      => round((float) $entry->tradePrice, 2),
                    'quantity'   => abs($qty),
                    'multiplier' => $multiplier,
                    'dte'        => $entry->expiry
                        ? Carbon::parse($time)->startOfDay()->diffInDays(Carbon::parse($entry->expiry)->startOfDay(), false)
                        : null,
                    'entry'      => $entry,
                ];
                $openings[$time]['contracts'] ??= (int) abs($qty);
                $openings[$time]['premium'] += -$qty * (float) $entry->tradePrice * $multiplier;
                $openings[$time]['fees_open'] += abs((float) $entry->commission);
                $openings[$time]['net_cash'] += (float) $entry->netcash;
                $lots[$key][] = ['opened_at' => $time, 'rest' => abs($qty), 'qty' => $qty,
                                 'price' => (float) $entry->tradePrice, 'multiplier' => $multiplier];
                continue;
            }

            if (!self::isClosing($action)) {
                continue;
            }
            $rest = abs($qty);
            foreach ($lots[$key] ?? [] as $i => $lot) {
                if ($rest <= 0) {
                    break;
                }
                $used = min($rest, $lot['rest']);
                if ($used <= 0) {
                    continue;
                }
                $share = $used / abs($qty);
                $rest -= $used;
                $lots[$key][$i]['rest'] -= $used;

                $opening = &$openings[$lot['opened_at']];
                $sign = $lot['qty'] < 0 ? 1 : -1;     // verkauft = eingenommen, gekauft = bezahlt
                $opening['closed_premium'] += $sign * $lot['price'] * $lot['multiplier'] * $used;
                $opening['pnl']        += (float) $entry->realized_pnl * $share;
                $opening['fees_close'] += abs((float) $entry->commission) * $share;
                $opening['closes'][]    = $entry;
                $opening['closed_at']   = $time;
                unset($opening);
            }
        }

        foreach ($lots as $contractLots) {
            foreach ($contractLots as $lot) {
                if ($lot['rest'] > 0) {
                    $openings[$lot['opened_at']]['open_quantity'] += $lot['rest'];
                }
            }
        }

        ksort($openings);
        $openings = array_map(fn($o) => self::finish($o, $shares), array_values($openings));

        // Reine Aktien-Kampagne: die erste Eröffnung trägt die ganze Summe
        if ($openings && !collect($openings)->contains('type', 'option')) {
            $openings[0]['pnl'] = round($stockPnl, 2);
        }

        return [
            'openings'    => $openings,
            'option_pnl'  => round($optionPnl, 2),
            'stock_pnl'   => round($stockPnl, 2),
            'total_pnl'   => round($optionPnl + $stockPnl, 2),
            'shares_open' => $shares,
        ];
    }

    /** Eröffnung zum Zeitpunkt, sonst null. */
    public static function find(array $openings, ?string $openedAt): ?array
    {
        foreach ($openings as $opening) {
            if ($opening['opened_at'] === $openedAt) {
                return $opening;
            }
        }
        return null;
    }

    /** Noch nicht vergebene Eröffnung, die dem Datum am nächsten liegt; sind alle vergeben, die nächste überhaupt. */
    public static function nearest(array $openings, $date, array $taken = []): ?array
    {
        $free = array_filter($openings, fn($o) => !in_array($o['opened_at'], $taken, true)) ?: $openings;
        $day  = Carbon::parse($date)->startOfDay();

        return collect($free)
            ->sortBy(fn($o) => abs($day->diffInSeconds(Carbon::parse($o['opened_at']), false)))
            ->first();
    }

    public static function timeKey($dateTime): string
    {
        return Carbon::parse($dateTime)->format('Y-m-d H:i:s');
    }

    public static function isClosing(string $action): bool
    {
        return str_ends_with($action, '_CLOSE') || str_ends_with($action, '_PARTIAL')
            || in_array($action, ['OPTION_EXPIRATION', 'OPTION_EXERCISE', 'CASH_SETTLEMENT'], true);
    }

    private static function newOpening(string $time, string $type): array
    {
        return [
            'opened_at'      => $time,
            'type'           => $type,
            'legs'           => [],
            'contracts'      => null,
            'shares'         => 0.0,
            'stock_price'    => null,
            'premium'        => 0.0,
            'closed_premium' => 0.0,
            'net_cash'       => 0.0,
            'fees_open'      => 0.0,
            'fees_close'     => 0.0,
            'pnl'            => 0.0,
            'closes'         => [],
            'closed_at'      => null,
            'open_quantity'  => 0.0,
        ];
    }

    private static function finish(array $o, float $sharesOpen): array
    {
        $isOption = $o['type'] === 'option';
        if ($isOption) {
            $state = $o['open_quantity'] <= 0 ? 'closed' : ($o['closes'] ? 'partial' : 'open');
        } else {
            $state = abs($sharesOpen) < 0.0001 ? 'closed' : 'open';
        }
        $actions = collect($o['closes'])->pluck('action');

        return [
            'opened_at'     => $o['opened_at'],
            'type'          => $o['type'],
            'legs'          => $o['legs'],
            'contracts'     => $o['contracts'],
            'shares'        => abs($o['shares']),
            'is_short'      => $o['shares'] < 0,
            'stock_price'   => $o['stock_price'],
            'premium'       => round($o['premium'], 2),
            // Kosten der Schließung = Prämie der geschlossenen Menge − P&L (brutto); so passt auch der Barausgleich
            'closing_cost'  => $isOption && $o['closes'] ? round($o['closed_premium'] - $o['pnl'], 2) : null,
            'fees_open'     => round($o['fees_open'], 2),
            'fees_close'    => round($o['fees_close'], 2),
            'pnl'           => $isOption ? round($o['pnl'], 2) : null,
            'closes'        => $o['closes'],
            'closed_at'     => $state === 'closed' ? $o['closed_at'] : null,
            'state'         => $state,
            'outcome'       => self::outcome($state, $actions),
            'max_risk'      => self::verticalMaxRisk($o),
        ];
    }

    private static function outcome(string $state, Collection $actions): string
    {
        return match (true) {
            $state === 'open'                                  => 'offen',
            $state === 'partial'                               => 'teilweise geschlossen',
            $actions->contains('OPTION_EXERCISE')              => 'zugeteilt',
            $actions->isNotEmpty()
                && $actions->every(fn($a) => $a === 'OPTION_EXPIRATION') => 'verfallen',
            default                                            => 'geschlossen',
        };
    }

    /** Nur beim reinen 2-Leg-Vertical eindeutig: Breite × Multiplikator × Menge abzüglich Kredit bzw. das Debit. */
    private static function verticalMaxRisk(array $o): ?float
    {
        if (count($o['legs']) !== 2) {
            return null;
        }
        [$a, $b] = $o['legs'];
        $sameRight = $a['leg_type'][1] === $b['leg_type'][1];
        $sameSide  = $a['leg_type'][0] === $b['leg_type'][0];
        if (!$sameRight || $sameSide || !$a['strike'] || !$b['strike'] || $a['strike'] === $b['strike']
            || $a['quantity'] !== $b['quantity']) {
            return null;
        }
        $width = abs($a['strike'] - $b['strike']) * $a['multiplier'] * $a['quantity'];
        if ($o['net_cash'] > 0) {
            return round($width - $o['net_cash'], 2);
        }
        return $o['net_cash'] < 0 ? round(abs($o['net_cash']), 2) : null;
    }
}
