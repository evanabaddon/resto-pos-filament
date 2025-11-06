<?php

namespace App\Livewire;

use Livewire\Component;

class CloseShiftButton extends Component
{
    public $showConfirmationModal = false;
    public $cashOutInput;

    public function openConfirmationModal()
    {
        $this->showConfirmationModal = true;
    }

    public function closeConfirmationModal()
    {
        $this->showConfirmationModal = false;
    }

    public function closeShift()
    {
        $this->dispatch('closeCashSessionFromLayout', cashOut: $this->cashOutInput);
        $this->closeConfirmationModal();
    }
    
    public function render()
    {
        return view('livewire.close-shift-button');
    }
}
