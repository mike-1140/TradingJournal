# TradingJournal2 – Projektkontext für Claude Code

## Stack
- Laravel + Livewire 3
- Mehrere Datenbankverbindungen: `trading_money`, `trading_paper`, `trading_test`, `trading_autom_money`, `trading_autom_paper`
- Keine Migrations – Feldnamen sind in den Models per `$fillable` / `$casts` definiert
- Bootstrap + Bootstrap Icons (`bi-*`)
- DB-Schema wird durch Python (`reset_IBKR_DB.py`) verwaltet

## Architektur: Zwei globale Filter-Traits

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

## Standard-Pattern für neue Komponenten mit Zeitfilter
```php
use App\Traits\ManagesEnvironment;
use App\Traits\FilterableByTime;

class MyComponent extends Component
{
    use WithPagination, ManagesEnvironment, FilterableByTime;

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed'  => 'updateTimeRangeFromEvent',
    ];

    public function mount()
    {
        $this->mountFilterableByTime();      // ZUERST
        $this->mountManagesEnvironment();
    }

    public function updateTimeRangeFromEvent($range)
    {
        $this->timeRange = $range;
        $this->resetPage();
    }

    public function render()
    {
        $baseQuery = fn() => $this->applyTimeFilter(
            TradeClose::onEnvironment($this->selectedEnvironment)
                ->where('environment', $this->selectedEnvironment),
            'dateTimeClose'
        );
        // ...
    }
}
```

## $baseQuery Closure Pattern (bevorzugt)
```php
$baseQuery = fn() => $this->applyTimeFilter(
    TradeClose::onEnvironment($this->selectedEnvironment)
        ->where('environment', $this->selectedEnvironment)
        ->where('assetCategory', 'STK'),
    'dateTimeClose'
);

$trades      = $baseQuery()->with('symbol')->paginate(...);
$totalTrades = $baseQuery()->count();
$totalProfit = $baseQuery()->sum('profit');
```

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
Platzierung: nach `</x-page-header>`, vor den Statistiken.

## Datenbankverbindungen
| Environment | Connection          |
|-------------|---------------------|
| MONEY       | trading_money       |
| PAPER       | trading_paper       |
| TEST        | trading_test        |
| AUTOM_MONEY | trading_autom_money |
| AUTOM_PAPER | trading_autom_paper |

Verbindung wird per `TradeClose::onEnvironment($env)` oder `Model::on($connection)` gesetzt.

## Livewire Event-System
- `environment-changed` → `'$refresh'` in allen Komponenten
- `time-range-changed` → `updateTimeRangeFromEvent($range)` in Komponenten mit FilterableByTime

## Verzeichnisstruktur (relevant)
```
app/
  Livewire/
    Dashboard.php
    Campaigns/  CampaignDetail.php, Campaigns.php, GlobalSymbolDetail.php
    Spreads/    SpreadsList.php, SpreadDetail.php
    Trades/     ClosedStocks.php, ClosedOptions.php, ClosedTrades.php, OpenStocks.php, OpenOptions.php, RawData.php
    Symbols/    SymbolList.php
    Tags/       TagStatistics.php, TagManager.php
    Stats/      StatsDashboard.php
  Models/
    TradeClose.php     → trades_close, Felder: dateTimeClose, dateTimeOpen, profit, ibCommission, ...
    TradeOpen.php      → trades_open
    Campaign.php       → end_date, closed_at, start_date
    CampaignLedger.php → dateTime
  Traits/
    FilterableByTime.php
    ManagesEnvironment.php
    UsesTradingDatabase.php
```

## Stand Globalisierung Betrachtungszeitraum
**Fertig:** Dashboard, Campaigns, SpreadsList, ClosedStocks, ClosedOptions, SymbolList
**Offen:** TagStatistics, ClosedTrades (hat eigenen Datumsfilter – Designentscheidung nötig)
**Sonderfall:** StatsDashboard (eigene Zeitnavigation mit Offset), OpenStocks, OpenOptions, RawData (kein Filter nötig)

## EUR-Basiswährung (seit 2026-03-01)
Python berechnet EUR-Werte vor — Laravel zeigt sie nur an, keine Berechnung in Laravel.

**`TradeRaw.php`** + **`TradeOpen.php`**: `currency` (VARCHAR 5), `fxRateToBase` (decimal:6)

**`TradeClose.php`**: `fxRateToBase_open`, `fxRateToBase_close` (decimal:6), `netCashOpen_EUR`, `netCashClose_EUR`, `ibCommission_EUR`, `profit_EUR` (decimal:4)

**`CampaignLedger.php`**: `fxRateToBase` (decimal:6), `netcash_eur`, `proceeds_eur`, `commission_eur` (decimal:4)

## Bekannte Bugs (behoben)
- `campaigns-index.blade.php` nutzte `$this->startDateProperty` statt `$this->startDate`
- `Campaigns.php` fehlte `mountFilterableByTime()` in `mount()`
- `#[Computed]` in Traits funktioniert in Livewire 3 korrekt
