<?php

namespace App\Livewire;

use Livewire\Component;

class PosNotification extends Component
{
    // No server-side listeners needed - handled by AlpineJS globally
    // public string $message = '';
    // public string $type = 'info'; 
    // public bool $visible = false;

    public function render()
    {
        return view('livewire.pos-notification');
    }
}
