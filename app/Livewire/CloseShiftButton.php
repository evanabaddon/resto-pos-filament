<?php

namespace App\Livewire;

use Livewire\Component;

class CloseShiftButton extends Component
{
    public function closeShift()
    {
        $this->dispatch('closeCashSessionFromLayout'); // event yang akan didengar oleh POS
    }
    
    public function render()
    {
        return view('livewire.close-shift-button');
    }
}
