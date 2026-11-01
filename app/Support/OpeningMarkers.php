<?php

namespace App\Support;

use App\Models\CampaignLedger;
use App\Models\PsychologyEntry;
use App\Models\TradePlan;

/**
 * Verbindet Buchungszeilen mit dem Log: welche Zeile gehört zu welcher Eröffnung, und ist diese
 * dokumentiert? Eintrag ohne opened_at (vor der Migration) gehört zur ersten Eröffnung.
 */
class OpeningMarkers
{
    /**
     * Marker je Buchung, die zu einer Eröffnung gehört: ledger-id → campaign_id, opened_at,
     * documented, psychology und first (nur die erste angezeigte Zeile einer Eröffnung trägt den Knopf).
     * Die Zuordnung rechnet mit allen Buchungen der Kampagne, auch wenn nur ein Teil angezeigt wird.
     */
    public static function forEntries(iterable $entries, string $connection, string $environment): array
    {
        $entries = collect($entries);
        $ids     = $entries->pluck('campaign_id')->filter()->unique()->values()->all();
        $rows    = [];
        $logged  = [];
        $psych   = [];
        foreach (self::campaigns($ids, $connection, $environment) as $campaignId => $data) {
            $rows   += $data['rows'];
            $logged += array_fill_keys(array_map(fn($t) => "$campaignId|$t", $data['documented']), true);
            $psych  += array_fill_keys(array_map(fn($t) => "$campaignId|$t", $data['psychology']), true);
        }

        $markers = [];
        $seen    = [];
        foreach ($entries as $entry) {
            $openedAt = $rows[$entry->id] ?? null;
            if ($openedAt === null) {
                continue;
            }
            $key = "{$entry->campaign_id}|$openedAt";
            $markers[$entry->id] = [
                'campaign_id' => $entry->campaign_id,
                'opened_at'   => $openedAt,
                'documented'  => isset($logged[$key]),
                'psychology'  => isset($psych[$key]),
                'first'       => !isset($seen[$key]),
            ];
            $seen[$key] = true;
        }

        return $markers;
    }

    /** Je Kampagne: wie viele Eröffnungen sind dokumentiert? campaign_id → [documented, total]. */
    public static function coverage(array $campaignIds, string $connection, string $environment): array
    {
        return array_map(
            fn($data) => ['documented' => count($data['documented']), 'total' => count($data['openings'])],
            self::campaigns($campaignIds, $connection, $environment)
        );
    }

    private static function campaigns(array $campaignIds, string $connection, string $environment): array
    {
        if (!$campaignIds) {
            return [];
        }
        $ledgers = CampaignLedger::on($connection)->whereIn('campaign_id', $campaignIds)
            ->where('environment', $environment)
            ->orderBy('dateTime')->orderBy('id')->get()->groupBy('campaign_id');
        $plans = TradePlan::on($connection)->whereIn('campaign_id', $campaignIds)
            ->where('environment', $environment)->get()->groupBy('campaign_id');
        $psychology = PsychologyEntry::tableExistsFor($connection)
            ? PsychologyEntry::on($connection)->whereIn('campaign_id', $campaignIds)
                ->where('environment', $environment)->whereNotNull('opened_at')->get()->groupBy('campaign_id')
            : collect();

        $result = [];
        foreach ($campaignIds as $id) {
            $ledger   = $ledgers[$id] ?? collect();
            $openings = array_column(CampaignOpenings::fromLedger($ledger)['openings'], 'opened_at');
            $times    = ($plans[$id] ?? collect())
                ->map(fn($p) => $p->opened_at?->format('Y-m-d H:i:s') ?? ($openings[0] ?? null))
                ->all();

            $result[$id] = [
                'rows'       => CampaignOpenings::openingRows($ledger),
                'openings'   => $openings,
                'documented' => array_values(array_intersect($openings, $times)),
                'psychology' => ($psychology[$id] ?? collect())->map(fn($e) => $e->opened_at->format('Y-m-d H:i:s'))->all(),
            ];
        }

        return $result;
    }
}
