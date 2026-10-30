<?php

namespace Tests\Unit;

use App\Support\CampaignOpenings;
use PHPUnit\Framework\TestCase;

/** Eröffnungen aus den Ledger-Buchungen einer Kampagne — ohne Datenbank, erfundene Beispiele. */
class CampaignOpeningsTest extends TestCase
{
    private function entry(string $time, string $action, int $conid, float $qty, float $price, float $pnl = 0, array $extra = []): object
    {
        return (object) array_merge([
            'dateTime' => $time, 'action' => $action, 'assetCategory' => 'OPT', 'conid' => $conid,
            'symbol' => "OPT$conid", 'inventory_delta' => $qty, 'tradePrice' => $price, 'realized_pnl' => $pnl,
            'commission' => -1.0, 'multiplier' => 100, 'strike' => $conid, 'expiry' => '2026-12-18',
            'netcash' => -$qty * $price * 100, 'proceeds' => 0,
        ], $extra);
    }

    private function stock(string $time, string $action, float $qty, float $price, float $pnl = 0): object
    {
        return $this->entry($time, $action, 1, $qty, $price, $pnl, ['assetCategory' => 'STK', 'commission' => 0, 'strike' => 0, 'expiry' => null]);
    }

    /** Zwei Spreads nacheinander in einer Kampagne: je eigene Eröffnung, je eigene Zahlen. */
    public function test_folgetrade_ist_eine_eigene_eroeffnung(): void
    {
        $r = CampaignOpenings::fromLedger(collect([
            $this->entry('2026-08-18 12:00:00', 'OPT_CALL_SELL', 105, -1, 3.73),
            $this->entry('2026-08-18 12:00:00', 'OPT_CALL_BUY', 110, 1, 3.00),
            $this->entry('2026-08-24 09:00:00', 'OPT_CALL_BUY_CLOSE', 105, 1, 1.65, 208),
            $this->entry('2026-08-24 09:00:00', 'OPT_CALL_SELL_CLOSE', 110, -1, 1.30, -170),
            $this->entry('2026-08-24 13:00:00', 'OPT_CALL_SELL', 90, -1, 2.30),
            $this->entry('2026-08-24 13:00:00', 'OPT_CALL_BUY', 100, 1, 1.19),
            $this->entry('2026-09-01 09:00:00', 'OPT_CALL_BUY_CLOSE', 90, 1, 1.18, 112),
            $this->entry('2026-09-01 09:00:00', 'OPT_CALL_SELL_CLOSE', 100, -1, 0.68, -51),
        ]));

        $this->assertCount(2, $r['openings']);
        [$first, $second] = $r['openings'];
        $this->assertSame(73.0, $first['premium']);
        $this->assertSame(35.0, $first['closing_cost']);
        $this->assertSame(38.0, $first['pnl']);
        $this->assertSame('2026-08-24 09:00:00', $first['closed_at']);
        $this->assertSame('geschlossen', $first['outcome']);
        $this->assertSame(111.0, $second['premium']);
        $this->assertSame(61.0, $second['pnl']);
        $this->assertSame(99.0, $r['total_pnl']);
    }

    /** Wheel: nie glatt, Zuteilung ist keine Eröffnung, Aktien-P&L nur in der Kampagnen-Summe. */
    public function test_wheel_ohne_glattstellung(): void
    {
        $r = CampaignOpenings::fromLedger(collect([
            $this->entry('2026-01-05 10:00:00', 'OPT_PUT_SELL', 30, -1, 0.50),
            $this->stock('2026-02-20 16:20:00', 'STK_BUY', 100, 30),
            $this->entry('2026-02-20 16:20:00', 'OPTION_EXERCISE', 30, 1, 0, 50),
            $this->entry('2026-03-02 10:00:00', 'OPT_CALL_SELL', 28, -1, 0.40),
            $this->entry('2026-03-20 16:20:00', 'OPTION_EXPIRATION', 28, 1, 0, 40),
            $this->entry('2026-04-01 10:00:00', 'OPT_CALL_SELL', 29, -1, 0.30),
            $this->stock('2026-04-17 16:20:00', 'STK_SELL_CLOSE', -100, 29, -100),
            $this->entry('2026-04-17 16:20:00', 'OPTION_EXERCISE', 29, 1, 0, 30),
            $this->entry('2026-05-04 10:00:00', 'OPT_PUT_SELL', 27, -1, 0.20),
        ]));

        $this->assertCount(4, $r['openings']);
        $this->assertSame(['zugeteilt', 'verfallen', 'zugeteilt', 'offen'], array_column($r['openings'], 'outcome'));
        $this->assertSame([50.0, 40.0, 30.0, 0.0], array_column($r['openings'], 'pnl'));
        $this->assertSame(120.0, $r['option_pnl']);
        $this->assertSame(-100.0, $r['stock_pnl']);
        $this->assertSame(20.0, $r['total_pnl']);
        $this->assertSame(0.0, $r['shares_open']);
    }

    /** Derselbe Kontrakt wird zweimal eröffnet: Schließung trifft die älteste offene Eröffnung. */
    public function test_wiederholter_strike_wird_der_aeltesten_eroeffnung_zugeordnet(): void
    {
        $r = CampaignOpenings::fromLedger(collect([
            $this->entry('2026-01-05 10:00:00', 'OPT_PUT_SELL', 50, -1, 1.00),
            $this->entry('2026-01-10 10:00:00', 'OPT_PUT_BUY_CLOSE', 50, 1, 0.40, 60),
            $this->entry('2026-01-12 10:00:00', 'OPT_PUT_SELL', 50, -1, 1.10),
        ]));

        $this->assertSame(['closed', 'open'], array_column($r['openings'], 'state'));
        $this->assertSame([60.0, 0.0], array_column($r['openings'], 'pnl'));
    }

    /** Beide Eröffnungen gleichzeitig offen: die Schließung trifft die ältere, die jüngere bleibt offen. */
    public function test_schliessung_trifft_bei_zwei_offenen_die_aeltere(): void
    {
        $r = CampaignOpenings::fromLedger(collect([
            $this->entry('2026-01-05 10:00:00', 'OPT_PUT_SELL', 50, -1, 1.00),
            $this->entry('2026-01-06 10:00:00', 'OPT_PUT_SELL', 50, -1, 1.20),
            $this->entry('2026-01-10 10:00:00', 'OPT_PUT_BUY_CLOSE', 50, 1, 0.40, 60),
        ]));

        $this->assertSame(['closed', 'open'], array_column($r['openings'], 'state'));
        $this->assertSame([60.0, 0.0], array_column($r['openings'], 'pnl'));
    }

    /** Teilschließung bleibt „teilweise“; P&L und Gebühren anteilig, Schließung über zwei Eröffnungen wird geteilt. */
    public function test_teilschliessung_und_schliessung_ueber_zwei_eroeffnungen(): void
    {
        $partial = CampaignOpenings::fromLedger(collect([
            $this->entry('2026-01-05 10:00:00', 'OPT_PUT_SELL', 50, -3, 1.00),
            $this->entry('2026-01-10 10:00:00', 'OPT_PUT_BUY_PARTIAL', 50, 1, 0.40, 60),
        ]))['openings'][0];
        $this->assertSame('partial', $partial['state']);
        $this->assertSame('teilweise geschlossen', $partial['outcome']);
        $this->assertNull($partial['closed_at']);
        $this->assertSame(40.0, $partial['closing_cost']);

        $split = CampaignOpenings::fromLedger(collect([
            $this->entry('2026-01-05 10:00:00', 'OPT_PUT_SELL', 50, -1, 1.00),
            $this->entry('2026-01-06 10:00:00', 'OPT_PUT_SELL', 50, -1, 1.20),
            $this->entry('2026-01-10 10:00:00', 'OPT_PUT_BUY_CLOSE', 50, 2, 0.50, 120),
        ]));
        $this->assertSame([60.0, 60.0], array_column($split['openings'], 'pnl'));
        $this->assertSame([0.5, 0.5], array_column($split['openings'], 'fees_close'));
    }

    public function test_iron_condor_ist_eine_eroeffnung_mit_vier_legs(): void
    {
        $t = '2026-01-05 10:00:00';
        $o = CampaignOpenings::fromLedger(collect([
            $this->entry($t, 'OPT_PUT_BUY', 90, 2, 0.50),
            $this->entry($t, 'OPT_PUT_SELL', 95, -2, 1.00),
            $this->entry($t, 'OPT_CALL_SELL', 105, -2, 1.10),
            $this->entry($t, 'OPT_CALL_BUY', 110, 2, 0.60),
        ]))['openings'];

        $this->assertCount(1, $o);
        $this->assertCount(4, $o[0]['legs']);
        $this->assertSame(2, $o[0]['contracts']);
        $this->assertSame(200.0, $o[0]['premium']);
        $this->assertNull($o[0]['max_risk']);
    }

    /** Vertical: Breite 5 × 100 × 1 = 500, abzüglich Netto-Kredit 70 (Ledger-netcash). */
    public function test_max_risiko_beim_vertical(): void
    {
        $t = '2026-01-05 10:00:00';
        $o = CampaignOpenings::fromLedger(collect([
            $this->entry($t, 'OPT_CALL_SELL', 105, -1, 3.70, 0, ['netcash' => 369.0]),
            $this->entry($t, 'OPT_CALL_BUY', 110, 1, 3.00, 0, ['netcash' => -301.0]),
        ]))['openings'][0];

        $this->assertSame(432.0, $o['max_risk']);
    }

    public function test_barausgleich_ergibt_schliesskosten_aus_dem_pnl(): void
    {
        $o = CampaignOpenings::fromLedger(collect([
            $this->entry('2026-01-05 10:00:00', 'OPT_PUT_SELL', 50, -1, 2.00),
            $this->entry('2026-01-20 16:20:00', 'CASH_SETTLEMENT', 50, 1, 0, 50),
        ]))['openings'][0];

        $this->assertSame(150.0, $o['closing_cost']);
        $this->assertSame('closed', $o['state']);
    }

    /** Reine Aktien-Kampagne: erste Eröffnung trägt die Summe; Nachkauf ist eine eigene Eröffnung. */
    public function test_reine_aktien_kampagne(): void
    {
        $o = CampaignOpenings::fromLedger(collect([
            $this->stock('2026-01-05 10:00:00', 'STK_BUY', 100, 50),
            $this->stock('2026-01-07 10:00:00', 'STK_BUY', 50, 48),
            $this->stock('2026-02-01 10:00:00', 'STK_SELL_CLOSE', -150, 55, 650),
        ]))['openings'];

        $this->assertSame(['stock', 'stock'], array_column($o, 'type'));
        $this->assertSame(650.0, $o[0]['pnl']);
        $this->assertNull($o[1]['pnl']);
        $this->assertSame('closed', $o[0]['state']);
    }

    public function test_naechste_freie_eroeffnung_zum_datum(): void
    {
        $o = [['opened_at' => '2026-01-05 10:00:00'], ['opened_at' => '2026-02-05 10:00:00'], ['opened_at' => '2026-03-05 10:00:00']];

        $this->assertSame('2026-02-05 10:00:00', CampaignOpenings::nearest($o, '2026-02-01')['opened_at']);
        $this->assertSame('2026-01-05 10:00:00', CampaignOpenings::nearest($o, '2026-02-01', ['2026-02-05 10:00:00'])['opened_at']);
        $this->assertNull(CampaignOpenings::find($o, '2026-04-01 00:00:00'));
    }
}
