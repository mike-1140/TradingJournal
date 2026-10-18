# TradingJournal

Persönliches Trading-Journal zur Auswertung von IBKR-Trades (Interactive Brokers) —
Web-Oberfläche über die Daten, die [TradingJournal-Weaver](https://github.com/mike-1140/TradingJournal-Weaver) importiert.

> **Teil eines Zwei-Repo-Setups:** TradingJournal zeigt nur an, was
> [TradingJournal-Weaver](https://github.com/mike-1140/TradingJournal-Weaver) bereits in die Datenbank importiert
> hat — **TradingJournal-Weaver zuerst einrichten** (siehe dessen README), danach hier weitermachen.
> Beide Repos werden als Geschwister-Verzeichnisse erwartet (`../TradingJournal-Weaver` relativ zu
> diesem Repo).

## Stack

- **Backend:** Laravel 12 + Livewire 3
- **Frontend:** Bootstrap 5 + Bootstrap Icons
- **Datenbank:** MySQL (mehrere Verbindungen, Schema via Python verwaltet)
- **Datenimport:** Python-Skript (`reset_IBKR_DB.py`, aus TradingJournal-Weaver)

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
- [TradingJournal-Weaver](https://github.com/mike-1140/TradingJournal-Weaver) bereits eingerichtet (liefert Schema + Datenimport)

## Installation

> Diese Anleitung beschreibt die lokale Installation. Für einen eigenen Server (Remote-
> Zugriff von mehreren Geräten via VPN) siehe stattdessen [`docs/handbuch/technik/70-server-betrieb.md`](docs/handbuch/technik/70-server-betrieb.md).

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
hinterlegt, nicht über `.env` konfigurierbar, und müssen mit `TradingJournal-Weaver/.env` (`DB_NAME_*`)
übereinstimmen.

### Datenbank einrichten

Das Schema wird durch `TradingJournal-Weaver` verwaltet — **keine Laravel-Migrations** für die Trading-Tabellen:

```bash
cd ../TradingJournal-Weaver
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

Für spätere Updates (`git pull` + Abhängigkeiten + Migrationen + Cache-Rebuild in einem
Schritt) siehe [`docs/handbuch/benutzer/80-updates.md`](docs/handbuch/benutzer/80-updates.md) — gilt auch für eine bestehende
Server-Installation.

## Dokumentation

Die ausführliche Doku ist in zwei Handbücher gegliedert — Übersicht aller Kapitel:
[`docs/handbuch/`](docs/handbuch/README.md). Beide gibt es auch als PDF (siehe Releases).

| Handbuch | Für wen | Wichtigste Kapitel |
|---|---|---|
| [Benutzerhandbuch](docs/handbuch/README.md#benutzerhandbuch) | Anwender, auch ohne Programmierkenntnisse | [Überblick: vom Trade zur Auswertung](docs/handbuch/benutzer/10-ueberblick.md), [IBKR einrichten](docs/handbuch/benutzer/20-ibkr-einrichten.md), [TradingJournal installieren](docs/handbuch/benutzer/50-tradingjournal-installieren.md), [Erster Start](docs/handbuch/benutzer/60-erster-start.md), [Updates](docs/handbuch/benutzer/80-updates.md) |
| [Technisches Handbuch](docs/handbuch/README.md#technisches-handbuch) | Technisch Interessierte, Betrieb, Mitwirkende | [Architektur und Pipeline](docs/handbuch/technik/10-architektur-und-pipeline.md), [Installation (technisch)](docs/handbuch/technik/60-installation-tradingjournal.md), [Server-Betrieb](docs/handbuch/technik/70-server-betrieb.md), [Verteilter Abruf](docs/handbuch/technik/80-verteilter-abruf.md) |

Weitere Dateien in diesem Repo:

| Datei | Inhalt |
|---|---|
| `CLAUDE.md` | Entwickler-Notizen (Traits, Event-System, Environment-Handling, bekannte Bugs) |
| `tests/README.md` | Test-Setup gegen echtes MySQL, Environment-Isolation |
| `CONTRIBUTING.md` | Wie beitragen — Konventionen, Test-Erwartung, PR-Ablauf |
| `SECURITY.md` | Meldeweg für Sicherheitslücken |
| `LICENSE` | PolyForm Noncommercial 1.0.0 (siehe Abschnitt „Lizenz“) |

## Lizenz

Dieses Projekt steht unter der **[PolyForm Noncommercial License 1.0.0](LICENSE)**. Der Quellcode
ist öffentlich einsehbar („source-available“), aber **nicht** Open Source im Sinne der OSI: Die
Nutzung ist frei für alle nicht-kommerziellen Zwecke, eine kommerzielle Verwertung ist
ausgeschlossen.

**Erlaubt — ohne Rückfrage:**

- Die Software installieren und für die **eigenen privaten Trading-Konten** nutzen — ausdrücklich
  auch für Echtgeld-Konten, auch wenn mit dem Trading selbst Gewinn erzielt werden soll (siehe
  „Additional Permission“ in [`LICENSE`](LICENSE))
- Den Code lesen, daraus lernen, ihn für sich anpassen und erweitern
- Die Software oder eigene Änderungen daran **kostenlos** weitergeben — mit beiliegender
  [`LICENSE`](LICENSE) (inkl. der Zeile `Required Notice: …`)
- Nutzung durch gemeinnützige Organisationen, Schulen, Hochschulen und Forschungseinrichtungen
- Beiträge zu diesem Projekt per Issue oder Pull Request (siehe [`CONTRIBUTING.md`](CONTRIBUTING.md))

**Nicht erlaubt:**

- Die Software oder Teile davon **verkaufen** oder gegen Gebühr anbieten — auch nicht als
  gehosteten Dienst, Abo oder SaaS
- Den Code (oder wesentliche Teile davon) in ein **kommerzielles Produkt** oder eine kommerzielle
  Dienstleistung übernehmen
- Den Einsatz im Rahmen einer bezahlten Tätigkeit für Dritte, z. B. Vermögensverwaltung,
  Trading-Coaching oder Signal-Dienste, bei denen die Software für Kunden genutzt wird
- Die Lizenzhinweise entfernen oder die Software unter einer anderen Lizenz weitergeben

**Kommerzielle Nutzung gewünscht?** Wer das Projekt kommerziell einsetzen möchte, kann über ein
[GitHub-Issue](https://github.com/mike-1140/TradingJournal/issues) Kontakt aufnehmen — eine separate Lizenz ist im Einzelfall möglich.

> Diese Zusammenfassung dient der Orientierung. Rechtlich verbindlich ist allein der englische
> Lizenztext in [`LICENSE`](LICENSE).

## Rechtliches

- **Kein Support-Anspruch:** Hobbyprojekt, best effort — keine SLA, keine Garantie auf Reaktionszeit bei Issues oder PRs.
- **Keine Finanzberatung:** Dieses Tool dient ausschließlich der Auswertung und Dokumentation eigener Trades. Es stellt keine Anlage- oder Steuerberatung dar und ersetzt keine professionelle Beratung.
- **Keine Gewährleistung:** Für die Korrektheit der berechneten P&L-, ROI- oder sonstigen Kennzahlen wird keine Haftung übernommen. Vor jeder Nutzung für reale Entscheidungen die Zahlen gegen die eigenen Broker-Unterlagen prüfen.
- **Kein IBKR-Bezug:** Dieses Projekt steht in keiner Verbindung zu und ist nicht autorisiert durch Interactive Brokers LLC. Es verarbeitet lediglich von IBKR bereitgestellte Exportformate (FlexQuery/Activity-Statements) über die dafür vorgesehenen Schnittstellen.
