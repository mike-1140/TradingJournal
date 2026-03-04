<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Pagination\LengthAwarePaginator;

class Pagination extends Component
{
    public $paginator;
    public $showPerPage;
    public $showInfo;
    public $perPageOptions;

    public function __construct(
        LengthAwarePaginator $paginator,
                             $showPerPage = true,
                             $showInfo = true,
                             $perPageOptions = null
    ) {
        $this->paginator = $paginator;
        $this->showPerPage = $showPerPage;
        $this->showInfo = $showInfo;
        $this->perPageOptions = $perPageOptions ?? [10, 25, 50, 100];
    }

    public function render()
    {
        return view('components.pagination');
    }
}
