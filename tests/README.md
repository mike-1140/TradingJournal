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

## CI (GitHub Actions)

`.github/workflows/tests.yml` — läuft auf jedem Push/PR: MySQL-Service-Container, checkt zusätzlich `xml_proj` aus (liefert das Trading-Schema, s.o.), baut Assets, migriert die Haupt-DB, führt `php artisan test` aus.

**Einmaliger Setup-Schritt nötig, solange `xml_proj` noch privat ist:** Fine-grained PAT mit Read-Zugriff auf `xml_proj` erzeugen und als Repo-Secret `XMLPROJ_CHECKOUT_TOKEN` hinterlegen (Settings → Secrets and variables → Actions). Entfällt automatisch, sobald `xml_proj` public ist (Phase 5 im Open-Source-Fahrplan) — dann kann die `token:`-Zeile im Checkout-Step raus.

## Warum kein SQLite

`phpunit.xml` sah ursprünglich eine SQLite-In-Memory-DB vor — funktioniert aber nicht für diese App: mehrere Livewire-Komponenten (`Dashboard`, `SpreadsList`, `CampaignDetail`, `Navigation`, …) nutzen MySQL-spezifisches Raw-SQL (`DATE_FORMAT()`, `YEAR()`, `GROUP_CONCAT(... SEPARATOR ...)`), das es in SQLite nicht gibt — die Queries würden mit "no such function" abbrechen. Das Schema selbst ist außerdem reines MySQL-DDL (`xml_proj/sql/*.sql`), keine Laravel-Migrations. Tests laufen deshalb bewusst gegen echtes MySQL.

## Falle: veralteter Config-Cache überschreibt `.env.testing`

Falls `bootstrap/cache/config.php` existiert (z. B. nach einem früheren
`php artisan config:cache`, etwa durch `update.ps1`), liest `config('app.env')` — und damit
`app()->environment()` — den zur Cache-Zeit eingefrorenen Wert aus der damals aktiven
`.env`, **nicht** mehr `.env.testing`s `APP_ENV=testing`. Symptom: `env('APP_ENV')` zeigt
korrekt `testing`, aber `app()->environment()`/`config('app.env')` zeigt trotzdem `local`
(oder was auch immer beim Cachen aktiv war) — jeder auf `app()->environment(...)` basierende
Test verhält sich dadurch falsch, ohne dass der Grund offensichtlich ist. Fix:
`php artisan config:clear` vor dem Testlauf.

## Zwei getrennte DB-Verbindungen in Tests

| Verbindung | Wofür | Isolation |
|---|---|---|
| `mysql` (Haupt-DB, `ibkr_laravel_test`) | `users`, `cache`, Sessions — echte Laravel-Migrationen | `RefreshDatabase`-Trait (migriert frisch pro Testklasse) |
| `trading_test` (`ibkr_test`, Präfix `phpunit_`) | `TradeClose`, `Campaign`, `CampaignLedger`, … | `Tests\Concerns\UsesTradingTestConnection` (Transaktion pro Test, Rollback in `tearDown()`) |

Nie direkt gegen `karola_*`/`mike_*` schreiben — die gehören zum manuellen TEST-Environment des Nutzers, nicht zu automatisierten Tests.

### Falle: `ManagesEnvironment::applyUserPrefix()` killt eine schon offene Transaktion

`applyUserPrefix()` ruft bei **jedem** Component-`mount()` `DB::purge('trading_test')` auf — auch wenn sich der Präfix gar nicht ändert. Das verwirft die aktive PDO-Connection samt einer eventuell offenen Transaktion. Zwei Nutzungsmuster je nachdem, ob der Test eine Livewire-Komponente mountet:

**Kein `Livewire::test()` beteiligt** (reine DB-Zugriffe, z.B. `TradingConnectionSmokeTest.php`):
```php
protected function setUp(): void { parent::setUp(); $this->setUpTradingTestConnection(); }
protected function tearDown(): void { $this->tearDownTradingTestConnection(); parent::tearDown(); }
```

**Eine `ManagesEnvironment`-Komponente wird gemountet** (praktisch jede Livewire-Komponente in dieser App, z.B. `SpreadAssignmentPnlTest.php`): Präfix in `setUp()` setzen, aber die Transaktion **erst nach dem ersten `Livewire::test()`-Aufruf** beginnen — sonst ist sie beim Mounten schon wieder weg:
```php
protected function setUp(): void { parent::setUp(); $this->setPhpunitPrefix(); }
protected function tearDown(): void { $this->rollbackTradingTransaction(); parent::tearDown(); }

public function test_...(): void
{
    $test = Livewire::actingAs($user)->test(MyComponent::class); // mount() -> purge()
    $this->beginTradingTransaction();                            // JETZT erst
    DB::connection('trading_test')->table(...)->insert(...);
    $test->call('irgendeineHarmloseAction')->viewData(...);      // erzwingt Re-Render nach dem Insert
}
```

## Testdateien (Stand 2026-08-10)

| Datei | Prüft |
|---|---|
| `Unit/TradingHelperTest.php` | `TradingHelper` — reine Environment-/Connection-Logik, kein DB-Zugriff |
| `Unit/DashboardListenerConfigTest.php` | Bug: Livewire-Self-Refresh-Antipattern — Dashboard darf auf kein Event hören, das es selbst dispatched |
| `Feature/UserAccountSmokeTest.php` | Haupt-DB-Verbindung zeigt korrekt auf `ibkr_laravel_test`, `User::factory()` funktioniert |
| `Feature/TradingConnectionSmokeTest.php` | `UsesTradingTestConnection` isoliert wirklich — Test 2 beweist, dass Test 1s Rollback nichts hinterlassen hat |
| `Feature/Campaigns/CampaignsStartDateDisplayTest.php` | Bug: `$this->startDateProperty`-Typo — Zeitfenster wird korrekt angezeigt, nicht immer "Alle historischen Daten" |
| `Feature/Spreads/SpreadAssignmentPnlTest.php` | Bug: Spread-Assignment-linkID — Aktienzuteilung wird ins Spread-P&L eingerechnet (TXN-Beispiel: 118 → -382) |

Alle Bug-Regressionstests per Tripwire verifiziert (Fix testweise zurückgedreht, Test schlägt fehl, zurückgesetzt).

**Als gegenstandslos eingestuft, keine Tests:** die ursprünglich in `CLAUDE.md` dokumentierten Bugs "fehlender `mountFilterableByTime()`-Aufruf" und "Trait-Reihenfolge" — seit dem DB-basierten Zeitfilter-Umbau (2026-03-04) war `mountFilterableByTime()` nur noch ein reiner No-Op. Ein Test dafür hätte nur bewiesen, dass eine leere Funktion leer bleibt — die Methode wurde stattdessen komplett entfernt (Trait + alle 11 Aufrufstellen, 2026-08-10).

Noch nicht getestet: `FilterableByTime`/`ManagesEnvironment` (Traits) direkt, weitere Livewire-Komponenten mit echten Trading-Daten.
