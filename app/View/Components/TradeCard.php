<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TradeCard extends Component
{
    public $title;
    public $icon;
    public $headerClass;
    public $bodyClass;
    public $collapsible;
    public $collapsed;
    public $cardId;

    public function __construct(
        $title = null,
        $icon = null,
        $headerClass = '',
        $bodyClass = '',
        $collapsible = false,
        $collapsed = false
    ) {
        $this->title = $title;
        $this->icon = $icon;
        $this->headerClass = $headerClass;
        $this->bodyClass = $bodyClass;
        $this->collapsible = $collapsible;
        $this->collapsed = $collapsed;
        $this->cardId = 'card-' . uniqid();
    }

    public function render()
    {
        return view('components.trade-card');
    }
}
