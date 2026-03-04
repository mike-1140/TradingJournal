# TradingJournal2

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
cd TradingJournal2

composer install
npm install

cp .env.example .env
php artisan key:generate
```

### .env konfigurieren

Die App nutzt mehrere Datenbankverbindungen. In `.env` müssen folgende Verbindungen eingetragen werden:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trading_money
DB_USERNAME=...
DB_PASSWORD=...

DB_MONEY_DATABASE=trading_money
DB_PAPER_DATABASE=trading_paper
DB_TEST_DATABASE=trading_test
DB_AUTOM_MONEY_DATABASE=trading_autom_money
DB_AUTOM_PAPER_DATABASE=trading_autom_paper
```

### Datenbank einrichten

Das Schema wird durch das Python-Skript verwaltet — **keine Laravel-Migrations** für die Trading-Tabellen:

```bash
python python/reset_IBKR_DB.py
```

Laravel-eigene Tabellen (users, cache, sessions):

```bash
php artisan migrate
php artisan db:seed
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

