<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugYahooFinance extends Command
{
    protected $signature = 'yahoo:debug';
    protected $description = 'Debug Yahoo Finance paths';

    public function handle(): int
    {
        $this->info("=== Yahoo Finance Path Debug ===\n");

        // Config-Werte
        $pythonPath = config('services.yahoo_finance.python_path', base_path('python/venv/bin/python'));
        $scriptPath = config('services.yahoo_finance.script_path', base_path('python/yahoo_finance.py'));

        $this->info("Config Python Path: " . $pythonPath);
        $this->info("Exists: " . (file_exists($pythonPath) ? '✓' : '✗'));

        $this->newLine();
        $this->info("Config Script Path: " . $scriptPath);
        $this->info("Exists: " . (file_exists($scriptPath) ? '✓' : '✗'));

        // Alternative Pfade prüfen
        $this->newLine();
        $this->info("=== Alternative Paths ===");

        $alternatives = [
            'python/venv/Scripts/python.exe' => base_path('python/venv/Scripts/python.exe'),
            'python/venv/Scripts/python3.exe' => base_path('python/venv/Scripts/python3.exe'),
            'python/venv/bin/python' => base_path('python/venv/bin/python'),
            'python/yahoo_finance.py' => base_path('python/yahoo_finance.py'),
        ];

        foreach ($alternatives as $name => $path) {
            $exists = file_exists($path);
            $this->line($name . ": " . ($exists ? "✓ {$path}" : "✗ not found"));
        }

        // System Python
        $this->newLine();
        $this->info("=== System Python ===");

        try {
            $result = \Illuminate\Support\Facades\Process::run('where python');
            if ($result->successful()) {
                $this->info("Found: " . trim($result->output()));
            } else {
                $this->error("Not found in PATH");
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }

        // Base Path
        $this->newLine();
        $this->info("Laravel Base Path: " . base_path());

        return 0;
    }
}
