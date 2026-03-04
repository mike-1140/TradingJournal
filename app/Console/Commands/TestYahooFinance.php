<?php

namespace App\Console\Commands;

use App\Services\YahooFinanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestYahooFinance extends Command
{
    protected $signature = 'yahoo:test {symbol=AAPL}';
    protected $description = 'Test Yahoo Finance integration';

    public function handle(YahooFinanceService $yahooFinance): int
    {
        $symbol = $this->argument('symbol');

        $this->info("Testing Yahoo Finance Service...");

        // Health Check
        $health = $yahooFinance->healthCheck();
        $this->info("Health Check: " . ($health['available'] ? '✓' : '✗'));

        // Quote
        $this->info("\nFetching quote for {$symbol}...");
        $quote = $yahooFinance->quote($symbol);
        $this->table(['Key', 'Value'], collect($quote['data'] ?? [])->map(fn($v, $k) => [$k, $v]));

        // Historical
        $this->info("\nFetching historical data...");
        $historical = $yahooFinance->getHistoricalData($symbol, now()->subDays(7)->format('Y-m-d'));
        $this->info("Got {$historical['count']} days of data");
        Log::warning($historical);

        return 0;
    }
}
