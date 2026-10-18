# Contributing

Danke fürs Interesse an TradingJournal. Das Projekt ist ein Hobbyprojekt — Reviews und Antworten
auf Issues/PRs laufen best effort, ohne feste Zeitzusage.

## Bevor du einen PR aufmachst

- Bei größeren Änderungen (neue Features, Umbau bestehender Komponenten) vorher ein Issue
  aufmachen und kurz den Ansatz skizzieren — erspart Arbeit an PRs, die aus Architekturgründen
  nicht passen.
- Kleine, fokussierte PRs statt große Sammel-PRs. Ein PR = eine Änderung.

## Setup

Siehe [`README.md`](README.md) für Installation und `.env`-Konfiguration.

## Tests

```bash
php artisan test
```

Die Testsuite läuft gegen echtes MySQL, nicht SQLite (mehrere Livewire-Komponenten nutzen
MySQL-spezifisches Raw-SQL wie `DATE_FORMAT`/`GROUP_CONCAT ... SEPARATOR`). Setup und
Hintergrund dazu: [`tests/README.md`](tests/README.md). `.env.testing.example` als Vorlage
für die eigene `.env.testing` nutzen.

Neue Bugfixes bitte mit Regressionstest einreichen (Fix + Test, der ohne den Fix fehlschlägt).

## Konventionen

- Code-Kommentare und Log-Meldungen: Deutsch
- Variablen- und Funktionsnamen: Englisch
- Keine Laravel-Migrations für die Trading-Tabellen — das Schema wird von `TradingJournal-Weaver`
  (`reset_IBKR_DB.py`) verwaltet. Migrations sind nur für laravel-eigene Tabellen
  (`users`, `cache`, `sessions`, …) zulässig.
- Finanzwerte: `Decimal`/`decimal`-Spalten, kein `float`
- Weitere projektspezifische Muster (Traits, Event-System, Environment-Handling) stehen in
  [`CLAUDE.md`](CLAUDE.md)

## Secrets

`.env`/`.env.testing` niemals committen. Falls versehentlich doch: sofort im Issue oder per
[SECURITY.md](SECURITY.md) melden statt einen "stillen" Fix-PR aufzumachen — die History muss
bereinigt werden, nicht nur der aktuelle Stand.

## Lizenz deiner Beiträge

Das Projekt steht unter der [PolyForm Noncommercial License 1.0.0](LICENSE). Mit dem Einreichen
eines Pull Requests bestätigst du, dass du die Rechte an deinem Beitrag besitzt, und räumst dem
Projektinhaber (mike-1140) ein unbefristetes, unentgeltliches und nicht-exklusives Recht ein,
den Beitrag unter der Projektlizenz **und** unter künftigen oder zusätzlichen Lizenzen
(einschließlich separater kommerzieller Lizenzen) zu nutzen, zu ändern und weiterzugeben. Deine
eigenen Rechte an deinem Beitrag bleiben davon unberührt.
