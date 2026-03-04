<?php
namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;

class UserSwitchService
{
    public static function switchUser(int $userId): bool
    {
        $user = User::find($userId);

        if (!$user) {
            return false;
        }

        // 1. völlig neue Session starten (Security)
        Session::invalidate();
        Session::regenerateToken();

        // 2. neuen User einloggen
        Auth::login($user);

        // 3. Session neu generieren
        Session::regenerate();

        // 4. Custom Daten setzen (z. B. Prefix)
        session(['table_prefix' => $user->table_prefix]);

        return true;
    }
}
