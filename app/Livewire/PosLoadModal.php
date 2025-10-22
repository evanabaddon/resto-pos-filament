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
        // Ambil cash_session_id dari session
        $cashSessionId = session('cash_session_id');
        
        if (!$cashSessionId) {
            $this->savedSales = [];
            $this->show = true;
            return;
        }

        // Ambil semua transaksi pada cash session yang sama
        $this->savedSales = Sale::where('cash_session_id', $cashSessionId)
            ->withCount('items')
            ->latest()
            ->take(20)
            ->get();

        $this->show = true;
    }

    public function loadSale($saleId)
    {
        $sale = Sale::find($saleId);
        
        // Cek apakah transaksi masih bisa diedit (hanya draft)
        if ($sale->status !== 'draft') {
            $this->dispatch('showNotification', 
                'Transaksi ini sudah diproses dan tidak bisa diedit.', 
                'warning'
            );
            return;
        }

        $this->dispatch('saleLoaded', saleId: $saleId);
        $this->show = false;
    }

    public function openPayment($saleId)
    {
        $sale = Sale::find($saleId);
        
        // Cek status transaksi
        if ($sale->status === 'paid') {
            $this->dispatch('showNotification', 
                'Transaksi ini sudah dibayar.', 
                'warning'
            );
            return;
        }

        if ($sale->status === 'completed') {
            $this->dispatch('showNotification', 
                'Transaksi ini sudah selesai.', 
                'warning'
            );
            return;
        }

        $this->dispatch('paymentRequested', saleId: $saleId);
        $this->show = false;
    }

    public function printReceipt($saleId)
    {
        // Dispatch event ke PosPaymentModal untuk print struk
        $this->dispatch('openReceiptModal', saleId: $saleId);
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