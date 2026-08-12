# TradingJournal

Persönliches Trading-Journal zur Auswertung von IBKR-Trades (Interactive Brokers) —
Web-Oberfläche über die Daten, die [xml_proj](https://github.com/mike-1140/xml_proj) importiert.

> **Teil eines Zwei-Repo-Setups:** TradingJournal zeigt nur an, was
> [xml_proj](https://github.com/mike-1140/xml_proj) bereits in die Datenbank importiert
> hat — **xml_proj zuerst einrichten** (siehe dessen README), danach hier weitermachen.
> Beide Repos werden als Geschwister-Verzeichnisse erwartet (`../xml_proj` relativ zu
> diesem Repo).

## Stack

- **Backend:** Laravel 12 + Livewire 3
- **Frontend:** Bootstrap 5 + Bootstrap Icons
- **Datenbank:** MySQL (mehrere Verbindungen, Schema via Python verwaltet)
- **Datenimport:** Python-Skript (`reset_IBKR_DB.py`, aus xml_proj)

## Features

- Auswertung geschlossener und offener Positionen (Stocks, Options)
- Campaign-Tracking (gruppierte Trades pro Symbol)
- Tag-System zur Strategie-Klassifizierung
- Zeitraumfilter: 13 Wochen, 6 Monate, YTD, Alle
- Mehrere Trading-Environments: Money, Paper, Test, Autom_Money, Autom_Paper
- EUR-Basiswährung (FX-Umrechnung durch Python vorberechnet)
- Statistik-Dashboard mit Wochen-/Monats-/Jahresnavigation

## Voraussetzungen

- PHP 8.2+
- Composer
- Node.js + npm
- MySQL
- [xml_proj](https://github.com/mike-1140/xml_proj) bereits eingerichtet (liefert Schema + Datenimport)

## Installation

```bash
git clone https://github.com/mike-1140/TradingJournal.git
cd TradingJournal

composer install
npm install

cp .env.example .env
php artisan key:generate
```

### .env konfigurieren

Die App nutzt mehrere Datenbankverbindungen. `.env.example` liefert als Laravel-Standard
`DB_CONNECTION=sqlite` — für dieses Projekt auf MySQL umstellen:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ibkr_laravel
DB_USERNAME=...
DB_PASSWORD=...

TRADING_DB_HOST=127.0.0.1
TRADING_DB_PORT=3306
TRADING_DB_USERNAME=...
TRADING_DB_PASSWORD=...
```

`DB_*` ist die Laravel-eigene Datenbank (users, sessions, cache). `TRADING_DB_*` sind die
Zugangsdaten für die fünf Trading-Datenbanken — deren Namen (`ibkr_money`, `ibkr_paper`,
`ibkr_test`, `ibkr_autom_money`, `ibkr_autom_paper`) sind in `config/database.php` fest
hinterlegt, nicht über `.env` konfigurierbar, und müssen mit `xml_proj/.env` (`DB_NAME_*`)
übereinstimmen.

### Datenbank einrichten

Das Schema wird durch `xml_proj` verwaltet — **keine Laravel-Migrations** für die Trading-Tabellen:

```bash
cd ../xml_proj
python -m tools.maintenance.reset_IBKR_DB money dein_user_
```

Laravel-eigene Tabellen (users, cache, sessions):

```bash
php artisan migrate
```

Vorschritt (einmalig): `is_admin`-Spalte anlegen — hat keine Migration, sonst schlaegt
`auth:create-user --admin` mit "Column not found: is_admin" fehl:

```bash
php artisan setup:auth
```

Ersten User anlegen (Prefix muss zum oben initialisierten Prefix passen):

```bash
php artisan auth:create-user "Dein Name" dein_name@example.com "PASSWORT" --prefix=dein_user_ --admin
```

### Assets bauen

```bash
npm run build
# oder für Entwicklung:
npm run dev
```

## Datenbankstruktur

| Environment | DB-Verbindung       |
|-------------|---------------------|
| MONEY       | trading_money       |
| PAPER       | trading_paper       |
| TEST        | trading_test        |
| AUTOM_MONEY | trading_autom_money |
| AUTOM_PAPER | trading_autom_paper |

Haupttabellen: `trades_close`, `trades_open`, `trades_raw`, `campaigns`, `campaign_ledger`, `symbols`, `tags`

## Tests

```bash
php artisan test
```

Läuft gegen echtes MySQL, nicht SQLite (mehrere Livewire-Komponenten nutzen
MySQL-spezifisches Raw-SQL wie `DATE_FORMAT`/`GROUP_CONCAT ... SEPARATOR`). Setup-Details
in [`tests/README.md`](tests/README.md); `.env.testing.example` als Vorlage für die
eigene `.env.testing` kopieren.

## Entwicklung auf mehreren Rechnern

Nach dem Clone ist kein weiterer Setup-Schritt nötig. Claude Code liest `CLAUDE.md` automatisch für Projektkontext.

```bash
git pull
composer install
npm install
```

## Dokumentation

| Datei | Inhalt |
|---|---|
| `CLAUDE.md` | Entwickler-Notizen (Traits, Event-System, Environment-Handling, bekannte Bugs) |
| `docs/setup/tradingjournal-setup(-einsteiger).md` | Setup-Anleitung für dieses Repo, technisch bzw. für Einsteiger |
| `docs/setup/ui-first-run.md` | UI-Erstkonfiguration nach dem Setup (Environment-Switcher, Nutzer wechseln, ...) |
| `tests/README.md` | Test-Setup gegen echtes MySQL, Environment-Isolation |
| `CONTRIBUTING.md` | Wie beitragen — Konventionen, Test-Erwartung, PR-Ablauf |
| `SECURITY.md` | Meldeweg für Sicherheitslücken |
| `LICENSE` | MIT |

## Rechtliches

- **Lizenz:** MIT, siehe [`LICENSE`](LICENSE).
- **Kein Support-Anspruch:** Hobbyprojekt, best effort — keine SLA, keine Garantie auf Reaktionszeit bei Issues oder PRs.
- **Keine Finanzberatung:** Dieses Tool dient ausschließlich der Auswertung und Dokumentation eigener Trades. Es stellt keine Anlage- oder Steuerberatung dar und ersetzt keine professionelle Beratung.
- **Keine Gewährleistung:** Für die Korrektheit der berechneten P&L-, ROI- oder sonstigen Kennzahlen wird keine Haftung übernommen. Vor jeder Nutzung für reale Entscheidungen die Zahlen gegen die eigenen Broker-Unterlagen prüfen.
- **Kein IBKR-Bezug:** Dieses Projekt steht in keiner Verbindung zu und ist nicht autorisiert durch Interactive Brokers LLC. Es verarbeitet lediglich von IBKR bereitgestellte Exportformate (FlexQuery/Activity-Statements) über die dafür vorgesehenen Schnittstellen.
