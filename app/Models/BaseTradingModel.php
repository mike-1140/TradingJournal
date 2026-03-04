<?php
// app/Models/BaseTradingModel.php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\TradingHelper;

abstract class BaseTradingModel extends Model
{
    /**
     * Gibt die aktuelle Trading-Connection zurück
     */
    protected $appends = [
        'formatted_option',
    ];

    public function getConnectionName()
    {
        $environment = session(
            'trading_environment',
            config('trading.default_environment')
        );

        // ZENTRAL: Nur noch EINE Stelle!
        return TradingHelper::getConnection($environment);
    }

    /**
     * Virtuelles Attribut: formatted_option
     */
    public function getFormattedOptionAttribute(): string
    {
        return $this->formatOptionSymbol($this->symbol);
    }

    /**
     * Formatiert ein Optionssymbol wie:
     * OMEX 240719P00004000
     * → OMEX Put 4.00 · Exp 19.07.2024
     */
    protected function formatOptionSymbol(?string $symbol): string
    {
        flow_log('formatOptionSymbol (BaseTradingModel): ', [$symbol]);
        if (!$symbol) {
            return '';
        }

        if (!preg_match('/^([A-Z]+)\s(\d{2})(\d{2})(\d{2})([CP])(\d{8})$/', $symbol, $m)) {
            return $symbol; // Fallback, falls Format unbekannt
        }

        [, $underlying, $yy, $mm, $dd, $type, $strikeRaw] = $m;

        $year = '20' . $yy;
        // $date = "{$dd}.{$mm}.{$year}";
        // Datum: 19JUL24
        $date = sprintf(
            '%02d%s%02d',
            (int) $dd,
            strtoupper(Carbon::createFromDate(
                2000 + (int) $yy,
                (int) $mm,
                (int) $dd
            )->format('M')),
            (int) $yy
        );

        $strike = number_format((int)$strikeRaw / 1000, 2);

        $typeText = $type === 'C' ? 'Call' : 'Put';

        //return "{$underlying} {$typeText} {$strike} · Exp {$date}";
        return "{$underlying}  {$date}  {$strike}  {$type}";
    }

}
