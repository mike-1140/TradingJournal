# Architektur TradingJournal2

## Verzeichnisstruktur (relevant)
```
app/
  Livewire/
    Dashboard.php
    Campaigns/
      Campaigns.php
      CampaignDetail.php
      GlobalSymbolDetail.php
    Spreads/
      SpreadsList.php
      SpreadDetail.php
    Trades/
      ClosedStocks.php
      ClosedOptions.php
      ClosedTrades.php
      OpenStocks.php
      OpenOptions.php
      RawData.php
    Symbols/
      SymbolList.php
    Tags/
      TagStatistics.php
      TagManager.php
    Stats/
      StatsDashboard.php
  Models/
    TradeClose.php     → trades_close, Felder: dateTimeClose, dateTimeOpen, profit, ibCommission, ...
    TradeOpen.php      → trades_open
    Campaign.php       → end_date, closed_at, start_date
    CampaignLedger.php → dateTime
    Symbol.php
    Tag.php
    TradeTagPivot.php
    BaseTradingModel.php
  Traits/
    FilterableByTime.php
    ManagesEnvironment.php
    UsesTradingDatabase.php
routes/web.php
resources/views/livewire/
```

## Datenbankverbindungen
| Environment   | Connection          |
|---------------|---------------------|
| MONEY         | trading_money       |
| PAPER         | trading_paper       |
| TEST          | trading_test        |
| AUTOM_MONEY   | trading_autom_money |
| AUTOM_PAPER   | trading_autom_paper |

Verbindung wird per `TradeClose::onEnvironment($env)` oder `Model::on($connection)` gesetzt.

## Livewire Event-System
- `environment-changed` → `'$refresh'` in allen Komponenten
- `time-range-changed` → `updateTimeRangeFromEvent($range)` in Komponenten mit FilterableByTime
- Navigation-Komponente dispatcht `time-range-changed` bei Klick auf Zeitraum-Buttons

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
