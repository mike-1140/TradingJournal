<?php

namespace App\Traits;

trait HasSmartBackUrl
{
    public string $backUrl = '';

    /**
     * "Zurück"-Ziel aus der tatsächlichen Herkunfts-URL ableiten (inkl. Filter/Query-String),
     * statt fix zur unfilterten Listenansicht zu verlinken. Nur interne URLs akzeptieren,
     * sonst Fallback auf die übergebene Route.
     */
    public function setBackUrl(string $fallbackRoute): void
    {
        $previous = url()->previous();

        $this->backUrl = str_starts_with($previous, url('/'))
            ? $previous
            : route($fallbackRoute);
    }
}
