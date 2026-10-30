<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Liest die vom TradingJournal-Weaver-Schritt "enrich" (src/enrich_greeks.py) berechneten Werte
 * für die Vorbelegung im Log: trade_greeks (Delta je Options-Trade) und iv_history
 * (IV-Tageswerte je Basiswert → IVR/IVP an einem beliebigen Tag).
 * Fehlen die Tabellen (enrich nie gelaufen), liefert alles null — kein Fehler.
 */
class GreeksLookup
{
    /** Wie MIN_IV_DAYS in src/enrich_greeks.py: weniger Tageswerte → IVR/IVP nicht aussagekräftig */
    public const MIN_IV_DAYS = 20;

    private bool $available;

    public function __construct(private string $connection)
    {
        $schema = Schema::connection($connection);
        $this->available = $schema->hasTable('trade_greeks') && $schema->hasTable('iv_history');
    }

    public function available(): bool
    {
        return $this->available;
    }

    /** trade_greeks-Zeile zu einer Kampagnen-Buchung (Ledger → trades_open → tradeID), sonst null. */
    public function forLedgerEntry($entry): ?object
    {
        if (!$this->available || ($entry->source_table ?? null) !== 'TRADES_OPEN' || !$entry->source_id) {
            return null;
        }
        $tradeId = DB::connection($this->connection)->table('trades_open')
            ->where('id', $entry->source_id)->value('tradeID');

        return $tradeId
            ? DB::connection($this->connection)->table('trade_greeks')->where('tradeID', $tradeId)->first()
            : null;
    }

    /**
     * Kurs des Basiswerts zur Minute einer Options-Ausführung (Eröffnung oder Schließung).
     * Zuordnung Buchung → Ausführung über conid + Zeitpunkt in trades_raw, da schließende
     * Buchungen auf die Eröffnungs-Tranche verweisen. Verfall/Zuteilung haben keinen Wert.
     */
    public function underlyingPriceFor($entry): ?float
    {
        if (!$this->available || !$entry->conid || !$entry->dateTime) {
            return null;
        }
        $tradeIds = DB::connection($this->connection)->table('trades_raw')
            ->where('conid', $entry->conid)->where('dateTime', $entry->dateTime)
            ->where('assetCategory', 'OPT')->where('tradePrice', '>', 0)
            ->pluck('tradeID');
        if ($tradeIds->isEmpty()) {
            return null;
        }
        $price = DB::connection($this->connection)->table('trade_greeks')
            ->whereIn('tradeID', $tradeIds)->whereNotNull('underlying_price')->value('underlying_price');

        return $price !== null ? round((float) $price, 2) : null;
    }

    /**
     * Kurs, IVR/IVP und Delta zu einer Eröffnung (CampaignOpenings). Von Hand eingetragene Werte
     * gelten vor den berechneten: $savedLegs (Delta, Zuordnung über Typ + Strike), $plan (Kurs, IV).
     */
    public function detailsFor(array $opening, iterable $savedLegs = [], $plan = null): array
    {
        $firstLeg = $opening['legs'][0]['entry'] ?? null;
        $greeks   = $firstLeg ? $this->forLedgerEntry($firstLeg) : null;
        $open     = $greeks?->underlying ? $this->ivStats($greeks->underlying, $opening['opened_at']) : null;
        $close    = ($greeks?->underlying && $opening['closed_at']) ? $this->ivStats($greeks->underlying, $opening['closed_at']) : null;

        $priceClose = null;
        foreach (array_reverse($opening['closes']) as $entry) {
            if (($priceClose = $this->underlyingPriceFor($entry)) !== null) {
                break;
            }
        }

        $deltas = [];
        foreach ($opening['legs'] as $i => $leg) {
            $saved = collect($savedLegs)->first(fn($s) => data_get($s, 'leg_type') === $leg['leg_type']
                && is_numeric(data_get($s, 'strike')) && abs((float) data_get($s, 'strike') - (float) $leg['strike']) < 0.0001);
            $deltas[$i] = data_get($saved, 'delta') ?: self::deltaForLog($this->forLedgerEntry($leg['entry'])?->delta);
        }

        return [
            'price_open'  => data_get($plan, 'price_open') ?? ($firstLeg ? $this->underlyingPriceFor($firstLeg) : $opening['stock_price']),
            'ivr_open'    => data_get($plan, 'ivr_open') ?? ($open['ivr'] ?? null),
            'ivp_open'    => data_get($plan, 'ivp_open') ?? ($open['ivp'] ?? null),
            'price_close' => data_get($plan, 'price_close') ?? $priceClose,
            'ivr_close'   => data_get($plan, 'ivr_close') ?? ($close['ivr'] ?? null),
            'ivp_close'   => data_get($plan, 'ivp_close') ?? ($close['ivp'] ?? null),
            'deltas'      => $deltas,
        ];
    }

    /** Delta im Log-Format: Betrag × 100, gerundet (z. B. −0,32 → 32). */
    public static function deltaForLog($delta): ?int
    {
        return $delta === null ? null : (int) round(abs((float) $delta) * 100);
    }

    /** ['ivr' => int|null, 'ivp' => int] am Tag $day aus iv_history, oder null bei zu wenig Daten. */
    public function ivStats(string $underlying, $day): ?array
    {
        if (!$this->available) {
            return null;
        }
        $day = Carbon::parse($day)->startOfDay();
        $values = DB::connection($this->connection)->table('iv_history')
            ->where('underlying', $underlying)
            ->where('iv_date', '>', $day->copy()->subDays(365)->toDateString())
            ->where('iv_date', '<=', $day->toDateString())
            ->orderBy('iv_date')
            ->pluck('iv')
            ->map(fn($v) => (float) $v)
            ->filter(fn($v) => $v > 0)
            ->values()
            ->all();

        return self::rankPercentile($values);
    }

    /** Gleiche Formel wie iv_rank_percentile() im Weaver; letzter Wert = Stichtag. */
    public static function rankPercentile(array $values): ?array
    {
        if (count($values) < self::MIN_IV_DAYS) {
            return null;
        }
        $current = end($values);
        $low     = min($values);
        $high    = max($values);
        $prior   = array_slice($values, 0, -1);

        $ivr = $high > $low ? ($current - $low) / ($high - $low) * 100 : null;
        $ivp = count(array_filter($prior, fn($v) => $v < $current)) / count($prior) * 100;

        return ['ivr' => $ivr === null ? null : (int) round($ivr), 'ivp' => (int) round($ivp)];
    }
}
