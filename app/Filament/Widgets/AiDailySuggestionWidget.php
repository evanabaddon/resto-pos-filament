<?php

namespace App\Filament\Widgets;


use App\Models\Product;
use App\Models\Sale;
use App\Services\DeepSeekService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class AiDailySuggestionWidget extends Widget
{
    protected string $view = 'filament.widgets.ai-daily-suggestion-widget';
    protected static ?int $sort = -5; // Show at the top
    protected int|string|array $columnSpan = 'full';

    // Enable lazy loading for better performance
    protected static bool $isLazy = true;

    public $suggestion;

    public function mount()
    {
        $this->suggestion = $this->getSuggestion();
    }

    public function refreshSuggestion()
    {
        Cache::forget('ai_daily_suggestion');
        $this->suggestion = $this->getSuggestion();
    }

    protected function getSuggestion()
    {
        // Increased cache to 2 hours to reduce API calls
        return Cache::remember('ai_daily_suggestion', 7200, function () {
            try {
                $context = $this->getQuickContext();
                $service = new DeepSeekService();

                $history = [
                    ['role' => 'user', 'content' => "Berdasarkan data berikut, berikan 1 saran bisnis harian DAN (jika ada) peringatan stok kritis. Pastikan keduanya muncul meski singkat. Maksimal 25 kata.\n\n{$context}"]
                ];

                $response = $service->analyzeBusiness($history, "Berikan kombinasi saran strategi bisnis dan info stok singkat.");

                return $response['choices'][0]['message']['content'] ?? "Semangat jualan hari ini, Bos! Pastikan pelayanan maksimal.";
            } catch (\Exception $e) {
                return "Fokus pada pelayanan terbaik hari ini. Pelanggan puas, rejeki lancar!";
            }
        });
    }

    protected function getQuickContext(): string
    {
        $todayRevenue = Sale::whereDate('created_at', today())->where('status', 'completed')->sum('final_total');

        $lowStockItems = Product::where('stock', '<', 10)
            ->where('is_sellable', true)
            ->where('name', '!=', 'Down Payment (DP)')
            ->whereDoesntHave('recipes') // Only track stock for items without recipes (Retail/Raw)
            ->orderBy('stock', 'asc')
            ->limit(3)
            ->get();

        $lowStockItemsWithIngredients = Product::where('stock', '<', 10)
            ->where('is_sellable', true)
            ->where('name', '!=', 'Down Payment (DP)')
            ->whereHas('recipes') // Only track stock for items without recipes (Retail/Raw)
            ->orderBy('stock', 'asc')
            ->limit(3)
            ->get();

        $lowStockIngredients = Product::where('stock', '<', 10)
            ->whereHas('usedInRecipes') // Only items used as ingredients
            ->orderBy('stock', 'asc')
            ->limit(5)
            ->get();

        $lowStockCount = Product::where('stock', '<', 10)
            ->where('is_sellable', true)
            ->where('name', '!=', 'Down Payment (DP)')
            ->whereDoesntHave('recipes')
            ->count();

        $context = "Pendapatan Hari Ini: Rp" . number_format($todayRevenue, 0, ',', '.') . ". Total retail kritis: {$lowStockCount}. ";

        if ($lowStockItems->isNotEmpty()) {
            $context .= "Produk Retail Kritis: " . $lowStockItems->map(fn($p) => "{$p->name} ({$p->stock})")->implode(', ') . ". ";
        }

        if ($lowStockItemsWithIngredients->isNotEmpty()) {
            $context .= "Produk Kritis" . $lowStockItemsWithIngredients->map(fn($p) => "{$p->name} ({$p->stock})")->implode(', ') . ". ";
        }

        if ($lowStockIngredients->isNotEmpty()) {
            $context .= "BAHAN BAKU KRITIS (Wajib Restock): " . $lowStockIngredients->map(fn($p) => "{$p->name} (Sisa {$p->stock})")->implode(', ') . ".";
        }

        return $context;
    }
}
