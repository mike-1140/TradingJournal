<?php

namespace App\Traits;

// Helper-Trait für conditional logging
trait DebugsInLocal
{
    protected function debugLog($message, $context = [])
    {

        if (app()->environment('local')) {
            \Log::channel('debug')->info($message, $context);
        }
    }
}
