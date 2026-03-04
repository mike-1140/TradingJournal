<?php

namespace App\View\Components;

use Illuminate\View\Component;

class FilterBar extends Component
{
    public $title;
    public $collapsible;
    public $collapsed;
    public $showReset;

    public function __construct(
        $title = 'Filters',
        $collapsible = true,
        $collapsed = false,
        $showReset = true
    ) {
        $this->title = $title;
        $this->collapsible = $collapsible;
        $this->collapsed = $collapsed;
        $this->showReset = $showReset;
    }

    public function render()
    {
        return view('components.filter-bar');
    }
}
