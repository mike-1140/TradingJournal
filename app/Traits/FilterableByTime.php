<?php
namespace App\Traits;

use Carbon\Carbon;
use Livewire\Attributes\Computed;

trait FilterableByTime
{
    // Wir initialisieren die Property in der mount-Methode
    public $timeRange;

    /**
     * Diese Methode wird von Livewire automatisch aufgerufen,
     * wenn die Komponente geladen wird.
     */
    public function mountFilterableByTime()
    {
        // Hole Wert aus Session oder nimm Standard 'ytd'
        $this->timeRange = session('trading_time_range', 'ytd');
    }

    /**
     * Jedes Mal, wenn $timeRange geändert wird, speichern wir in die Session.
     */
    public function updatedTimeRange($value)
    {
        session(['trading_time_range' => $value]);

        // WICHTIG: Sende ein Signal an alle anderen Komponenten
        $this->dispatch('time-range-changed', range: $value);

        if (method_exists($this, 'loadStatistics')) {
            $this->loadStatistics();
        }
    }

    #[Computed]
    public function startDate()
    {
        // Wir nutzen den Wert aus der Session, falls die Property noch nicht gesetzt ist
        $range = $this->timeRange ?? session('trading_time_range', 'ytd');

        return match($range) {
            '13w' => now()->subWeeks(13)->startOfDay(),
            '6m'  => now()->subMonths(6)->startOfDay(),
            'ytd' => now()->startOfYear(),
            'all' => null,
            default => now()->startOfYear(),
        };
    }

    public function applyTimeFilter($query, $dateColumn = 'close_date')
    {
        $startDate = $this->startDate();

        if ($startDate) {
            return $query->where($dateColumn, '>=', $startDate);
        }
        return $query;
    }
}
