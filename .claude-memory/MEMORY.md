# TradingJournal2 – Projektgedächtnis

## Stack
- Laravel + Livewire 3
- Mehrere Datenbankverbindungen (trading_money, trading_paper, trading_test, trading_autom_money, trading_autom_paper)
- Keine Migrations – Feldnamen sind in den Models per `$fillable` / `$casts` definiert
- Bootstrap + Bootstrap Icons (bi-*)

## Architektur: Zwei globale Filter-Traits
→ Details: `architecture.md`

### `FilterableByTime` (`app/Traits/FilterableByTime.php`)
- Property: `$timeRange` (Session-Key: `trading_time_range`, Default: `'ytd'`)
- Werte: `13w`, `6m`, `ytd`, `all`
- Methoden: `mountFilterableByTime()`, `startDate()` (#[Computed]), `applyTimeFilter($query, $dateColumn)`
- Event dispatch: `time-range-changed`
- **Wichtig:** `mountFilterableByTime()` MUSS in `mount()` VOR `mountManagesEnvironment()` aufgerufen werden
- In Blade-Templates: `$this->startDate` (Computed Property)

### `ManagesEnvironment` (`app/Traits/ManagesEnvironment.php`)
- Property: `$selectedEnvironment`
- Session-Key: `trading_environment`

## Datenbankfeld für Close-Datum
- Model: `TradeClose` → Tabelle: `trades_close`
- Feld: `dateTimeClose` (cast: `'date'`)
- Immer `applyTimeFilter($query, 'dateTimeClose')` verwenden

## Stand Globalisierung Betrachtungszeitraum
→ Details: `globalisierung.md`

**Fertig:** Dashboard, Campaigns, SpreadsList, ClosedStocks, ClosedOptions, SymbolList
**Offen:** TagStatistics, ClosedTrades (hat eigenen Datumsfilter – Designentscheidung nötig)
**Sonderfall:** StatsDashboard (eigene Zeitnavigation mit Offset)
**Nicht nötig:** OpenStocks, OpenOptions (offene Positionen, kein Enddatum)

## Blade-Template Pattern für startDate-Anzeige
```blade
<div class="text-muted small mt-1">
    @if($this->startDate)
        <i class="bi bi-calendar3"></i>
        Daten seit dem {{ $this->startDate->format('d.m.Y') }}
    @else
        <i class="bi bi-calendar3"></i>
        Alle historischen Daten
    @endif
</div>
```
Platzierung: nach `</x-page-header>` oder nach dem Header-Div, vor den Statistiken.

## EUR-Basiswährung Integration (2026-03-01)

Die Python-Seite berechnet EUR-Werte vor (zwei verschiedene FX-Kurse für Open/Close).
Laravel empfängt fertige EUR-Werte und zeigt sie nur an — keine Berechnung in Laravel.

### Geänderte Models & neue Felder

**`TradeRaw.php`** + **`TradeOpen.php`** (je 2 Felder):
- `currency` (VARCHAR 5)
- `fxRateToBase` → cast: `decimal:6`

**`TradeClose.php`** (6 Felder):
- `fxRateToBase_open`, `fxRateToBase_close` → cast: `decimal:6`
- `netCashOpen_EUR`, `netCashClose_EUR`, `ibCommission_EUR`, `profit_EUR` → cast: `decimal:4`

**`CampaignLedger.php`** (4 Felder):
- `fxRateToBase` → cast: `decimal:6`
- `netcash_eur`, `proceeds_eur`, `commission_eur` → cast: `decimal:4`

Keine Migration notwendig — DB-Schema wird durch Python (`reset_IBKR_DB.py`) verwaltet.

## Bekannte Bugs (behoben)
- `campaigns-index.blade.php` nutzte `$this->startDateProperty` statt `$this->startDate`
- `Campaigns.php` fehlte `mountFilterableByTime()` in `mount()`
- `#[Computed]` in Traits: funktioniert in Livewire 3 – war NICHT die Fehlerursache
