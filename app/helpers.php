<?php
if (!function_exists('dbg')) {
    function dbg(...$args): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $location = basename($trace['file']) . ':' . $trace['line'];

        $output = "[{$location}] " . date('H:i:s') . "\n";

        foreach ($args as $arg) {
            $output .= print_r($arg, true) . "\n";
        }

        file_put_contents(
            storage_path('logs/debug.log'),
            $output . "---\n",
            FILE_APPEND
        );
    }
}
