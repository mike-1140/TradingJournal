# Globalisierung Betrachtungszeitraum – Status

## Fertig angepasst
| Komponente                        | PHP | Blade | Anmerkung |
|-----------------------------------|-----|-------|-----------|
| Dashboard                         | ✅  | ✅    | `loadStatistics()` Pattern |
| Campaigns/Campaigns               | ✅  | ✅    | `end_date` als Datumsspalte |
| Campaigns/CampaignDetail          | ✅  | ?     | `applyTimeFilter` bereits vorhanden |
| Campaigns/GlobalSymbolDetail      | ✅  | ?     | `applyTimeFilter` bereits vorhanden |
| Spreads/SpreadsList               | ✅  | ✅    | |
| Spreads/SpreadDetail              | ✅  | ?     | `applyTimeFilter` bereits vorhanden |
| Trades/ClosedStocks               | ✅  | ✅    | `$baseQuery` Closure Pattern |
| Trades/ClosedOptions              | ✅  | ✅    | `$baseQuery` Closure Pattern |
| Symbols/SymbolList                | ✅  | ✅    | Filter nur auf TradeClose, nicht auf Symbol |

## Noch offen
| Komponente          | PHP | Blade | Anmerkung |
|---------------------|-----|-------|-----------|
| Tags/TagStatistics  | ❌  | ✅    | Blade bereits angepasst |
| Trades/ClosedTrades | ❌  | ✅    | Hat eigenen filterStartDate/filterEndDate – Designentscheidung: globalen Filter als Default übernehmen oder eigenständig lassen? |

## Sonderfälle (kein Zeitfilter nötig/gewollt)
| Komponente            | Grund |
|-----------------------|-------|
| Trades/OpenStocks     | Offene Positionen, kein Enddatum |
| Trades/OpenOptions    | Offene Positionen, kein Enddatum |
| Trades/RawData        | Rohdaten-Import, keine Zeitfilterung sinnvoll |
| Stats/StatsDashboard  | Eigene Zeitnavigation (Wochen/Monate/Jahre + Offset) |
| Tags/TagManager       | Nur Tag-Verwaltung, keine Trades |

## $baseQuery Closure Pattern (bevorzugt)
Statt Query-Bedingungen mehrfach zu wiederholen:
```php
$baseQuery = fn() => $this->applyTimeFilter(
    TradeClose::onEnvironment($this->selectedEnvironment)
        ->where('environment', $this->selectedEnvironment)
        ->where('assetCategory', 'STK'),
    'dateTimeClose'
);

$trades        = $baseQuery()->with('symbol')->paginate(...);
$totalTrades   = $baseQuery()->count();
$totalProfit   = $baseQuery()->sum('profit');
$commissions   = $baseQuery()->sum('ibCommission');
$strategies    = $baseQuery()->distinct()->whereNotNull('strategy')->pluck('strategy')->filter();
```
