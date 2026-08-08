# TradingJournal - einmalig ausfuehren, um eine Desktop-Verknuepfung anzulegen.
#
# Doppelklick auf die erzeugte Verknuepfung oeffnet PowerShell, bereits im
# TradingJournal-Ordner - danach z.B. direkt "php artisan serve" eintippen.
#
# Aufruf (einmalig, aus dem TradingJournal-Ordner heraus):
#   . .\create-desktop-shortcut.ps1
# oder im Explorer: Rechtsklick auf die Datei -> "Mit PowerShell ausfuehren"
#
# Ein einfacher Doppelklick auf DIESE Datei startet sie NICHT automatisch - .ps1-Dateien
# oeffnen sich per Doppelklick standardmaessig in einem Text-Editor (Windows-
# Sicherheitsmechanismus). Genau deshalb legt dieses Skript stattdessen eine echte
# Desktop-Verknuepfung (.lnk) an, die per Doppelklick zuverlaessig funktioniert.

$ProjectDir = $PSScriptRoot
$Desktop = [Environment]::GetFolderPath("Desktop")
$ShortcutPath = Join-Path $Desktop "TradingJournal.lnk"

$InnerCommand = "Set-Location '$ProjectDir'"

$WshShell = New-Object -ComObject WScript.Shell
$Shortcut = $WshShell.CreateShortcut($ShortcutPath)
$Shortcut.TargetPath = "powershell.exe"
$Shortcut.Arguments = "-NoExit -ExecutionPolicy Bypass -Command `"$InnerCommand`""
$Shortcut.WorkingDirectory = $ProjectDir
$Shortcut.IconLocation = "powershell.exe,0"
$Shortcut.Description = "TradingJournal: PowerShell im Projektordner oeffnen"
$Shortcut.Save()

Write-Host "Desktop-Verknuepfung erstellt: $ShortcutPath" -ForegroundColor Green
Write-Host "Doppelklick darauf oeffnet PowerShell, bereits im TradingJournal-Ordner." -ForegroundColor Green
