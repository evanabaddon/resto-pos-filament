<?php

namespace App\Livewire;

use Livewire\Component;

class PosManualDiscountModal extends Component
{
    public $show = false;
    public $discountType = 'fixed'; // fixed or percentage
    public $value = '';
    public $reason = '';

    protected $listeners = ['openManualDiscountModal' => 'open'];

    public function open()
    {
        $this->show = true;
        // Reset state
        $this->discountType = 'fixed';
        $this->value = '';
        $this->reason = '';
    }

    public function close()
    {
        $this->show = false;
    }

    public function apply()
    {
        $this->validate([
            'value' => 'required|numeric|min:0',
            'discountType' => 'required|in:fixed,percentage',
        ]);

        $this->dispatch(
            'applyManualDiscount',
            type: $this->discountType,
            value: (float) $this->value,
            reason: $this->reason
        );

        $this->close();
    }

    public function render()
    {
        return view('livewire.pos-manual-discount-modal');
    }
}
