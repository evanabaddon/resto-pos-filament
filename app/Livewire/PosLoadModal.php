<?php

namespace App\Livewire;

use App\Models\Sale;
use Livewire\Component;
use App\Models\SaleItem;

class PosLoadModal extends Component
{
    public $show = false;
    public $savedSales = [];
    public $showSplitBillModal = false;
    public $selectedSaleForSplit = null;
    public $splitType = 'equal'; // 'equal', 'item', 'percentage'
    public $splitCount = 2;
    public $splitAssignments = [];
    public $itemAssignments = [];
    public $percentageSplits = [];
    public $customerNames = [];

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
            ->withCount('items') // Ini akan menghasilkan 'items_count'
            ->with(['items', 'paymentMethod']) // Load relationships untuk menghindari N+1 query
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

    // Fitur Split Bill - Item Based
    public function openSplitBill($saleId)
    {
        $sale = Sale::find($saleId);
        
        if ($sale->status !== 'draft') {
            $this->dispatch('showNotification', 
                'Split bill hanya bisa dilakukan pada transaksi draft.', 
                'warning'
            );
            return;
        }

        $this->selectedSaleForSplit = $sale;
        $this->splitCount = 2;
        $this->splitType = 'item';
        $this->initializeSplitAssignments();
        $this->showSplitBillModal = true;
    }

    public function initializeSplitAssignments()
    {
        if (!$this->selectedSaleForSplit) return;

        // Initialize customer names
        for ($i = 0; $i < $this->splitCount; $i++) {
            $this->customerNames[$i] = 'Orang ' . ($i + 1);
        }

        // Initialize item assignments
        $this->itemAssignments = [];
        foreach ($this->selectedSaleForSplit->items as $item) {
            $this->itemAssignments[$item->id] = array_fill(0, $this->splitCount, 0);
        }

        // Initialize percentage splits (for percentage type)
        $this->percentageSplits = array_fill(0, $this->splitCount, 100 / $this->splitCount);
    }

    public function updatedSplitCount()
    {
        $this->splitCount = max(2, min(8, $this->splitCount));
        $this->initializeSplitAssignments();
    }

    public function updatedSplitType()
    {
        $this->initializeSplitAssignments();
    }

    // Method untuk assign item ke customer tertentu
    public function assignItemToCustomer($itemId, $customerIndex, $quantity)
    {
        // Reset semua assignment untuk item ini terlebih dahulu
        $this->itemAssignments[$itemId] = array_fill(0, $this->splitCount, 0);
        
        // Assign quantity ke customer yang dipilih
        $this->itemAssignments[$itemId][$customerIndex] = $quantity;
        
        $this->calculateSplitTotals();
    }

    // Method untuk assign sebagian item (misal: pizza dibagi 2 orang)
    public function assignPartialItem($itemId, $customerIndex, $partialQuantity)
    {
        $maxQuantity = $this->selectedSaleForSplit->items
            ->find($itemId)->quantity;
        
        // Cek total assignment tidak melebihi quantity tersedia
        $currentTotal = array_sum($this->itemAssignments[$itemId]);
        $available = $maxQuantity - $currentTotal + $this->itemAssignments[$itemId][$customerIndex];
        
        if ($partialQuantity <= $available) {
            $this->itemAssignments[$itemId][$customerIndex] = $partialQuantity;
        }
        
        $this->calculateSplitTotals();
    }

    // Hitung total untuk setiap split
    public function calculateSplitTotals()
    {
        $this->splitAssignments = array_fill(0, $this->splitCount, [
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'items' => []
        ]);

        foreach ($this->selectedSaleForSplit->items as $item) {
            $itemPrice = $item->unit_price;
            $itemSubtotal = $item->subtotal;
            
            for ($i = 0; $i < $this->splitCount; $i++) {
                $assignedQty = $this->itemAssignments[$item->id][$i] ?? 0;
                
                if ($assignedQty > 0) {
                    $assignedSubtotal = ($itemSubtotal / $item->quantity) * $assignedQty;
                    $assignedTax = $assignedSubtotal * 0.10; // 10% tax
                    $assignedTotal = $assignedSubtotal + $assignedTax;
                    
                    $this->splitAssignments[$i]['subtotal'] += $assignedSubtotal;
                    $this->splitAssignments[$i]['tax'] += $assignedTax;
                    $this->splitAssignments[$i]['total'] += $assignedTotal;
                    
                    $this->splitAssignments[$i]['items'][] = [
                        'name' => $item->product->name,
                        'quantity' => $assignedQty,
                        'price' => $itemPrice,
                        'subtotal' => $assignedSubtotal
                    ];
                }
            }
        }
    }

    // Auto-assign items equally - Updated
    public function autoAssignEqual()
    {
        foreach ($this->selectedSaleForSplit->items as $item) {
            $quantity = floatval($item->quantity);
            $splitCount = $this->splitCount;
            
            // Untuk quantity float, kita perlu approach yang berbeda
            if (is_float($quantity) && floor($quantity) != $quantity) {
                // Handle fractional quantities (misal: 2.5)
                $integerPart = floor($quantity);
                $fractionalPart = $quantity - $integerPart;
                
                // Assign integer parts
                $perPersonInteger = floor($integerPart / $splitCount);
                $remainderInteger = $integerPart - ($perPersonInteger * $splitCount);
                
                // Assign fractional part ke orang pertama
                $fractionalAssignments = array_fill(0, $splitCount, $perPersonInteger);
                for ($i = 0; $i < $remainderInteger; $i++) {
                    $fractionalAssignments[$i] += 1;
                }
                
                // Tambahkan fractional part ke orang pertama
                $fractionalAssignments[0] += $fractionalPart;
                
                for ($i = 0; $i < $splitCount; $i++) {
                    $this->itemAssignments[$item->id][$i] = $fractionalAssignments[$i];
                }
            } else {
                // Integer quantity - logic original
                $quantity = intval($quantity);
                $perPerson = floor($quantity / $splitCount);
                $remainder = $quantity % $splitCount;
                
                for ($i = 0; $i < $splitCount; $i++) {
                    $assignedQty = $perPerson + ($i < $remainder ? 1 : 0);
                    $this->itemAssignments[$item->id][$i] = $assignedQty;
                }
            }
        }
        $this->calculateSplitTotals();
    }

    // Clear all assignments for an item
    public function clearItemAssignment($itemId)
    {
        $this->itemAssignments[$itemId] = array_fill(0, $this->splitCount, 0);
        $this->calculateSplitTotals();
    }

    // comfirm split bill
    public function confirmSplitBill()
    {
        if (!$this->selectedSaleForSplit) return;

        // Validasi: semua item harus ter-assign
        $allItemsAssigned = true;
        $validationErrors = [];

        foreach ($this->selectedSaleForSplit->items as $item) {
            $totalAssigned = array_sum($this->itemAssignments[$item->id]);
            $itemQuantity = floatval($item->quantity);
            $totalAssignedFloat = floatval($totalAssigned);
            
            if (abs($totalAssignedFloat - $itemQuantity) > 0.001) {
                $allItemsAssigned = false;
                $validationErrors[] = "Item {$item->product->name}: {$totalAssigned}/{$item->quantity} ter-assign";
            }
        }

        if (!$allItemsAssigned) {
            $this->dispatch('showNotification', 
                'Beberapa item belum ter-assign sepenuhnya: ' . implode(', ', $validationErrors), 
                'error'
            );
            return;
        }

        try {
            // Buat transaksi baru untuk setiap split
            $originalSale = $this->selectedSaleForSplit;
            $splitSales = [];

            foreach ($this->splitAssignments as $index => $split) {
                if ($split['total'] > 0) {
                    // Generate unique invoice number untuk setiap split
                    $invoiceNumber = $this->generateUniqueSplitInvoiceNumber($originalSale->id, $index + 1);
                    
                    $newSale = new Sale();
                    $newSale->cash_session_id = $originalSale->cash_session_id;
                    $newSale->user_id = $originalSale->user_id;
                    $newSale->invoice_number = $invoiceNumber; // Gunakan invoice number yang unique
                    $newSale->customer_name = $this->customerNames[$index] ?? 'Customer ' . ($index + 1);
                    $newSale->order_type = $originalSale->order_type;
                    $newSale->subtotal = $split['subtotal'];
                    $newSale->tax = $split['tax'];
                    $newSale->discount = 0;
                    $newSale->final_total = $split['total'];
                    $newSale->total = $split['total'];
                    $newSale->payment_method = '';
                    $newSale->payment_method_id = null;
                    $newSale->status = 'draft';
                    $newSale->note = $originalSale->note;
                    $newSale->split_from = $originalSale->id;
                    $newSale->split_number = $index + 1;
                    $newSale->save();

                    // Create sale items untuk split ini
                    foreach ($split['items'] as $itemData) {
                        $productId = $this->findProductIdByName($itemData['name']);
                        if ($productId) {
                            SaleItem::create([
                                'sale_id' => $newSale->id,
                                'product_id' => $productId,
                                'quantity' => $itemData['quantity'],
                                'unit_price' => $itemData['price'],
                                'subtotal' => $itemData['subtotal'],
                            ]);
                        }
                    }

                    $splitSales[] = $newSale;
                }
            }

            // Update original sale status menjadi split
            $originalSale->update([
                'status' => 'split',
                'split_into' => count($splitSales)
            ]);

            $this->showSplitBillModal = false;
            $this->selectedSaleForSplit = null;
            $this->splitAssignments = [];
            $this->itemAssignments = [];
            $this->customerNames = [];
            
            $this->dispatch('showNotification', 
                'Split bill berhasil! ' . count($splitSales) . ' transaksi baru telah dibuat.', 
                'success'
            );

            // Refresh modal
            $this->openLoadModal();

        } catch (\Exception $e) {
            logger('Split Bill Error: ' . $e->getMessage());
            $this->dispatch('showNotification', 
                'Gagal melakukan split bill: ' . $e->getMessage(), 
                'error'
            );
        }
    }

    // Tambahkan method debug di PosLoadModal.php
    public function debugAssignments()
    {
        if (!$this->selectedSaleForSplit) return;
        
        $debugInfo = [];
        foreach ($this->selectedSaleForSplit->items as $item) {
            $totalAssigned = array_sum($this->itemAssignments[$item->id]);
            $debugInfo[] = [
                'item' => $item->product->name,
                'quantity' => $item->quantity,
                'type_quantity' => gettype($item->quantity),
                'total_assigned' => $totalAssigned,
                'type_assigned' => gettype($totalAssigned),
                'assignments' => $this->itemAssignments[$item->id],
                'is_equal' => $totalAssigned == $item->quantity
            ];
        }
        
        logger('Split Bill Debug:', $debugInfo);
        
        // Panggil method ini sebelum validasi di confirmSplitBill
        $this->debugAssignments();
    }

    protected function findProductIdByName($productName)
    {
        // Cari product ID berdasarkan nama
        // Dalam implementasi real, Anda mungkin perlu relationship yang lebih baik
        foreach ($this->selectedSaleForSplit->items as $item) {
            if ($item->product->name === $productName) {
                return $item->product_id;
            }
        }
        return null;
    }

    protected function generateUniqueSplitInvoiceNumber($originalSaleId, $splitNumber)
    {
        $date = now()->format('Ymd');
        $random = strtoupper(\Illuminate\Support\Str::random(4));
        
        do {
            $invoiceNumber = "#{$date}-{$random}-SPLIT{$splitNumber}";
            $exists = Sale::where('invoice_number', $invoiceNumber)->exists();
        } while ($exists);
        
        return $invoiceNumber;
    }

    public function closeSplitBillModal()
    {
        $this->showSplitBillModal = false;
        $this->selectedSaleForSplit = null;
        $this->splitAssignments = [];
        $this->itemAssignments = [];
        $this->customerNames = [];
    }

    public function incrementSplitCount()
    {
        $this->splitCount = min(8, $this->splitCount + 1);
        $this->initializeSplitAssignments();
    }

    public function decrementSplitCount()
    {
        $this->splitCount = max(2, $this->splitCount - 1);
        $this->initializeSplitAssignments();
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