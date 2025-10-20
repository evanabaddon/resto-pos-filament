<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sale;
use Filament\Notifications\Notification;

class PosPaymentModal extends Component
{
    public $show = false;
    public $saleId;
    public $finalTotal = 0;
    public $amount_paid = 0;
    public $payment_method = 'cash';
    public $saleItems = [];
    public $subtotal = 0;
    public $tax = 0;
    public $discount = 0;
    public $customerName = '';
    public $invoiceNumber = '';

    protected $rules = [
        'amount_paid' => 'required|numeric|min:0',
        'payment_method' => 'required|string',
    ];

    protected $listeners = ['openPaymentModal'];

    public function openPaymentModal($saleId)
    {
        $sale = Sale::with('items')->findOrFail($saleId);
        
        $this->saleId = $sale->id;
        $this->finalTotal = (float) ($sale->final_total ?? 0);
        $this->subtotal = (float) ($sale->subtotal ?? 0);
        $this->tax = (float) ($sale->tax ?? 0);
        $this->discount = (float) ($sale->discount ?? 0);
        $this->customerName = $sale->customer_name ?? 'Umum';
        $this->invoiceNumber = $sale->invoice_number;
        $this->amount_paid = $this->finalTotal;
        $this->payment_method = 'cash';
        
        // Load sale items untuk struk
        $this->saleItems = $sale->items->map(function ($item) {
            return [
                'name' => $item->product->name ?? 'Produk',
                'quantity' => $item->quantity,
                'price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ];
        })->toArray();

        $this->show = true;
    }

    public function getChangeProperty()
    {
        return max(0, (float)($this->amount_paid ?? 0) - (float)($this->finalTotal ?? 0));
    }

    public function processPayment()
    {
        $this->validate();

        // Validasi untuk cash
        if ($this->payment_method === 'cash' && $this->amount_paid < $this->finalTotal) {
            Notification::make()
                ->title('Pembayaran Gagal')
                ->body('Jumlah bayar tidak boleh kurang dari total tagihan.')
                ->danger()
                ->send();
            return;
        }

        $this->dispatch('paymentProcessed', 
            saleId: $this->saleId,
            paymentMethod: $this->payment_method,
            amountPaid: $this->amount_paid
        );

        $this->show = false;
        $this->reset();
    }

    public function closeModal()
    {
        $this->show = false;
        $this->reset();
    }

    public function render()
    {
        return view('livewire.pos-payment-modal');
    }
}