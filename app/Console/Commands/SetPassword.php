<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SetPassword extends Command
{
    protected $signature   = 'auth:set-password {user_id : ID des Users} {password : Neues Passwort}';
    protected $description = 'Setzt das Passwort eines Users';

    public function handle(): int
    {
        $userId   = (int) $this->argument('user_id');
        $password = $this->argument('password');

        $user = User::find($userId);

        if (!$user) {
            $this->error("User ID {$userId} nicht gefunden.");
            return 1;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Passwort für '{$user->name}' (ID {$userId}) gesetzt.");
        return 0;
    }
}
