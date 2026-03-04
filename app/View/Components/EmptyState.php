<?php

namespace App\View\Components;

use Illuminate\View\Component;

class EmptyState extends Component
{
    public $icon;
    public $title;
    public $message;
    public $actionText;
    public $actionRoute;

    public function __construct(
        $icon = 'inbox',
        $title = 'No data found',
        $message = null,
        $actionText = null,
        $actionRoute = null
    ) {
        $this->icon = $icon;
        $this->title = $title;
        $this->message = $message;
        $this->actionText = $actionText;
        $this->actionRoute = $actionRoute;
    }

    public function render()
    {
        return view('components.empty-state');
    }
}
