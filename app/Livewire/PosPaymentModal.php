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
    public $showReceiptPreview = false;
    public $receiptContent = '';

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

    // public function processPayment()
    // {
    //     $this->validate();

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

    //     // DEBUG: Cek nilai sebelum dikirim
    //     // dd([
    //     //     'saleId' => $this->saleId,
    //     //     'paymentMethodId' => $this->payment_method,
    //     //     'amountPaid' => $this->amount_paid
    //     // ]);

    //     $this->dispatch('paymentProcessed', 
    //         saleId: $this->saleId,
    //         paymentMethodId: $this->payment_method, // Ganti nama parameter menjadi paymentMethodId
    //         amountPaid: $this->amount_paid
    //     );

    //     $this->show = false;
    //     $this->resetExcept(['paymentMethods']);
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

        logger('Process Payment Called', [
            'saleId' => $this->saleId,
            'paymentMethod' => $this->payment_method,
            'amountPaid' => $this->amount_paid
        ]);

        // Process payment
        $this->dispatch('paymentProcessed', 
            saleId: $this->saleId,
            paymentMethodId: $this->payment_method,
            amountPaid: $this->amount_paid
        );

        // Generate dan show receipt preview
        $this->generateReceiptPreview();

        $this->show = false;
        $this->showReceiptPreview = true;
    }

    protected function generateReceiptPreview()
    {
        $sale = Sale::with(['items.product', 'paymentMethod', 'user'])->find($this->saleId);
        
        if (!$sale) {
            return;
        }

        $content = "<div class='text-center'>";
        $content .= "<h1 class='font-bold text-lg uppercase'>STRUK PEMBAYARAN</h1>";
        $content .= "<p class='text-sm'>" . config('app.name') . "</p>";
        $content .= "<p class='text-xs'>" . $sale->created_at->format('d/m/Y H:i') . "</p>";
        $content .= "</div>";
        
        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";
        
        // Info Transaksi
        $content .= "<div class='space-y-1 text-sm'>";
        $content .= "<div class='flex justify-between'><span>No. Transaksi:</span><span class='font-semibold'>" . $sale->invoice_number . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Kasir:</span><span>" . ($sale->user->name ?? 'System') . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Customer:</span><span>" . ($sale->customer_name ?? 'Umum') . "</span></div>";
        $content .= "</div>";
        
        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";
        
        // Items
        $content .= "<div class='space-y-2'>";
        foreach ($sale->items as $item) {
            $content .= "<div class='flex justify-between items-start'>";
            $content .= "<div class='flex-1'>";
            $content .= "<div class='font-semibold'>" . ($item->product->name ?? 'Produk') . "</div>";
            $content .= "<div class='text-xs text-gray-600'>" . $item->quantity . " × Rp" . number_format($item->unit_price, 0, ',', '.') . "</div>";
            $content .= "</div>";
            $content .= "<div class='font-semibold'>Rp" . number_format($item->subtotal, 0, ',', '.') . "</div>";
            $content .= "</div>";
        }
        $content .= "</div>";
        
        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";
        
        // Summary
        $content .= "<div class='space-y-1 text-sm'>";
        $content .= "<div class='flex justify-between'><span>Subtotal:</span><span>Rp" . number_format($sale->subtotal, 0, ',', '.') . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Pajak (10%):</span><span>Rp" . number_format($sale->tax, 0, ',', '.') . "</span></div>";
        if ($sale->discount > 0) {
            $content .= "<div class='flex justify-between text-green-600'><span>Diskon:</span><span>- Rp" . number_format($sale->discount, 0, ',', '.') . "</span></div>";
        }
        $content .= "<div class='border-t border-gray-300 pt-1'>";
        $content .= "<div class='flex justify-between font-bold'><span>TOTAL:</span><span>Rp" . number_format($sale->final_total, 0, ',', '.') . "</span></div>";
        $content .= "</div>";
        $content .= "</div>";
        
        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";
        
        // Payment Info
        $content .= "<div class='space-y-1 text-sm'>";
        $content .= "<div class='flex justify-between'><span>Metode:</span><span class='font-semibold'>" . ($sale->paymentMethod->name ?? 'Cash') . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Bayar:</span><span>Rp" . number_format($sale->amount_paid, 0, ',', '.') . "</span></div>";
        if (($sale->paymentMethod->code ?? 'cash') === 'cash') {
            $change = $sale->amount_paid - $sale->final_total;
            $content .= "<div class='flex justify-between'><span>Kembali:</span><span class='font-semibold'>Rp" . number_format($change, 0, ',', '.') . "</span></div>";
        }
        $content .= "</div>";
        
        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";
        
        // Footer
        $content .= "<div class='text-center text-xs'>";
        $content .= "<p>Terima kasih atas kunjungan Anda</p>";
        $content .= "<p class='font-semibold'>*** SELAMAT MENIKMATI ***</p>";
        $content .= "</div>";

        $this->receiptContent = $content;
    }

    public function closeReceiptPreview()
    {
        $this->showReceiptPreview = false;
        $this->receiptContent = '';
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