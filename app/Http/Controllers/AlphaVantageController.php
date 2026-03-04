<?php

namespace App\Http\Controllers;

use App\Services\AlphaVantageService;

class AlphaVantageController extends Controller
{
    public function quote(string $symbol, AlphaVantageService $alpha)
    {
        return response()->json(
            $alpha->quote($symbol)
        );
    }
}
