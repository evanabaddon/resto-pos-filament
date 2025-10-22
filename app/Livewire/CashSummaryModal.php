<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CashSession;
use App\Models\PaymentMethod;
use Illuminate\Support\Collection;

class CashSummaryModal extends Component
{
    public $showModal = false;
    public $session;
    public $summary = [];
    public Collection $paymentMethods;

    protected $listeners = ['openCashSummaryModal' => 'openModal'];

    public function mount()
    {
        // Load semua payment method untuk mapping sebagai Collection
        $this->paymentMethods = PaymentMethod::active()->get()->keyBy('code');
    }

    public function openModal()
    {
        $sessionId = session('cash_session_id');
        
        if (!$sessionId) {
            $this->dispatch('showNotification', [
                'message' => 'Tidak ada sesi kas yang aktif',
                'type' => 'warning'
            ]);
            return;
        }

        $this->session = CashSession::with(['sales' => function($query) {
            $query->where('status', 'completed');
        }])->find($sessionId);

        if ($this->session) {
            $this->calculateSummary();
            $this->showModal = true;
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['session', 'summary']);
    }

    private function calculateSummary()
    {
        $sales = $this->session->sales;

        // Hitung berdasarkan payment method ID
        $paymentMethodSales = [];
        $totalSales = 0;
        
        foreach ($sales as $sale) {
            $paymentMethodCode = $sale->paymentMethod->code ?? 'unknown';
            $amount = $sale->final_total;
            
            if (!isset($paymentMethodSales[$paymentMethodCode])) {
                $paymentMethodSales[$paymentMethodCode] = 0;
            }
            
            $paymentMethodSales[$paymentMethodCode] += $amount;
            $totalSales += $amount;
        }

        // Hitung cash sales khusus (cash_in_hand + penjualan cash)
        $cashSales = $paymentMethodSales['cash'] ?? 0;
        $expectedCash = $this->session->cash_in_hand + $cashSales;

        $this->summary = [
            'cash_in_hand' => $this->session->cash_in_hand,
            'payment_method_sales' => $paymentMethodSales,
            'total_sales' => $totalSales,
            'expected_cash' => $expectedCash,
            'transaction_count' => $sales->count(),
            'average_transaction' => $sales->count() > 0 ? $totalSales / $sales->count() : 0,
        ];
    }

    // Helper untuk mendapatkan nama payment method yang user-friendly
    public function getPaymentMethodName($code)
    {
        $method = $this->paymentMethods->get($code);
        return $method ? $method->name : ucfirst(str_replace('_', ' ', $code));
    }

    // Helper untuk mendapatkan warna berdasarkan payment method
    public function getPaymentMethodColor($code)
    {
        return match($code) {
            'cash' => 'green',
            'transfer' => 'purple',
            'qris' => 'orange',
            'credit_card', 'debit_card' => 'indigo',
            'ewallet' => 'pink',
            default => 'gray',
        };
    }

    // 🔥 PERBAIKAN: Buat method formatCurrency yang bisa diakses di view
    public function formatCurrency($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public function render()
    {
        return view('livewire.cash-summary-modal');
    }
}
