<?php

namespace App\Traits;

use App\Models\PsychologyEntry;

/**
 * Nicht jeder User/jede Environment hat das Psychology-Modul eingerichtet
 * (z.B. tom_/MONEY) — die psychology_*-Tabellen existieren dann schlicht
 * nicht. Ohne diesen Check würde jede Psychology-Seite mit MySQL-Fehler 1146
 * abstürzen; stattdessen zeigen wir eine freundliche Hinweisseite.
 */
trait RequiresPsychologyModule
{
    protected function psychologyModuleMissing(): bool
    {
        $connection = PsychologyEntry::getConnectionForEnvironment($this->selectedEnvironment);
        return !PsychologyEntry::tableExistsFor($connection);
    }

    protected function moduleNotInstalledView()
    {
        return view('livewire.psychology.partials.module-not-installed');
    }
}
