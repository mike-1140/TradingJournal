# TradingJournal

Persönliches Trading-Journal zur Auswertung von IBKR-Trades (Interactive Brokers).

## Stack

- **Backend:** Laravel 11 + Livewire 3
- **Frontend:** Bootstrap 5 + Bootstrap Icons
- **Datenbank:** MySQL (mehrere Verbindungen, Schema via Python verwaltet)
- **Datenimport:** Python-Skript (`reset_IBKR_DB.py`)

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
- Python 3.x (für Datenimport)

## Installation

```bash
git clone <repo-url>
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
python -m tools.maintenance.reset_IBKR_DB money mike_
```

Laravel-eigene Tabellen (users, cache, sessions):

```bash
php artisan migrate
```

Ersten User anlegen (Prefix muss zum oben initialisierten Prefix passen):

```bash
php artisan auth:create-user "Mike" mike@example.com "PASSWORT" --prefix=mike_ --admin
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

## Entwicklung auf mehreren Rechnern

Nach dem Clone ist kein weiterer Setup-Schritt nötig. Claude Code liest `CLAUDE.md` automatisch für Projektkontext.

```bash
git pull
composer install
npm install
```

