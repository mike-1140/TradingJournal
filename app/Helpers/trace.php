<?php

use Illuminate\Support\Facades\Log;

if (!function_exists('trace')) {
    function trace(string $message = null, array $context = []): void
    {
        // call stack prüfen, wir wollen die aufrufende Klasse/Methode
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($trace[1]['class'])
            ? $trace[1]['class'] . '::' . $trace[1]['function']
            : ($trace[1]['function'] ?? 'unknown');

        Log::channel('flow')->debug("\u{1F9ED} $caller - $message", $context);
    }
}

function flow_trace(bool $override = false)
{
    if (config('app.flow_logging') || $override) {
        if (function_exists('trace')) {
            trace();
        }
    }
}

function flow_log($message, array $context = [], bool $override = false)
{
    $global = config('app.flow_logging');

    // Wenn global aktiv ODER Override aktiv ist → loggen
    if ($global || $override) {
        Log::channel('flow')->info($message, $context);
    }
}
