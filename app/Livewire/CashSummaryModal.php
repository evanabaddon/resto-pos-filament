<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CashSession;
use App\Models\PaymentMethod;
use App\Models\Expense;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

class CashSummaryModal extends Component
{
    public $showModal = false;
    public $session;
    public $summary = [];
    public Collection $paymentMethods;
    public $manualCashOut = 0;
    public $actualCashOut = 0;
    public $cashDifference = 0;

    protected $listeners = [
        'openCashSummaryModal' => 'openModal',
        'refreshSummary' => 'refreshSummary'
    ];

    public function mount()
    {
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

        // Load session dengan semua data terkait
        $this->session = CashSession::with([
            'sales' => function ($query) {
                $query->where('status', 'completed');
            },
            'cashExpenses' => function ($query) {
                $query->where('status', 'approved');
            },
            'cashPurchases' => function ($query) {
                $query->where('status', 'received');
            }
        ])->find($sessionId);

        if ($this->session) {
            $this->calculateSummary();
            $this->showModal = true;
        }
    }

    public function refreshSummary()
    {
        if ($this->session) {
            $this->calculateSummary();
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['session', 'summary', 'manualCashOut', 'actualCashOut', 'cashDifference']);
    }

    private function calculateSummary()
    {
        $sales = $this->session->sales;
        $expenses = $this->session->cashExpenses; // Hanya expenses dari kasir
        $purchases = $this->session->cashPurchases; // Hanya pembelian dari kasir

        // Hitung penjualan berdasarkan payment method
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

        // Hitung total pengeluaran dari kasir
        $totalCashExpenses = $expenses->sum('amount');

        // Hitung total pembelian dari kasir
        $totalCashPurchases = $purchases->sum('total');

        // Cash sales khusus untuk perhitungan expected cash
        $cashSales = $paymentMethodSales['cash'] ?? 0;

        // Expected cash = kas awal + penjualan cash - pengeluaran cash - pembelian cash
        $expectedCash = $this->session->cash_in_hand + $cashSales - $totalCashExpenses - $totalCashPurchases;

        // Jika sudah ada cash_out yang diisi, hitung selisih
        $cashDifference = null;
        if ($this->session->cash_out !== null) {
            $cashDifference = $this->session->cash_out - $expectedCash;
        }

        $this->summary = [
            'cash_in_hand' => $this->session->cash_in_hand,
            'payment_method_sales' => $paymentMethodSales,
            'total_sales' => $totalSales,
            'cash_sales' => $cashSales,
            'total_cash_expenses' => $totalCashExpenses,
            'total_cash_purchases' => $totalCashPurchases,
            'expected_cash' => $expectedCash,
            'cash_out' => $this->session->cash_out,
            'cash_difference' => $cashDifference,
            'transaction_count' => $sales->count(),
            'expense_count' => $expenses->count(),
            'purchase_count' => $purchases->count(),
            'average_transaction' => $sales->count() > 0 ? $totalSales / $sales->count() : 0,
            'session_duration' => $this->session->opened_at->diffForHumans(now(), true),
        ];

        // Set nilai untuk input
        $this->actualCashOut = $this->session->cash_out ?? 0;
        $this->manualCashOut = $this->actualCashOut;
        $this->cashDifference = $cashDifference ?? 0;
    }

    public function updateCashOut()
    {
        $this->validate([
            'manualCashOut' => 'required|numeric|min:0',
        ]);

        try {
            // Update cash_out di database
            $this->session->update([
                'cash_out' => $this->manualCashOut
            ]);

            // Hitung ulang selisih
            $cashDifference = $this->manualCashOut - $this->summary['expected_cash'];

            $this->summary['cash_out'] = $this->manualCashOut;
            $this->summary['cash_difference'] = $cashDifference;
            $this->cashDifference = $cashDifference;
            $this->actualCashOut = $this->manualCashOut;

            // ✅ PERBAIKAN: Kirim string, bukan array
            $this->dispatch(
                'showNotification',
                'Kas akhir berhasil diperbarui'
            );

        } catch (\Exception $e) {
            // ✅ PERBAIKAN: Kirim string, bukan array
            $this->dispatch(
                'showNotification',
                'Gagal memperbarui kas akhir: ' . $e->getMessage()
            );
        }
    }

    public function closeCashSession()
    {
        // Validasi: cash_out harus diisi
        if ($this->actualCashOut === null || $this->actualCashOut === '') {
            $this->dispatch(
                'showNotification',
                'Harap masukkan jumlah kas akhir terlebih dahulu'
            );
            return;
        }

        try {
            // Update cash_out jika belum diisi
            if ($this->session->cash_out === null) {
                $this->session->update(['cash_out' => $this->actualCashOut]);
            }

            // Tutup sesi
            $this->session->update([
                'closed_at' => now(),
                'status' => 'closed'
            ]);

            // Clear session
            session()->forget('cash_session_id');

            $this->dispatch(
                'showNotification',
                'Sesi kas berhasil ditutup'
            );

            // Tutup modal dan refresh halaman
            $this->closeModal();

            // Dispatch event untuk refresh dashboard
            $this->dispatch('cashSessionClosed');

            // Redirect atau refresh
            redirect()->route('filament.admin.pages.dashboard');

            $this->dispatch(
                'show-notification',
                message: 'Laporan Shift berhasil dicetak',
                type: 'success'
            );

        } catch (\Exception $e) {
            $this->dispatch(
                'showNotification',
                'Gagal menutup sesi: ' . $e->getMessage()
            );
        }
    }

    public function getPaymentMethodName($code)
    {
        $method = $this->paymentMethods->get($code);
        return $method ? $method->name : ucfirst(str_replace('_', ' ', $code));
    }

    public function getPaymentMethodColorClass($color): string
    {
        return match ($color) {
            'green' => '#10B981',
            'purple' => '#8B5CF6',
            'orange' => '#F97316',
            'indigo' => '#6366F1',
            'pink' => '#EC4899',
            default => '#6B7280',
        };
    }

    public function formatCurrency($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public function render()
    {
        return view('livewire.cash-summary-modal');
    }
}