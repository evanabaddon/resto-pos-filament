<?php

namespace App\Livewire;

use App\Models\Sale;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ReceiptPreview extends Component
{
    public $sale;
    public $show = false;

    protected $listeners = ['showReceiptPreview', 'printReceipt'];

    public function showReceiptPreview($saleId)
    {
        // Log - perbaiki syntax, tambahkan array context
        Log::info('showReceiptPreview called', ['saleId' => $saleId]);
        
        $this->sale = Sale::with(['items.product', 'paymentMethod', 'user'])->find($saleId);
        
        // Log hasil query
        Log::info('Sale data loaded', [
            'sale' => $this->sale ? $this->sale->id : 'null',
            'items_count' => $this->sale ? $this->sale->items->count() : 0
        ]);
        
        $this->show = true;
        
        // Log status show
        Log::info('Show status updated', ['show' => $this->show]);
    }

    public function closePreview()
    {
        Log::info('closePreview called');
        $this->show = false;
        $this->sale = null;
    }

    public function printReceipt($saleId = null)
    {
        Log::info('printReceipt called', ['saleId' => $saleId, 'current_sale_id' => $this->sale?->id]);
        
        $saleId = $saleId ?? $this->sale->id;
        $sale = Sale::with(['items.product', 'paymentMethod', 'user'])->find($saleId);
        
        $receiptContent = $this->generateReceiptContent($sale);
        
        $this->dispatch('printReceiptContent', content: $receiptContent, invoiceNumber: $sale->invoice_number);
        $this->closePreview();
    }

    // Ubah dari static method ke regular method
    protected function generateReceiptContent(Sale $sale): string
    {
        Log::info('generateReceiptContent called', ['sale_id' => $sale->id]);
        
        $content = "";
        
        // Header
        $content .= "<div class='text-center'>";
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
        $content .= "<div class='flex justify-between'><span>Status:</span><span class='font-semibold'>" . strtoupper($sale->status) . "</span></div>";
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
        $content .= "<div class='flex justify-between'><span>Metode Bayar:</span><span class='font-semibold'>" . ($sale->paymentMethod->name ?? 'Cash') . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Dibayar:</span><span>Rp" . number_format($sale->amount_paid, 0, ',', '.') . "</span></div>";
        
        if (($sale->paymentMethod->code ?? 'cash') === 'cash') {
            $change = $sale->amount_paid - $sale->final_total;
            if ($change > 0) {
                $content .= "<div class='flex justify-between'><span>Kembali:</span><span class='font-semibold'>Rp" . number_format($change, 0, ',', '.') . "</span></div>";
            }
        }
        
        $paymentStatus = $sale->is_paid ? 'LUNAS' : 'BELUM LUNAS';
        $statusColor = $sale->is_paid ? 'text-green-600' : 'text-red-600';
        $content .= "<div class='flex justify-between {$statusColor}'><span>Status Bayar:</span><span class='font-semibold'>{$paymentStatus}</span></div>";
        
        $content .= "</div>";
        
        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";
        
        // Footer
        $content .= "<div class='text-center text-xs'>";
        $content .= "<p>Terima kasih atas kunjungan Anda</p>";
        if (!$sale->is_paid) {
            $content .= "<p class='text-red-600 font-semibold'>*** MENUNGGU PEMBAYARAN ***</p>";
        } else {
            $content .= "<p class='font-semibold'>*** SELAMAT MENIKMATI ***</p>";
        }
        $content .= "</div>";
        
        Log::info('Receipt content generated', ['content_length' => strlen($content)]);
        
        return $content;
    }

    public function render()
    {
        Log::info('render called', ['show' => $this->show, 'sale' => $this->sale?->id]);
        
        return view('livewire.receipt-preview');
    }
}