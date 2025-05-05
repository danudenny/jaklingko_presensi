<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Modal extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id = '',
        public string $maxWidth = '2xl',
        public bool $closeable = true
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.modal');
    }
} 