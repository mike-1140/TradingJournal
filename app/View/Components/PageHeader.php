<?php

namespace App\View\Components;

use Illuminate\View\Component;

class PageHeader extends Component
{
    public $title;
    public $subtitle;
    public $icon;
    public $backRoute;
    public $backText;

    public function __construct(
        $title,
        $subtitle = null,
        $icon = null,
        $backRoute = null,
        $backText = 'Back'
    ) {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->icon = $icon;
        $this->backRoute = $backRoute;
        $this->backText = $backText;
    }

    public function render()
    {
        return view('components.page-header');
    }
}
