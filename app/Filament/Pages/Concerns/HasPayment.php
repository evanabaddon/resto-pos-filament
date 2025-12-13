<?php

namespace App\Filament\Pages\Concerns;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\OrderService;
use App\Services\OrderPrintService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

trait HasPayment
{
    // Handler untuk payment request
    public function handlePaymentRequested($saleId)
    {
        $this->openPaymentModal($saleId);
    }

    // Handler untuk payment processed
    public function handlePaymentProcessed($saleId, $paymentMethodId, $amountPaid)
    {
        try {
            Log::info('HasPayment: handlePaymentProcessed received', [
                'sale_id' => $saleId,
                'payment_method' => $paymentMethodId,
                'amount_paid' => $amountPaid
            ]);

            $sale = Sale::findOrFail($saleId);

            // Use OrderService to mark as paid
            app(OrderService::class)->markAsPaid($sale, $paymentMethodId, (float) $amountPaid);

            // Auto print receipt setelah pembayaran berhasil
            $this->printReceipt($saleId, $amountPaid);

            $this->dispatch('show-notification', message: 'Pembayaran berhasil diproses.', type: 'success');
            $this->showPaymentModal = false;
            $this->showLoadModal = false;

            $this->resetPos();

        } catch (\Exception $e) {
            $this->dispatch('show-notification', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openPaymentModal($saleId = null)
    {
        // Jika saleId tidak dikirim, gunakan current saleId
        $targetSaleId = $saleId ?? $this->saleId;

        if (!$targetSaleId) {
            $this->dispatch('showNotification', 'Simpan transaksi terlebih dahulu!', 'error');
            return;
        }

        // Dispatch event ke modal payment
        $this->dispatch('openPaymentModal', saleId: $targetSaleId);
    }

    public function processPayment()
    {
        $sale = Sale::findOrFail($this->saleId);

        $sale->update([
            'is_paid' => true,
            'payment_method' => $this->payment_method,
            'amount_paid' => $this->amount_paid,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->showPaymentModal = false;
        $this->showLoadModal = false;

        $this->dispatch('show-notification', message: 'Pembayaran berhasil diproses.', type: 'success');
        $this->resetPos();
    }

    /**
     * Handle mobile payment modal
     */
    public function openPaymentModalMobile()
    {
        if (!$this->saleId) {
            // Jika tidak ada saleId, coba simpan dulu
            if (!empty($this->items)) {
                $this->saveSale();

                // 🔹 Prevent continue if save failed (e.g. no customer name)
                if (!$this->saleId) {
                    return;
                }
            } else {
                $this->dispatch('showNotification', 'Keranjang kosong! Tambahkan produk terlebih dahulu.', 'error');
                return;
            }
        }

        $this->dispatch('openPaymentModal', saleId: $this->saleId);
    }

    /**
     * Save sale untuk mobile - simplified
     */
    public function mobileSaveSale()
    {
        if (empty($this->items)) {
            $this->dispatch('showNotification', 'Keranjang kosong! Tambahkan produk terlebih dahulu.', 'error');
            return;
        }

        if (empty(trim($this->customerName))) {
            $this->dispatch('show-notification', message: 'Nama pelanggan harus diisi!', type: 'error');
            return;
        }

        $this->saveSale();
    }

    public function saveSale()
    {
        // 🔹 Cek keranjang kosong
        if (empty($this->items)) {
            $this->dispatch('show-notification', message: 'Keranjang kosong!', type: 'error');
            return;
        }

        // 🔹 Cek nama pelanggan
        if (empty(trim($this->customerName))) {
            $this->dispatch('show-notification', message: 'Nama pelanggan harus diisi!', type: 'error');
            return;
        }

        try {
            $subtotal = $this->total ?? 0;
            $tax = $this->tax ?? 0;
            $discount = $this->discount ?? 0;
            $final = $this->finalTotal ?? ($subtotal + $tax - $discount);

            $isUpdate = !empty($this->saleId);
            $existingSale = $isUpdate ? Sale::find($this->saleId) : null;

            // Consistency check
            if ($isUpdate && !$existingSale) {
                $isUpdate = false;
            }

            $orderData = [
                'cash_session_id' => $this->cashSessionId ?? session('cash_session_id'),
                'user_id' => auth()->id(),
                'invoice_number' => $isUpdate ? $existingSale->invoice_number : $this->generateOrderNumber(),
                'customer_name' => $this->customerName ?? 'Umum',
                'order_type' => $this->orderType,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'final_total' => $final,
            ];

            // Use Service
            $orderService = app(OrderService::class);
            $sale = $orderService->processOrder($orderData, $this->items, $isUpdate, $existingSale);

            // Set saleId if new
            if (!$isUpdate) {
                $this->saleId = $sale->id;
            }

            // 🔹 TENTUKAN APAKAH INI UPDATE
            if ($isUpdate) {
                // 🔹 DAPATKAN ITEM BARU/TAMBAHAN
                $newItems = $this->getNewOrUpdatedItems();

                \Log::info('🔄 Update order detected', [
                    'sale_id' => $sale->id,
                    'new_items_count' => count($newItems)
                ]);

                // 🔹 PRINT HANYA ITEM BARU JIKA ADA
                if (!empty($newItems)) {
                    try {
                        $orderPrintService = new OrderPrintService();
                        $printResult = $orderPrintService->printNewItemsOnly($sale, $newItems);

                        $this->dispatch('show-notification', message: '✅ Item berhasil ditambah & tambahan order dicetak!', type: 'success');

                    } catch (\Exception $e) {
                        $this->dispatch('show-notification', message: '⚠️ Order tersimpan tapi gagal print tambahan: ' . $e->getMessage(), type: 'warning');
                    }
                } else {
                    $this->dispatch('show-notification', message: '✅ Order berhasil diupdate!', type: 'success');
                }
            } else {
                // 🔹 NEW ORDER - PRINT SEMUA
                try {
                    $orderPrintService = new OrderPrintService();
                    $printResult = $orderPrintService->printOrderByProductType($sale);
                    $this->dispatch('show-notification', message: '✅ Order baru berhasil dikirim ke divisi!', type: 'success');

                } catch (\Exception $e) {
                    $this->dispatch('show-notification', message: '⚠️ Order tersimpan tapi gagal print: ' . $e->getMessage(), type: 'warning');
                }
            }

            // 🔹 RESET previousItems
            $this->previousItems = [];

            // 🔹 TAMPILKAN NOTIFIKASI BERBEDA
            if ($isUpdate) {
                $this->dispatch('show-notification', message: 'Transaksi #' . $sale->invoice_number . ' berhasil diupdate!', type: 'success');
            } else {
                $this->dispatch('show-notification', message: 'Transaksi baru #' . $sale->invoice_number . ' berhasil disimpan!', type: 'success');
            }

            // 🔹 Refresh list di modal
            $this->dispatch('refreshSalesList');

            $this->resetPos();

        } catch (\Exception $e) {
            \Log::error('💥 Gagal menyimpan penjualan: ' . $e->getMessage());
            $this->dispatch('show-notification', message: 'Gagal menyimpan penjualan: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Dapatkan item baru atau yang quantity-nya berubah DENGAN NOTES
     */
    protected function getNewOrUpdatedItems(): array
    {
        $newItems = [];

        foreach ($this->items as $currentItem) {
            $foundInPrevious = false;

            // Cek apakah item ada di previous items
            foreach ($this->previousItems as $previousItem) {
                if ($previousItem['product_id'] == $currentItem['product_id']) {
                    $foundInPrevious = true;

                    // Jika quantity berubah atau notes berbeda
                    // Ensure notes is set to empty string if null
                    $currentNotes = $currentItem['notes'] ?? '';
                    $previousNotes = $previousItem['notes'] ?? '';

                    $quantityDiff = $currentItem['quantity'] - $previousItem['quantity'];
                    $notesChanged = $currentNotes !== $previousNotes;

                    if ($quantityDiff > 0 || $notesChanged) {
                        $newItems[] = [
                            'product_id' => $currentItem['product_id'],
                            'name' => $currentItem['name'],
                            'quantity' => $quantityDiff > 0 ? $quantityDiff : $currentItem['quantity'],
                            'price' => $currentItem['price'],
                            'notes' => $currentNotes,
                            'is_update' => true
                        ];
                    }
                    break;
                }
            }

            // Jika item benar-benar baru (tidak ada di previous)
            if (!$foundInPrevious) {
                $newItems[] = [
                    'product_id' => $currentItem['product_id'],
                    'name' => $currentItem['name'],
                    'quantity' => $currentItem['quantity'],
                    'price' => $currentItem['price'],
                    'notes' => $currentItem['notes'] ?? '',
                    'is_update' => false
                ];
            }
        }

        return $newItems;
    }

    // --- MERGE BILL LOGIC ---

    // Handler untuk merge confirmed
    public function handleMergeConfirmed($selectedSales, $mergeTarget)
    {
        $this->selectedSalesToMerge = $selectedSales;
        $this->mergeTargetSale = $mergeTarget;
        $this->processMergeBill();
    }

    // Handler untuk merge cancelled
    public function handleMergeCancelled()
    {
        $this->cancelMerge();
    }

    // Get merge sales data
    public function getMergeSalesProperty()
    {
        return Sale::where('status', 'draft')
            ->where('cash_session_id', $this->cashSessionId)
            ->where('id', '!=', $this->saleId) // Exclude current sale if needed
            ->latest()
            ->get();
    }

    // Method untuk menghitung merge totals
    public function getMergeTotalsProperty()
    {
        $count = count($this->selectedSalesToMerge);
        $total = 0;
        $items = 0;

        foreach ($this->selectedSalesToMerge as $saleId) {
            $sale = Sale::find($saleId);
            if ($sale) {
                $total += $sale->final_total;
                $items += $sale->items->sum('quantity');
            }
        }

        // Add target sale if unique
        if ($this->mergeTargetSale && !in_array($this->mergeTargetSale, $this->selectedSalesToMerge)) {
            $target = Sale::find($this->mergeTargetSale);
            if ($target) {
                $total += $target->final_total;
                $items += $target->items->sum('quantity');
                $count++;
            }
        }

        return [
            'count' => $count,
            'total' => $total,
            'items' => $items,
            'has_target' => !empty($this->mergeTargetSale)
        ];
    }

    // Method untuk mendapatkan info target sale
    public function getTargetSaleInfoProperty()
    {
        if (!$this->mergeTargetSale)
            return null;
        return Sale::find($this->mergeTargetSale);
    }

    // Method untuk mendapatkan sales yang tersedia untuk merge
    public function getAvailableMergeSalesProperty()
    {
        $query = Sale::query()
            ->where('status', 'draft')
            ->where('cash_session_id', $this->cashSessionId ?? session('cash_session_id'))
            ->orderBy('created_at', 'desc');

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('customer_name', 'like', '%' . $this->searchQuery . '%');
            });
        }

        return $query->get();
    }

    // Open merge bill modal - DENGAN DEBUG DETAIL
    public function openMergeModal()
    {
        // 1. Pastikan session ID ada
        $sessionId = $this->cashSessionId ?? session('cash_session_id');

        // 2. Query draft sales
        $this->availableSales = Sale::where('status', 'draft')
            ->where('cash_session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('Open Merge Modal', [
            'session_id' => $sessionId,
            'found_sales' => $this->availableSales->count()
        ]);

        if ($this->availableSales->isEmpty()) {
            $this->dispatch('showNotification', 'Tidak ada transaksi draft yang bisa digabung.', 'warning');
            return;
        }

        $this->showMergeModal = true;
        // Reset state
        $this->selectedSalesToMerge = [];
        $this->mergeTargetSale = null;
    }

    // Toggle select sale untuk merge
    public function toggleSelectSale($saleId)
    {
        if (in_array($saleId, $this->selectedSalesToMerge)) {
            $this->selectedSalesToMerge = array_diff($this->selectedSalesToMerge, [$saleId]);
            // If removed sale was target, reset target
            if ($this->mergeTargetSale == $saleId) {
                $this->mergeTargetSale = null;
            }
        } else {
            $this->selectedSalesToMerge[] = $saleId;
            // If first selection, auto set as target (optional UX)
            if (count($this->selectedSalesToMerge) == 1 && !$this->mergeTargetSale) {
                $this->mergeTargetSale = $saleId;
            }
        }
    }

    // Set merge target sale
    public function setMergeTarget($saleId)
    {
        if (in_array($saleId, $this->selectedSalesToMerge)) {
            $this->mergeTargetSale = $saleId;
        }
    }

    // Proses merge bill - DENGAN LOG DETAIL
    public function processMergeBill()
    {
        \Log::info('🔄 Memulai proses merge bill');

        if (empty($this->selectedSalesToMerge) || count($this->selectedSalesToMerge) < 2) {
            $this->dispatch('showNotification', 'Pilih minimal 2 transaksi untuk digabung!', 'error');
            return;
        }

        if (!$this->mergeTargetSale) {
            $this->dispatch('showNotification', 'Pilih transaksi tujuan!', 'error');
            return;
        }

        try {
            $orderService = app(OrderService::class);
            $targetSale = $orderService->mergeSales($this->mergeTargetSale, $this->selectedSalesToMerge);

            \Log::info('✅ Merge bill berhasil', ['target_sale_id' => $targetSale->id]);

            $this->dispatch('showNotification', 'Transaksi berhasil digabungkan ke #' . $targetSale->invoice_number, 'success');

            // Reset state
            $this->showMergeModal = false;
            $this->selectedSalesToMerge = [];
            $this->mergeTargetSale = null;
            $this->dispatch('refreshSalesList'); // Important for UI update

        } catch (\Exception $e) {
            \Log::error('💥 Gagal merge bill: ' . $e->getMessage());
            $this->dispatch('showNotification', 'Gagal menggabungkan transaksi: ' . $e->getMessage(), 'error');
        }
    }

    // Cancel merge process
    public function cancelMerge()
    {
        $this->showMergeModal = false;
        $this->selectedSalesToMerge = [];
        $this->mergeTargetSale = null;
    }

    // Safe get merge totals
    public function safeGetMergeTotals()
    {
        try {
            return $this->mergeTotals;
        } catch (\Exception $e) {
            return [
                'count' => 0,
                'total' => 0,
                'items' => 0,
                'has_target' => false
            ];
        }
    }

    // Safe get target sale info
    public function safeGetTargetSaleInfo()
    {
        try {
            return $this->targetSaleInfo;
        } catch (\Exception $e) {
            return null;
        }
    }
}
