<#
TradingJournal + TradingJournal-Weaver - Update-Automatismus
=================================================
Aktualisiert beide Repos (TradingJournal-Weaver, TradingJournal) auf den neuesten Stand,
installiert Abhaengigkeiten, fuehrt DB-Migrationen aus und schreibt die
Build-Version fuer die Sidebar-Anzeige.

Voraussetzung: TradingJournal-Weaver liegt als Geschwister-Verzeichnis neben diesem Repo
(../TradingJournal-Weaver relativ zu diesem Skript) - siehe README.md "Installation".

Aufruf (aus dem TradingJournal-Ordner):
  .\update.ps1

Sicher wiederholbar: Composer/NPM installieren nur bei Bedarf neu, die
TradingJournal-Weaver-Migrationsskripte in dev/migrate_*.py sind idempotent (ueberspringen
bereits vorhandene Spalten/Tabellen).

WICHTIG: Bricht sofort ab, sobald ein Schritt fehlschlaegt (z.B. git pull wegen
lokaler Aenderungen), statt mit einem halbaktualisierten Stand weiterzumachen -
externe Tools (git/composer/npm/php) melden Fehler nur ueber den Exit-Code,
$ErrorActionPreference="Stop" allein faengt das in Windows PowerShell 5.1 NICHT ab.
#>

$ErrorActionPreference = "Stop"
$TradingJournalRoot = $PSScriptRoot
$WeaverRoot = Join-Path (Split-Path $TradingJournalRoot -Parent) "TradingJournal-Weaver"

function Step($msg) {
    Write-Host ""
    Write-Host "==> $msg" -ForegroundColor Cyan
}

function Assert-LastExit([string]$Description) {
    if ($LASTEXITCODE -ne 0) {
        Write-Host ""
        Write-Host "FEHLGESCHLAGEN: $Description (Exit $LASTEXITCODE)" -ForegroundColor Red
        Write-Host "Update abgebrochen, damit nicht gegen einen halbaktualisierten Stand weitergemacht wird." -ForegroundColor Red
        Write-Host "Siehe Fehlerausgabe oben, Problem beheben und .\update.ps1 erneut starten." -ForegroundColor Red
        exit 1
    }
}

function Invoke-GitPull([string]$RepoName, [string]$Path) {
    Step "git pull: $RepoName"
    if (-not (Test-Path $Path)) {
        Write-Error "$RepoName nicht gefunden unter $Path - siehe README.md 'Installation' (Geschwister-Verzeichnisse erwartet)."
    }
    Set-Location $Path
    git pull
    if ($LASTEXITCODE -ne 0) {
        Write-Host ""
        Write-Host "FEHLGESCHLAGEN: git pull in $RepoName (Exit $LASTEXITCODE)" -ForegroundColor Red
        Write-Host "Meist lokale, nicht committete Aenderungen oder gleichnamige untracked Dateien, die kollidieren." -ForegroundColor Red
        Write-Host "In $Path pruefen: 'git status' und 'git diff' - Aenderungen sichern (git stash) oder committen," -ForegroundColor Red
        Write-Host "kollidierende untracked Dateien umbenennen/verschieben, dann Skript erneut starten." -ForegroundColor Red
        exit 1
    }
}

# --- 1) Code aktualisieren -------------------------------------------------
Invoke-GitPull "TradingJournal-Weaver" $WeaverRoot
Invoke-GitPull "TradingJournal" $TradingJournalRoot

# --- 2) Python-Umgebung (TradingJournal-Weaver) -----------------------------------------
Step "Python-Abhaengigkeiten (TradingJournal-Weaver)"
Set-Location $WeaverRoot
if (-not (Test-Path ".\.venv\Scripts\Activate.ps1")) {
    Write-Error "Keine .venv unter TradingJournal-Weaver gefunden - siehe TradingJournal-Weaver README.md 'Installation'"
}
. .\.venv\Scripts\Activate.ps1
pip install -q -r requirements.txt
Assert-LastExit "pip install -r requirements.txt"

# --- 3) DB-Schema-Migrationen (idempotent) ---------------------------------
# Muessen als Modul (-m dev.<name>) vom TradingJournal-Weaver-Root aus laufen, nicht per
# Dateipfad - die Skripte machen "from src.config import ..." und finden das
# src-Package sonst nicht (sys.path enthaelt dann nur den Skript-Ordner).
Step "DB-Migrationen (TradingJournal-Weaver/dev/migrate_*.py)"
if (-not (Test-Path "$WeaverRoot\dev")) {
    # Haeufigster Grund: Sparse-Klon ohne dev/ - dann fehlen Schema-Aenderungen unbemerkt
    Write-Warning "Ordner TradingJournal-Weaver\dev fehlt - DB-Migrationen koennen nicht laufen. Beheben im Weaver-Ordner mit: git sparse-checkout add dev"
}
$MigrateScripts = Get-ChildItem "$WeaverRoot\dev\migrate_*.py" -ErrorAction SilentlyContinue
if ($MigrateScripts) {
    foreach ($Script in $MigrateScripts) {
        $ModuleName = "dev." + $Script.BaseName
        Write-Host "  -> $ModuleName"
        python -m $ModuleName
        if ($LASTEXITCODE -ne 0) {
            Write-Warning "Migration $ModuleName meldete Fehler (Exit $LASTEXITCODE) - pruefen!"
        }
    }
} else {
    Write-Host "  (keine Migrationsskripte vorhanden)"
}
deactivate

# --- 4) TradingJournal (Laravel) -------------------------------------------
Step "Composer-Abhaengigkeiten"
Set-Location $TradingJournalRoot
composer install --no-interaction --prefer-dist
Assert-LastExit "composer install"

Step "NPM-Abhaengigkeiten + Frontend-Build"
npm install
Assert-LastExit "npm install"
npm run build
Assert-LastExit "npm run build"

Step "Laravel-Migrationen (z.B. users-Tabelle)"
php artisan migrate --force
Assert-LastExit "php artisan migrate --force"

Step "Caches leeren + neu aufbauen"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# --- 5) Build-Version fuer die UI schreiben --------------------------------
# Git-Aufruf passiert hier im Update-Skript, nicht zur Laufzeit in Laravel -
# funktioniert daher auch, wenn auf dem Zielrechner kein Git im PATH ist.
Step "Build-Version schreiben (storage/app/version.json)"
$Tag   = (git describe --tags --always).Trim()
$Hash  = (git rev-parse --short HEAD).Trim()
$Built = Get-Date -Format "dd.MM.yyyy HH:mm"

$Version = [ordered]@{ tag = $Tag; hash = $Hash; built_at = $Built } | ConvertTo-Json
# Set-Content -Encoding utf8 wuerde in Windows PowerShell 5.1 ein BOM voranstellen, an dem
# PHPs json_decode() scheitert - deshalb ohne BOM direkt ueber .NET schreiben.
[System.IO.File]::WriteAllText("$TradingJournalRoot\storage\app\version.json", $Version, [System.Text.UTF8Encoding]::new($false))

Set-Location $TradingJournalRoot
Write-Host ""
Write-Host "=== Update abgeschlossen: $Tag ($Hash) ===" -ForegroundColor Green
