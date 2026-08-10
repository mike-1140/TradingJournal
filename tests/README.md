# Tests

## Einmalig einrichten

1. `.env.testing.example` nach `.env.testing` kopieren, DB-Zugangsdaten anpassen (lokale MySQL-Credentials, wie in `.env`)
2. Dedizierte Test-Datenbank für die Haupt-App (users, cache, ...) anlegen — muss **anders heißen** als deine echte `DB_DATABASE`, damit Tests nie echte User-Accounts berühren:
   ```sql
   CREATE DATABASE ibkr_laravel_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Trading-Schema unter einem eigenen, nur für Tests reservierten Präfix anlegen (in `xml_proj/`):
   ```bash
   python -m tools.maintenance.reset_IBKR_DB test phpunit_
   ```
   Legt Tabellen wie `phpunit_symbols`, `phpunit_trades_close` in der bereits vorhandenen `ibkr_test`-DB an — betrifft **nur** den `phpunit_`-Präfix, rührt `karola_*`/`mike_*` oder andere Präfixe nicht an (siehe `apply_table_prefix()` in `reset_IBKR_DB.py`).

## Ausführen

```bash
php artisan test
```

## Warum kein SQLite

`phpunit.xml` sah ursprünglich eine SQLite-In-Memory-DB vor — funktioniert aber nicht für diese App: mehrere Livewire-Komponenten (`Dashboard`, `SpreadsList`, `CampaignDetail`, `Navigation`, …) nutzen MySQL-spezifisches Raw-SQL (`DATE_FORMAT()`, `YEAR()`, `GROUP_CONCAT(... SEPARATOR ...)`), das es in SQLite nicht gibt — die Queries würden mit "no such function" abbrechen. Das Schema selbst ist außerdem reines MySQL-DDL (`xml_proj/sql/*.sql`), keine Laravel-Migrations. Tests laufen deshalb bewusst gegen echtes MySQL.

## Zwei getrennte DB-Verbindungen in Tests

| Verbindung | Wofür | Isolation |
|---|---|---|
| `mysql` (Haupt-DB, `ibkr_laravel_test`) | `users`, `cache`, Sessions — echte Laravel-Migrationen | `RefreshDatabase`-Trait (migriert frisch pro Testklasse) |
| `trading_test` (`ibkr_test`, Präfix `phpunit_`) | `TradeClose`, `Campaign`, `CampaignLedger`, … | `Tests\Concerns\UsesTradingTestConnection` (Transaktion pro Test, Rollback in `tearDown()`) |

Für Tests, die Trading-Daten anfassen: Trait einbinden und `setUpTradingTestConnection()`/`tearDownTradingTestConnection()` in `setUp()`/`tearDown()` aufrufen (siehe `TradingConnectionSmokeTest.php`). Nie direkt gegen `karola_*`/`mike_*` schreiben — die gehören zum manuellen TEST-Environment des Nutzers, nicht zu automatisierten Tests.

## Testdateien (Stand: Grundgerüst)

| Datei | Prüft |
|---|---|
| `Unit/TradingHelperTest.php` | `TradingHelper` — reine Environment-/Connection-Logik, kein DB-Zugriff |
| `Feature/UserAccountSmokeTest.php` | Haupt-DB-Verbindung zeigt korrekt auf `ibkr_laravel_test`, `User::factory()` funktioniert |
| `Feature/TradingConnectionSmokeTest.php` | `UsesTradingTestConnection` isoliert wirklich — Test 2 beweist, dass Test 1s Rollback nichts hinterlassen hat |

Noch nicht getestet (nächste Schritte): `FilterableByTime`/`ManagesEnvironment` (Traits), die 5 bekannten, bereits gefixten Bugs aus `CLAUDE.md` (Computed-Property-Typo in `campaigns-index.blade.php`, fehlender `mountFilterableByTime()`-Aufruf, Trait-Reihenfolge, Livewire-Self-Refresh-Antipattern, Spread-Assignment-linkID-Anzeige), Livewire-Komponententests mit echten Trading-Daten.
