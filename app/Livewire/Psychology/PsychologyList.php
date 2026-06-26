<?php

namespace App\Livewire\Psychology;

use App\Traits\ManagesEnvironment;
use Livewire\Component;

class PsychologyList extends Component
{
    use ManagesEnvironment;

    protected $listeners = [
        'environment-changed' => '$refresh',
    ];

    public function mount(): void
    {
        $this->mountManagesEnvironment();
    }

    public function render()
    {
        return view('livewire.psychology.psychology-list');
    }
}
