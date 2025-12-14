<?php

namespace App\Livewire;

use App\Models\Sale;
use Livewire\Component;
use App\Models\PaymentMethod;
use App\Services\ReceiptPrintService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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
    public $isPrinting = false;
    public $currentSaleIdForPrint = null;

    protected $rules = [
        'amount_paid' => 'required|numeric|min:0',
        'payment_method' => 'required|string',
    ];

    protected $listeners = [
        'openPaymentModal',
        'openReceiptModal',
        'printCompleted' => 'handlePrintCompleted',
        'printFailed' => 'handlePrintFailed',
    ];

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
        $this->currentSaleIdForPrint = $sale->id;
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
        return max(0, (float) ($this->amount_paid ?? 0) - (float) ($this->finalTotal ?? 0));
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

        // Simpan saleId untuk print ulang
        $saleIdToPrint = $this->saleId;
        $this->currentSaleIdForPrint = $this->saleId;

        logger('Process Payment - Sale ID:', ['saleId' => $saleIdToPrint]);

        if (!$saleIdToPrint) {
            Notification::make()
                ->title('Error')
                ->body('Sale ID tidak valid.')
                ->danger()
                ->send();
            return;
        }

        // Process payment
        $amountPaid = (float) $this->amount_paid;

        Log::info('PosPaymentModal: Dispatching paymentProcessed', [
            'sale_id' => $saleIdToPrint,
            'payment_method' => $this->payment_method,
            'amount_paid' => $amountPaid,
            'final_total' => $this->finalTotal
        ]);

        $this->dispatch(
            'paymentProcessed',
            saleId: $saleIdToPrint,
            paymentMethodId: $this->payment_method,
            amountPaid: $amountPaid
        );

        // Generate dan show receipt preview
        // Pass current payment info explicitly because DB update might not have happened yet (handled by parent)
        $selectedMethodName = $this->selectedPaymentMethod['name'] ?? 'Cash';
        $this->generateReceiptPreview(null, $amountPaid, $selectedMethodName);


        $this->show = false;
        $this->showReceiptPreview = true;

        // Auto print setelah pembayaran berhasil
        // Auto print setelah pembayaran berhasil - kirim event ke Pos.php
        // $this->dispatch('printReceipt', saleId: $saleIdToPrint); // REMOVED to fix double print
        // $this->printReceiptDirect();
    }

    protected function generateReceiptPreview(Sale $sale = null, $overrideAmount = null, $overrideMethod = null)
    {
        // 1. Resolve Sale
        if (!$sale) {
            $sale = Sale::with(['items.product', 'paymentMethod', 'user'])->find($this->saleId);
        }

        if (!$sale) {
            return;
        }

        // 2. Apply Overrides (Clone to avoid side effects on actual model instance if needed, 
        //    though here we want to display the modified state)
        //    We use clone to be safe if $sale was passed in.
        $previewSale = $sale;

        if ($overrideAmount !== null) {
            $previewSale->amount_paid = $overrideAmount;
        }

        // 3. Handle Payment Method Override
        // The View uses $sale->paymentMethod->name and ->code.
        // If we have an overrideMethod (string name), we need to fake the relation.
        if ($overrideMethod !== null) {
            // Create a temporary object for the relation
            $fakePaymentMethod = new \stdClass();
            $fakePaymentMethod->name = $overrideMethod;
            $fakePaymentMethod->code = stripos($overrideMethod, 'cash') !== false ? 'cash' : 'other';
            
            // We can't easily setRelation with stdClass on a real Eloquent model 
            // without it expecting a Model instance usually.
            // But we can try setting the attribute if the view checks that.
            // Actually, best is to try to load the real payment method if we have ID.
            
            if ($this->payment_method) {
                // If we have the ID, fetch real model
                $pm = PaymentMethod::find($this->payment_method);
                if ($pm) {
                    $previewSale->setRelation('paymentMethod', $pm);
                } else {
                     // Fallback
                     $previewSale->setRelation('paymentMethod', new PaymentMethod(['name' => $overrideMethod, 'code' => 'other']));
                }
            } else {
                 // Fallback if no ID (shouldn't happen in processPayment)
                 // Or if just name passed.
                 $previewSale->setRelation('paymentMethod', new PaymentMethod(['name' => $overrideMethod, 'code' => 'other']));
            }
        }

        // 4. Render using Standard Blade View
        $settings = app(\App\Settings\GeneralSettings::class);
        
        $this->receiptContent = view('filament.components.receipt-preview-content', [
            'sale' => $previewSale,
            'settings' => $settings
        ])->render();
    }

    // Method untuk manual print dari tombol di modal preview
    public function manualPrintReceipt()
    {
        // Gunakan currentSaleIdForPrint jika available, fallback ke saleId
        $saleIdToPrint = $this->currentSaleIdForPrint ?? $this->saleId;

        logger('Manual Print Receipt - Sale ID:', [
            'currentSaleIdForPrint' => $this->currentSaleIdForPrint,
            'saleId' => $this->saleId,
            'saleIdToPrint' => $saleIdToPrint
        ]);

        if (!$saleIdToPrint) {
            Notification::make()
                ->title('Error')
                ->body('Tidak ada transaksi yang dipilih untuk dicetak.')
                ->danger()
                ->send();
            return;
        }

        $this->isPrinting = true;

        // Kirim event print ke Pos.php dengan saleId yang valid
        $this->dispatch('printReceipt', saleId: $saleIdToPrint);
    }

    // Handler ketika print selesai
    public function handlePrintCompleted()
    {
        logger('Print completed received in PosPaymentModal');
        $this->isPrinting = false;

        Notification::make()
            ->title('Print Selesai')
            ->body('Struk berhasil dicetak.')
            ->success()
            ->send();
    }

    // Handler ketika print gagal
    public function handlePrintFailed()
    {
        logger('Print failed received in PosPaymentModal');
        $this->isPrinting = false;

        Notification::make()
            ->title('Print Gagal')
            ->body('Gagal mencetak struk. Periksa koneksi printer.')
            ->danger()
            ->send();
    }

    // Method untuk print langsung ke printer thermal
    public function printReceiptDirect()
    {
        try {
            $this->isPrinting = true;

            $sale = Sale::with(['items.product', 'user', 'paymentMethod'])->findOrFail($this->saleId);

            $printService = new ReceiptPrintService($sale);
            $printService->printReceipt();

            Notification::make()
                ->title('Struk Berhasil Dicetak')
                ->body('Struk telah dikirim ke printer thermal.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            logger('Print receipt failed: ' . $e->getMessage());

            Notification::make()
                ->title('Gagal Mencetak Struk')
                ->body('Printer thermal tidak tersedia. Silakan cetak manual atau cek koneksi printer.')
                ->warning()
                ->send();
        } finally {
            $this->isPrinting = false;
        }
    }

    public function openReceiptModal($saleId)
    {
        $sale = Sale::with(['items.product', 'paymentMethod', 'user'])->findOrFail($saleId);

        // Set saleId untuk print ulang
        $this->currentSaleIdForPrint = $saleId;
        $this->saleId = $saleId;

        // Generate receipt content
        $this->generateReceiptPreview($sale);

        // Show receipt preview modal
        $this->showReceiptPreview = true;
    }

    public function closeReceiptPreview()
    {
        $this->showReceiptPreview = false;
        $this->receiptContent = '';
        // Jangan reset currentSaleIdForPrint agar bisa print ulang
        $this->reset(['saleId', 'finalTotal', 'amount_paid', 'payment_method', 'saleItems', 'subtotal', 'tax', 'discount', 'customerName', 'invoiceNumber', 'isPrinting']);
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
        return view('livewire.pos-payment-modal', [
            'isCashPayment' => $this->isCashPayment,
            'selectedPaymentMethod' => $this->selectedPaymentMethod,
        ]);
    }


}