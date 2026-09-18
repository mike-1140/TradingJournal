<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ersetzt den manuellen "php artisan setup:auth"-Schritt (der die Spalte bisher per
// rohem ALTER TABLE ausserhalb der Migrationen anlegte) durch eine reguläre Migration
// -- läuft künftig automatisch mit `migrate`/RefreshDatabase mit. Idempotent per
// hasColumn-Check, damit bereits per setup:auth manuell migrierte Installationen (Spalte
// existiert schon) beim Nachziehen dieser Migration nicht mit "Duplicate column" scheitern.
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        if (!Schema::connection('mysql')->hasColumn('users', 'is_admin')) {
            Schema::connection('mysql')->table('users', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false)->after('remember_token');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
