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
        // 1. Ringkasan Penjualan 30 Hari Terakhir
        $salesSummary = Sale::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(final_total) as total_revenue'),
                DB::raw('AVG(final_total) as avg_ticket')
            )->first();

        // 2. Top 5 Menu Terlaris
        $topItems = SaleItem::with('product')
            ->select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_sales'))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // 3. Ringkasan Stok (Low Stock)
        $lowStockCount = Product::where('stock', '<', 10)->count();

        // Bangun String Konteks
        $context = "RINGKASAN BISNIS (30 HARI TERAKHIR):\n";
        $context .= "- Total Order: {$salesSummary->total_orders}\n";
        $context .= "- Total Pendapatan: Rp " . number_format($salesSummary->total_revenue, 0, ',', '.') . "\n";
        $context .= "- Rata-rata belanja per tamu: Rp " . number_format($salesSummary->avg_ticket, 0, ',', '.') . "\n\n";

        $context .= "TOP 5 MENU:\n";
        foreach ($topItems as $item) {
            $context .= "- {$item->product_name}: {$item->total_qty} terjual (Rp " . number_format($item->total_sales, 0, ',', '.') . ")\n";
        }

        $context .= "\nSTATUS OPERASIONAL:\n";
        $context .= "- Produk stok rendah (< 10): {$lowStockCount} item\n";

        return $context;
    }

    public function clearChat()
    {
        $this->messages = [];
        $this->mount();
    }
}
