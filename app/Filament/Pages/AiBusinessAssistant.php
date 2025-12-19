<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\DeepSeekService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use BackedEnum;
use UnitEnum;

class AiBusinessAssistant extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Tanya Bos (AI)';
    protected static ?string $title = 'AI Business Assistant';
    protected static string|UnitEnum|null $navigationGroup = 'AI Intelligence';

    protected string $view = 'filament.pages.ai-business-assistant';

    public $messages = [];
    public $userMessage = '';
    public $isTyping = false;

    public function mount()
    {
        $this->messages[] = [
            'role' => 'assistant',
            'content' => 'Halo Bos! Saya adalah asisten pintar Anda. Saya memiliki akses ke data penjualan, stok, dan performa restoran Anda. Ada yang bisa saya bantu analisis hari ini?'
        ];
    }

    public function sendMessage()
    {
        if (empty(trim($this->userMessage))) return;

        $userPrompt = $this->userMessage;
        $this->messages[] = ['role' => 'user', 'content' => $userPrompt];
        $this->userMessage = '';
        $this->isTyping = true;

        // Dispatch process event to handle AI in background if needed or just process here
        $this->processAiResponse($userPrompt);
    }

    protected function processAiResponse($prompt)
    {
        try {
            $service = new DeepSeekService();
            $context = $this->getBusinessContext();

            $response = $service->analyzeBusiness($prompt, $context);

            $content = $response['choices'][0]['message']['content'] ?? 'Maaf Bos, saya sedang mengalami gangguan koneksi ke otak pusat.';

            $this->messages[] = ['role' => 'assistant', 'content' => $content];
        } catch (\Exception $e) {
            $this->messages[] = ['role' => 'assistant', 'content' => 'Maaf Bos, ada error: ' . $e->getMessage()];
        } finally {
            $this->isTyping = false;
        }
    }

    protected function getBusinessContext(): string
    {
        $days = 30;
        $dateLimit = now()->subDays($days);

        // 1. Ringkasan Penjualan (Filter Tanggal)
        $salesSummary = Sale::where('status', 'completed')
            ->where('created_at', '>=', $dateLimit)
            ->select(
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(final_total) as total_revenue'),
                DB::raw('AVG(final_total) as avg_ticket')
            )->first();

        // 2. Top 5 Menu (Filter Tanggal & Exclude DP)
        $topItems = SaleItem::whereHas('sale', function ($query) use ($dateLimit) {
            $query->where('status', 'completed')->where('created_at', '>=', $dateLimit);
        })
            ->where('product_name', '!=', 'Down Payment (DP)')
            ->select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_sales'))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // 3. Ringkasan Stok (Low Stock)
        $lowStockProducts = Product::where('stock', '<', 10)
            ->where('is_sellable', true)
            ->where('name', '!=', 'Down Payment (DP)')
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get();

        $lowStockCount = Product::where('stock', '<', 10)
            ->where('is_sellable', true)
            ->where('name', '!=', 'Down Payment (DP)')
            ->count();

        // Bangun String Konteks
        $context = "DATA ANALISIS " . strtoupper($days . " Hari Terakhir") . ":\n";
        $context .= "- Total Order: {$salesSummary->total_orders}\n";
        $context .= "- Total Pendapatan: Rp " . number_format($salesSummary->total_revenue, 0, ',', '.') . "\n";
        $context .= "- Rata-rata per Transaksi: Rp " . number_format($salesSummary->avg_ticket, 0, ',', '.') . "\n\n";

        $context .= "TOP 5 MENU TERLARIS:\n";
        if ($topItems->isEmpty()) {
            $context .= "- Belum ada data penjualan.\n";
        }
        foreach ($topItems as $item) {
            $context .= "- {$item->product_name}: {$item->total_qty} unit (Rp " . number_format($item->total_sales, 0, ',', '.') . ")\n";
        }

        $context .= "\nINVENTORI & STOK:\n";
        $context .= "- Jumlah Item Stok Rendah (< 10): {$lowStockCount} item\n";
        if ($lowStockProducts->isNotEmpty()) {
            $context .= "- Contoh Item Kritis: ";
            $context .= $lowStockProducts->map(fn($p) => "{$p->name} ({$p->stock} pcs)")->implode(', ');
            $context .= "\n";
        }

        return $context;
    }

    public function clearChat()
    {
        $this->messages = [];
        $this->mount();
    }
}
