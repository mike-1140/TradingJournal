<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Admin User
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            // table_prefix wird automatisch generiert
        ]);

        // Test User
        $michael = User::create([
            'name' => 'Michael',
            'email' => 'michael@example.com',
            'password' => Hash::make('password'),
            // table_prefix wird automatisch generiert
        ]);

        $this->command->info('Users created:');
        $this->command->info("  Admin: {$admin->table_prefix}");
        $this->command->info("  Michael: {$michael->table_prefix}");
    }
}
