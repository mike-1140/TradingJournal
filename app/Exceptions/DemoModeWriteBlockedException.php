<?php

namespace App\Exceptions;

use Exception;

/**
 * Wird geworfen, wenn im Demo-Modus (config('app.demo_mode')) ein schreibender
 * Eloquent-Zugriff versucht wird. Behandlung siehe bootstrap/app.php (renderable).
 */
class DemoModeWriteBlockedException extends Exception
{
    public function __construct(string $message = 'Diese Demo ist read-only — Änderungen werden nicht gespeichert.')
    {
        parent::__construct($message);
    }
}
