<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class YahooFinanceService
{
    private string $pythonPath;
    private string $scriptPath;

    public function __construct()
    {
        // Python Interpreter Pfad
        $this->pythonPath = config('services.yahoo_finance.python_path',
            base_path('python/venv/bin/python')
        );

        // Script Pfad
        $this->scriptPath = config('services.yahoo_finance.script_path',
            base_path('python/yahoo_finance.py')
        );

        // Prüfe ob Script existiert
        if (!file_exists($this->scriptPath)) {
            throw new \Exception("Yahoo Finance Python script not found at: {$this->scriptPath}");
        }
    }

    /**
     * Holt historische Kursdaten
     */
    public function getHistoricalData(
        string $symbol,
        string $startDate,
        ?string $endDate = null
    ): ?array {
        $endDate = $endDate ?? now()->format('Y-m-d');
        $cacheKey = "yahoo:historical:{$symbol}:{$startDate}:{$endDate}";
        return [];

//        return Cache::remember($cacheKey, 3600, function () use ($symbol, $startDate, $endDate) {
//            $result = Process::run([
//                $this->pythonPath,
//                $this->scriptPath,
//                'historical',
//                strtoupper($symbol),
//                $startDate,
//                $endDate
//            ]);
//
//            if ($result->failed()) {
//                Log::error('Yahoo Finance Historical Error', [
//                    'symbol' => $symbol,
//                    'error' => $result->errorOutput(),
//                    'output' => $result->output()
//                ]);
//
//                throw new \Exception("Failed to fetch historical data: " . $result->errorOutput());
//            }
//
//            $data = json_decode($result->output(), true);
//
//            if (!$data['success']) {
//                throw new \Exception($data['error'] ?? 'Unknown error');
//            }
//
//            return $data;
//        });
    }

    /**
     * Holt aktuellen Quote
     */
    public function quote(string $symbol): array
    {
        $cacheKey = "yahoo:quote:{$symbol}";

        // ✅ Intelligente Cache-Dauer basierend auf Handelszeiten
        $cacheDuration = $this->getCacheDuration();
        return [];
//        return Cache::remember($cacheKey, $cacheDuration, function () use ($symbol) {
//
//            $command = [
//                $this->pythonPath,
//                $this->scriptPath,
//                'quote',
//                strtoupper($symbol)
//            ];
//
//            $result = Process::path(dirname($this->scriptPath))
//                ->timeout(30)
//                ->run($command);
//
//            if ($result->failed()) {
//                Log::error('Yahoo Finance Quote Error', [
//                    'symbol' => $symbol,
//                    'exit_code' => $result->exitCode(),
//                    'error' => $result->errorOutput(),
//                    'output' => $result->output()
//                ]);
//
//                return [
//                    'success' => false,
//                    'error' => 'Failed to fetch quote: ' . $result->errorOutput()
//                ];
//            }
//
//            $data = json_decode($result->output(), true);
//
//            if (json_last_error() !== JSON_ERROR_NONE) {
//                return [
//                    'success' => false,
//                    'error' => 'Invalid JSON response'
//                ];
//            }
//
//            if (!isset($data['success']) || !$data['success']) {
//                return [
//                    'success' => false,
//                    'error' => $data['error'] ?? 'Unknown error'
//                ];
//            }
//
//            return $data;
//        });
    }

    /**
     * Bestimmt Cache-Dauer basierend auf Handelszeiten
     */
    private function getCacheDuration(): int
    {
        $now = now('America/New_York');
        $hour = $now->hour;
        $minute = $now->minute;
        $isWeekday = $now->isWeekday();

        $isMarketOpen = $isWeekday
            && (($hour == 9 && $minute >= 30) || ($hour >= 10 && $hour < 16));

        if ($isMarketOpen) {
            return config('services.yahoo_finance.cache.market_hours_ttl', 120);
        } else {
            return config('services.yahoo_finance.cache.quote_ttl', 300);
        }
    }

    /**
     * Prüft ob der Service verfügbar ist
     */
    public function healthCheck(): array
    {
        try {
            $result = Process::timeout(5)->run([
                $this->pythonPath,
                '--version'
            ]);

            return [
                'available' => $result->successful(),
                'python_version' => trim($result->output()),
                'script_exists' => file_exists($this->scriptPath)
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
