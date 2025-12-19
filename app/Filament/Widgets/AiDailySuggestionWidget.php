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
        return Cache::remember('ai_daily_suggestion', now()->endOfDay(), function () {
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
            ->orderBy('stock', 'asc')
            ->limit(3)
            ->get();

        $lowStockCount = Product::where('stock', '<', 10)->where('is_sellable', true)->count();

        $context = "Pendapatan Hari Ini: Rp" . number_format($todayRevenue, 0, ',', '.') . ". Total item kritis: {$lowStockCount}. ";

        if ($lowStockItems->isNotEmpty()) {
            $context .= "Produk paling kritis: " . $lowStockItems->map(fn($p) => "{$p->name} ({$p->stock})")->implode(', ') . ".";
        }

        return $context;
    }
}
