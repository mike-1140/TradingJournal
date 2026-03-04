<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AlphaVantageService
{
    protected string $url = 'https://www.alphavantage.co/query';

    /**
     * LIVE (intraday) Kurs laden
     */
    public function price(string $symbol): ?float
    {
        $symbol = strtoupper($symbol);

        return Cache::remember("alpha:price:$symbol", 30, function () use ($symbol) {

            $response = Http::get($this->url, [
                'function' => 'TIME_SERIES_INTRADAY',
                'symbol' => $symbol,
                'interval' => '1min',
                'apikey' => config('services.alphavantage.key'),
            ]);

            if ($response->failed()) {
                return null;
            }

            $json = $response->json();

            if (!isset($json['Time Series (1min)'])) {
                return null; // z.B. bei Rate Limit
            }

            $latest = array_key_first($json['Time Series (1min)']);
            $candle = $json['Time Series (1min)'][$latest];

            return (float) $candle['4. close'];
        });
    }

    /**
     * Letzten Börsenschluss laden (fallback)
     */
    public function quote(string $symbol): array
    {
        $symbol = strtoupper($symbol);

        return Cache::remember("alpha:quote:$symbol", 300, function () use ($symbol) {

            $response = Http::get($this->url, [
                'function' => 'GLOBAL_QUOTE',
                'symbol' => $symbol,
                'apikey' => config('services.alphavantage.key'),
            ]);

            if ($response->failed()) {
                return [];
            }

            $json = $response->json();

            if (!isset($json['Global Quote'])) {
                return [];
            }

            // Normalisierte Rückgabe

            return [
                'symbol'      => $json['Global Quote']['01. symbol'] ?? null,
                'price'       => (float)($json['Global Quote']['05. price'] ?? 0),
                'change'      => (float)($json['Global Quote']['09. change'] ?? 0),
                'change_pct'  => (float)str_replace('%', '', $json['Global Quote']['10. change percent'] ?? 0),
                'volume'      => (int)($json['Global Quote']['06. volume'] ?? 0),
                // 'latest_day'  => $json['Global Quote']['07. latest trading day'] ?? null,
                'latest_day' => isset($json['Global Quote']['07. latest trading day'])
                    ? Carbon::parse($json['Global Quote']['07. latest trading day'])->format('d.m.Y')
                    : null,
                'previous_close' => (float)($json['Global Quote']['08. previous close'] ?? 0),
                'open'        => (float)($json['Global Quote']['02. open'] ?? 0),
                'high'        => (float)($json['Global Quote']['03. high'] ?? 0),
                'low'         => (float)($json['Global Quote']['04. low'] ?? 0),
            ];
        });
    }

    /**
     * Kombiniert: zuerst LIVE → dann fallback auf GLOBAL_QUOTE
     */
    public function liveOrLast(string $symbol): ?float
    {
        // 1) live intraday Kurs
        $live = $this->price($symbol);

        if (!empty($live)) {
            return $live;
        }

        // 2) fallback: letzter Schlusskurs
        $quote = $this->quote($symbol);

        return $quote['close'] ?? null;
    }
}
