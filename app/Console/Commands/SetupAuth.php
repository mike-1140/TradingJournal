<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupAuth extends Command
{
    protected $signature   = 'setup:auth {--admin-id=1 : ID des Admin-Users}';
    protected $description = 'Fügt is_admin-Spalte zur users-Tabelle hinzu und setzt Admin-Flag';

    public function handle(): int
    {
        if (!Schema::hasColumn('users', 'is_admin')) {
            DB::statement('ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0');
            $this->info('Spalte is_admin hinzugefügt.');
        } else {
            $this->line('Spalte is_admin bereits vorhanden.');
        }

        $adminId  = (int) $this->option('admin-id');
        $affected = DB::table('users')->where('id', $adminId)->update(['is_admin' => 1]);

        if ($affected) {
            $this->info("User ID {$adminId} als Admin markiert.");
        } else {
            $this->warn("User ID {$adminId} nicht gefunden — bitte --admin-id prüfen.");
        }

        $this->line('');
        $this->line('Nächste Schritte:');
        $this->line('  php artisan tinker');
        $this->line('  >>> $u = App\Models\User::find(1); $u->password = Hash::make("PASSWORT"); $u->save();');

        return 0;
    }
}
