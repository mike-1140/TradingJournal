# TradingJournal - Komfort-Skript: wechselt in dieses Verzeichnis.
#
# Mit vorangestelltem Punkt aufrufen (Dot-Sourcing), damit der Verzeichniswechsel im
# aufrufenden Fenster erhalten bleibt, nicht nur innerhalb des Skripts:
#
#   . .\open.ps1
#
# Funktioniert unabhaengig davon, wo der Monorepo-Root-Ordner liegt (siehe
# docs/handbuch/technik/40-installation-windows.md - Pfad ist frei waehlbar).

Set-Location $PSScriptRoot
Write-Host "TradingJournal bereit: $PSScriptRoot" -ForegroundColor Green
Write-Host "Server starten: php artisan serve" -ForegroundColor DarkGray
