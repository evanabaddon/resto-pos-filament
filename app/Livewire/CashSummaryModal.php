<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CashSession;

class CashSummaryModal extends Component
{
    public $showModal = false;
    public $session;
    public $summary = [];

    protected $listeners = ['openCashSummaryModal' => 'openModal'];

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

        // Hitung berdasarkan payment method
        $cashSales = $sales->where('payment_method', 'cash')->sum('final_total');
        $transferSales = $sales->where('payment_method', 'transfer')->sum('final_total');
        $qrisSales = $sales->where('payment_method', 'qris')->sum('final_total');
        $cardSales = $sales->whereIn('payment_method', ['debit_card', 'credit_card'])->sum('final_total');

        $totalSales = $sales->sum('final_total');
        $expectedCash = $this->session->cash_in_hand + $cashSales;

        $this->summary = [
            'cash_in_hand' => $this->session->cash_in_hand,
            'cash_sales' => $cashSales,
            'transfer_sales' => $transferSales,
            'qris_sales' => $qrisSales,
            'card_sales' => $cardSales,
            'other_sales' => $totalSales - ($cashSales + $transferSales + $qrisSales + $cardSales),
            'total_sales' => $totalSales,
            'expected_cash' => $expectedCash,
            'transaction_count' => $sales->count(),
            'average_transaction' => $sales->count() > 0 ? $totalSales / $sales->count() : 0,
        ];

        // dd('Summary:', $this->summary);
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
