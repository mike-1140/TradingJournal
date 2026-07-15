<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultUser
{
    /**
     * Set a default user once per session if no user is logged in.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Nur lokal: auf dem Server greift stattdessen die normale auth-Middleware
        if (!app()->environment('local')) {
            return $next($request);
        }

        // Nur einmal pro Browser-Session ausführen
        if (!$request->session()->has('default_user_initialized')) {

            flow_log('SetDefaultUser: First session access detected.', [], true);

            // Falls kein User eingeloggt ist → Default-User setzen
            if (!Auth::check()) {

                $defaultUserId = config('app.default_user_id', 1);

                flow_log('SetDefaultUser: Logging in default user', [
                    'default_user_id' => $defaultUserId,
                ]);

                // Sicherstellen, dass der Benutzer existiert
                if ($defaultUserId && \App\Models\User::find($defaultUserId)) {

                    Auth::loginUsingId($defaultUserId);

                    // Falls andere Logik die Userwahl braucht
                    $request->session()->put('selected_user_id', $defaultUserId);
                } else {
                    flow_log("SetDefaultUser: Default user {$defaultUserId} does not exist.");
                }
            }

            // Markieren, dass die Initialisierung erfolgt ist
            $request->session()->put('default_user_initialized', true);
        }

        return $next($request);
    }
}
