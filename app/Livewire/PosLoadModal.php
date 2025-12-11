<?php

namespace App\Livewire;

use App\Models\Sale;
use Livewire\Component;
use App\Models\SaleItem;

class PosLoadModal extends Component
{
    use \Livewire\WithPagination;

    public $show = false;
    // public $savedSales = []; // Replaced by pagination in render
    public $search = '';
    public $activeTab = 'draft'; // 'draft', 'paid', 'completed', 'split', 'all'
    
    public $showSplitBillModal = false;
    public $selectedSaleForSplit = null;
    public $splitType = 'equal'; // 'equal', 'item', 'percentage'
    public $splitCount = 2;
    public $splitAssignments = [];
    public $itemAssignments = [];
    public $percentageSplits = [];
    public $customerNames = [];

    protected $listeners = ['openLoadModal', 'refreshSalesList' => '$refresh'];

    public function openLoadModal()
    {
        // Reset state when opening
        $this->search = '';
        $this->activeTab = 'draft'; // Default to draft for quick access to pending orders
        $this->show = true;
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedActiveTab()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
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

    public function deleteSale($saleId)
    {
        try {
            $sale = Sale::findOrFail($saleId);

            if ($sale->status !== 'draft') {
                $this->dispatch('showNotification', 'Hanya transaksi Draft yang bisa dihapus.', 'error');
                return;
            }

            // Gunakan Service untuk hapus & restore stock
            app(\App\Services\OrderService::class)->deleteSale($saleId);

            $this->dispatch('showNotification', 'Transaksi berhasil dihapus dan stok dikembalikan.', 'success');
            
            // Refresh list (otomatis via render karena Livewire)
        } catch (\Exception $e) {
            \Log::error('Gagal menghapus transaksi: ' . $e->getMessage());
            $this->dispatch('showNotification', 'Gagal menghapus transaksi: ' . $e->getMessage(), 'error');
        }
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
                        'product_id' => $item->product_id,
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
            // Prepare data for service
            $splits = [];
            foreach ($this->splitAssignments as $index => $split) {
                if ($split['total'] > 0) {
                    $split['customer_name'] = $this->customerNames[$index] ?? 'Customer ' . ($index + 1);
                    $splits[] = $split;
                }
            }

            // Call Service
            $orderService = app(\App\Services\OrderService::class);
            $newSales = $orderService->splitSale($this->selectedSaleForSplit->id, $splits);

            $this->showSplitBillModal = false;
            $this->selectedSaleForSplit = null;
            $this->splitAssignments = [];
            $this->itemAssignments = [];
            $this->customerNames = [];
            
            $this->dispatch('showNotification', 
                'Split bill berhasil! ' . count($newSales) . ' transaksi baru telah dibuat.', 
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
        $sales = collect();
        $cashSessionId = session('cash_session_id');

        if ($this->show && $cashSessionId) {
            $query = Sale::where('cash_session_id', $cashSessionId)
                ->withCount('items')
                ->with(['items', 'paymentMethod']);

            // Apply Status Filter
            if ($this->activeTab !== 'all') {
                $query->where('status', $this->activeTab);
            }

            // Apply Search
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('invoice_number', 'like', '%' . $this->search . '%')
                      ->orWhere('customer_name', 'like', '%' . $this->search . '%');
                });
            }

            $sales = $query->latest()->paginate(9); // Grid 3x3
        }

        return view('livewire.pos-load-modal', [
            'sales' => $sales
        ]);
    }
}
