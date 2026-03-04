<?php

namespace App\View\Components;

use Illuminate\View\Component;

class StatCard extends Component
{
    public $title;
    public $value;
    public $icon;
    public $trend;
    public $trendValue;
    public $color;
    public $subtitle;

    public function __construct(
        $title,
        $value,
        $icon = null,
        $trend = null,
        $trendValue = null,
        $color = 'primary',
        $subtitle = null
    ) {
        $this->title = $title;
        $this->value = $value;
        $this->icon = $icon;
        $this->trend = $trend; // 'up', 'down', 'neutral'
        $this->trendValue = $trendValue;
        $this->color = $color;
        $this->subtitle = $subtitle;
    }

    public function render()
    {
        return view('components.stat-card');
    }
}
