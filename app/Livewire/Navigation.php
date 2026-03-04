<?php

namespace App\Livewire;

use Livewire\Component;
use App\Traits\FilterableByTime; // Trait einbinden

class Navigation extends Component
{
    use FilterableByTime; // Trait nutzen

    public $selectedEnvironment;

    protected $listeners = [
        'environment-changed' => '$refresh',
        'time-range-changed' => '$refresh' // Jetzt reagiert alles auf den Zeitfilter!
    ];

    public function mount()
    {
        $this->selectedEnvironment = session('trading_environment', 'PAPER');
        // Initialisiert $this->timeRange aus der Session
        $this->mountFilterableByTime();
    }

    public function render()
    {
        $this->selectedEnvironment = session('trading_environment', 'PAPER');
        return view('livewire.navigation');
    }
}
