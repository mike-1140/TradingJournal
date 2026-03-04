<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\Tag;

class TagBadge extends Component
{
    public $tag;
    public $size;
    public $clickable;
    public $showIcon;

    public function __construct($tag, $size = 'md', $clickable = false, $showIcon = false)
    {
        $this->tag = $tag;
        $this->size = $size;
        $this->clickable = $clickable;
        $this->showIcon = $showIcon;
    }

    public function render()
    {
        return view('components.tag-badge');
    }
}
