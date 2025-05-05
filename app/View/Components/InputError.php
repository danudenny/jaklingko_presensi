<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class InputError extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $message = '',
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.input-error');
    }
} 