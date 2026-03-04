<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AlertMessage extends Component
{
    public $type;
    public $icon;
    public $dismissible;

    public function __construct($type = 'info', $dismissible = true)
    {
        $this->type = $type;
        $this->dismissible = $dismissible;
        $this->icon = $this->getIcon($type);
    }

    private function getIcon($type)
    {
        return match($type) {
            'success' => 'check-circle',
            'danger', 'error' => 'exclamation-triangle',
            'warning' => 'exclamation-circle',
            'info' => 'info-circle',
            default => 'info-circle',
        };
    }

    public function render()
    {
        return view('components.alert-message');
    }
}
