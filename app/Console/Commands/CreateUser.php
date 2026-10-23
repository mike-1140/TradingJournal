<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class CreateUser extends Command
{
    protected $signature   = 'auth:create-user
                                {name     : Anzeigename}
                                {email    : E-Mail-Adresse}
                                {password : Passwort}
                                {--prefix=        : Tabellen-Prefix (Standard: auto aus Name)}
                                {--admin          : User als Admin anlegen}';
    protected $description = 'Legt einen neuen User an';

    public function handle(): int
    {
        if (User::where('email', $this->argument('email'))->exists()) {
            $this->error("E-Mail '{$this->argument('email')}' ist bereits vergeben.");
            return 1;
        }

        $user = User::create([
            'name'         => $this->argument('name'),
            'email'        => $this->argument('email'),
            'password'     => Hash::make($this->argument('password')),
            'table_prefix' => $this->option('prefix') ?: null,
            'is_admin'     => $this->option('admin') ? 1 : 0,
        ]);

        $this->info("User '{$user->name}' angelegt (ID {$user->id}).");
        $this->line("  E-Mail:  {$user->email}");
        $this->line("  Prefix:  {$user->table_prefix}");
        $this->line("  Admin:   " . ($user->is_admin ? 'ja' : 'nein'));
        $this->newLine();
        $this->line("Nächster Schritt — Trading-Tabellen anlegen:");
        $this->line("  cd TradingJournal-Weaver");
        $this->line("  .venv\\Scripts\\python -m tools.maintenance.reset_IBKR_DB money {$user->table_prefix}");
        $this->line("  .venv\\Scripts\\python -m tools.maintenance.setup_psychology_tables money {$user->table_prefix}");

        return 0;
    }
}
