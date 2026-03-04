<?php

namespace App\Http\Controllers;

use App\Services\YahooFinanceService;

class YahooFinanceController extends Controller
{
    public function quote(string $symbol, YahooFinanceService $yahoo)
    {
        return response()->json(
            $yahoo->quote($symbol)
        );
    }

    public function history(string $symbol, YahooFinanceService $yahoo)
    {
        return response()->json(
            $yahoo->history($symbol)
        );
    }
}
