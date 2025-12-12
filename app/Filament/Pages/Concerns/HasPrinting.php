<?php

namespace App\Filament\Pages\Concerns;

use App\Models\Sale;
use App\Services\ReceiptPrintService;
use Illuminate\Support\Facades\Log;

trait HasPrinting
{
    // public $isPrinting = false; // Already defined in Pos.php or we can move it here if we want exclusive control

    public function printReceipt($saleId)
    {
        // 🔹 CEK APAKAH SEDANG PRINT
        if ($this->isPrinting) {
            Log::warning('Print receipt skipped - already printing', ['saleId' => $saleId]);
            return;
        }

        try {
            $this->isPrinting = true;

            if (!$saleId) {
                $this->dispatch('show-notification', message: 'Sale ID tidak valid untuk print.', type: 'error');
                $this->isPrinting = false;
                return;
            }

            logger('Print Receipt - Searching Sale:', ['saleId' => $saleId]);

            $sale = Sale::with(['items.product', 'user', 'paymentMethod'])->find($saleId);

            if (!$sale) {
                logger('Sale not found:', ['saleId' => $saleId]);
                $this->dispatch('show-notification', message: 'Transaksi tidak ditemukan untuk dicetak.', type: 'error');
                $this->isPrinting = false;
                return;
            }

            logger('Sale found, proceeding to print:', ['invoice' => $sale->invoice_number]);

            // ✅ GUNAKAN RECEIPT PRINT SERVICE YANG DIPERBAIKI
            $printService = new ReceiptPrintService($sale);
            $printService->printReceipt();

            $this->dispatch('show-notification', message: '✅ Struk berhasil dicetak!', type: 'success');
            $this->dispatch('printCompleted');

        } catch (\Exception $e) {
            Log::error('❌ Print receipt failed: ' . $e->getMessage());
            $this->dispatch('show-notification', message: '❌ Gagal mencetak struk: ' . $e->getMessage(), type: 'error');
            $this->dispatch('printFailed');
        } finally {
            $this->isPrinting = false;
        }
    }

    // 🔹 TAMBAHKAN METHOD UNTUK DEBUG PRINTER
    public function debugPrinter()
    {
        try {
            $printService = new ReceiptPrintService();

            // 1. Get available printers
            $printers = $printService->getAvailablePrinters();

            // 2. Test printer connection
            $testResult = $printService->testPrinter();

            $message = "Printers tersedia: " . implode(', ', $printers) . "\n";
            $message .= "Test result: " . ($testResult['success'] ? '✅ BERHASIL' : '❌ GAGAL: ' . $testResult['error']);

            $this->dispatch('show-notification', message: $message, type: $testResult['success'] ? 'success' : 'error');

        } catch (\Exception $e) {
            $this->dispatch('show-notification', message: '❌ Debug error: ' . $e->getMessage(), type: 'error');
        }
    }

    // Handler untuk print receipt
    public function handlePrintReceipt($saleId)
    {
        logger('Handle Print Receipt - Sale ID:', ['saleId' => $saleId]);
        $this->printReceipt($saleId);
    }

    // Handler untuk print completed
    public function handlePrintCompleted()
    {
        // Tidak perlu melakukan apa-apa di sini, ini hanya untuk menerima event
        $this->isPrinting = false;
        logger('Print process completed');
    }

    public function manualPrintReceipt()
    {
        if ($this->saleId) {
            $this->printReceipt($this->saleId);
        } else {
             $this->dispatch('show-notification', message: 'Tidak ada transaksi aktif untuk dicetak.', type: 'error');
        }
    }
}
