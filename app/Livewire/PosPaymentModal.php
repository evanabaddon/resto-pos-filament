<?php

namespace App\Livewire;

use App\Models\Sale;
use Livewire\Component;
use App\Models\PaymentMethod;
use Filament\Notifications\Notification;

class PosPaymentModal extends Component
{
    public $show = false;
    public $saleId;
    public $finalTotal = 0;
    public $amount_paid = 0;
    public $payment_method = '';
    public $paymentMethods = [];
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

    public function mount()
    {
        // Load payment methods saat komponen diinisialisasi
        $this->paymentMethods = PaymentMethod::active()
            ->get()
            ->map(function ($method) {
                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'code' => $method->code,
                ];
            })
            ->toArray();

        // Set default payment method (cash)
        if (!empty($this->paymentMethods)) {
            $cashMethod = collect($this->paymentMethods)->firstWhere('code', 'cash');
            $this->payment_method = $cashMethod ? $cashMethod['id'] : $this->paymentMethods[0]['id'];
        }
    }

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
        // $this->payment_method = 'cash';
        
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

    public function getSelectedPaymentMethodProperty()
    {
        return collect($this->paymentMethods)->firstWhere('id', $this->payment_method);
    }

    public function getIsCashPaymentProperty()
    {
        $method = $this->selectedPaymentMethod;
        return $method && $method['code'] === 'cash';
    }

    // Method untuk diakses di view
    public function isCashPayment()
    {
        return $this->isCashPayment;
    }

    // Method untuk mendapatkan selected payment method di view
    public function getSelectedMethod()
    {
        return $this->selectedPaymentMethod;
    }

    // public function processPayment()
    // {
    //     $this->validate();

    //     // Validasi untuk cash
    //     // if ($this->payment_method === 'cash' && $this->amount_paid < $this->finalTotal) {
    //     //     Notification::make()
    //     //         ->title('Pembayaran Gagal')
    //     //         ->body('Jumlah bayar tidak boleh kurang dari total tagihan.')
    //     //         ->danger()
    //     //         ->send();
    //     //     return;
    //     // }
    //     // Validasi untuk cash payment
    //     if ($this->isCashPayment && $this->amount_paid < $this->finalTotal) {
    //         Notification::make()
    //             ->title('Pembayaran Gagal')
    //             ->body('Jumlah bayar tidak boleh kurang dari total tagihan untuk pembayaran tunai.')
    //             ->danger()
    //             ->send();
    //         return;
    //     }


    //     // Untuk non-cash payment, set amount_paid sama dengan final_total
    //     if (!$this->isCashPayment) {
    //         $this->amount_paid = $this->finalTotal;
    //     }

    //     $this->dispatch('paymentProcessed', 
    //         saleId: $this->saleId,
    //         paymentMethodId: $this->payment_method,
    //         paymentMethod: $this->payment_method,
    //         amountPaid: $this->amount_paid
    //     );

    //     $this->show = false;
    //     $this->resetExcept(['paymentMethods']);
    //     // $this->reset();
    // }

    public function processPayment()
    {
        $this->validate();

        // Validasi untuk cash payment
        if ($this->isCashPayment && $this->amount_paid < $this->finalTotal) {
            Notification::make()
                ->title('Pembayaran Gagal')
                ->body('Jumlah bayar tidak boleh kurang dari total tagihan untuk pembayaran tunai.')
                ->danger()
                ->send();
            return;
        }

        // Untuk non-cash payment, set amount_paid sama dengan final_total
        if (!$this->isCashPayment) {
            $this->amount_paid = $this->finalTotal;
        }

        // DEBUG: Cek nilai sebelum dikirim
        // dd([
        //     'saleId' => $this->saleId,
        //     'paymentMethodId' => $this->payment_method,
        //     'amountPaid' => $this->amount_paid
        // ]);

        $this->dispatch('paymentProcessed', 
            saleId: $this->saleId,
            paymentMethodId: $this->payment_method, // Ganti nama parameter menjadi paymentMethodId
            amountPaid: $this->amount_paid
        );

        $this->show = false;
        $this->resetExcept(['paymentMethods']);
    }

    public function closeModal()
    {
        $this->show = false;
        $this->resetExcept(['paymentMethods']);
        // $this->reset();
    }

    public function updatedPaymentMethod()
    {
        // Jika bukan cash, set amount_paid sama dengan final_total
        if (!$this->isCashPayment) {
            $this->amount_paid = $this->finalTotal;
        }
    }

    public function render()
    {
        return view('livewire.pos-payment-modal',[
            'isCashPayment' => $this->isCashPayment,
            'selectedPaymentMethod' => $this->selectedPaymentMethod,
        ]);
    }
}