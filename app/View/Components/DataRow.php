<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DataRow extends Component
{
    public $label;
    public $value;
    public $icon;
    public $badge;
    public $badgeColor;

    public function __construct(
        $label,
        $value = null,
        $icon = null,
        $badge = false,
        $badgeColor = 'secondary'
    ) {
        $this->label = $label;
        $this->value = $value;
        $this->icon = $icon;
        $this->badge = $badge;
        $this->badgeColor = $badgeColor;
    }

    public function render()
    {
        return view('components.data-row');
    }
}
