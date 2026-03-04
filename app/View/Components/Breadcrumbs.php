<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Breadcrumbs extends Component
{
    public $items;
    public $divider;

    public function __construct($items = [], $divider = 'chevron-right')
    {
        $this->items = $items;
        $this->divider = $divider;
    }

    public function render()
    {
        return view('components.breadcrumbs');
    }
}
