<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $connection = 'mysql'; // Laravel Haupt-DB

    protected $fillable = [
        'name',
        'email',
        'password',
        'table_prefix',
        'filter_preset',
        'filter_from',
        'filter_to',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'filter_from' => 'date',
        'filter_to'   => 'date',
        'is_admin'    => 'boolean',
    ];

    /**
     * Auto-generiere Präfix beim Erstellen
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Nur wenn noch kein Präfix gesetzt
            if (empty($user->table_prefix)) {
                $user->table_prefix = self::generateTablePrefix($user->name);

                flow_log('Auto-generated table_prefix on user creation', [
                    'user_name' => $user->name,
                    'prefix' => $user->table_prefix,
                ]);
            }
        });
    }

    /**
     * Generiert eindeutigen Tabellen-Präfix
     */
    public static function generateTablePrefix(string $name): string
    {
        $cleanName = preg_replace('/[^a-z0-9]/', '', strtolower($name));
        $prefix = 'user_' . $cleanName . '_';

        // Prüfe auf Duplikate und füge Nummer hinzu wenn nötig
        $counter = 1;
        $originalPrefix = $prefix;

        while (self::where('table_prefix', $prefix)->exists()) {
            $prefix = $originalPrefix . $counter . '_';
            $counter++;
        }

        return $prefix;
    }

    /**
     * Prüft ob User Tabellen hat
     */
    public function hasSetupTables(): bool
    {
        if (empty($this->table_prefix)) {
            return false;
        }

        // Prüfe in einer der Datenbanken ob Tabellen existieren
        $connection = \DB::connection('mysql_paper');
        $tableName = $this->table_prefix . 'trades';

        return \Schema::connection('mysql_paper')->hasTable($tableName);
    }
}
