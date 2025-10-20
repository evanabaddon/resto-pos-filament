<?php

namespace App\Livewire;

use App\Models\Sale;
use Livewire\Component;

class PosLoadModal extends Component
{
    public $show = false;
    public $savedSales = [];

    protected $listeners = ['openLoadModal'];

    public function openLoadModal()
    {
        $this->savedSales = Sale::where('status', 'draft')
            ->withCount('items')
            ->latest()
            ->take(20)
            ->get();

        $this->show = true;
    }

    public function loadSale($saleId)
    {
        $this->dispatch('saleLoaded', saleId: $saleId);
        $this->show = false;
    }

    public function openPayment($saleId)
    {
        $this->dispatch('paymentRequested', saleId: $saleId);
        $this->show = false;
    }

    public function closeModal()
    {
        $this->show = false;
    }
    
    public function render()
    {
        return view('livewire.pos-load-modal');
    }
}
