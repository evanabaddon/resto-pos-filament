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
                    ['role' => 'user', 'content' => __('messages.ai_prompt_user', ['context' => $context])]
                ];

                $response = $service->analyzeBusiness($history, __('messages.ai_prompt_system'));

                return $response['choices'][0]['message']['content'] ?? __('messages.ai_default_advice');
            } catch (\Exception $e) {
                return __('messages.ai_error_advice');
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

        $context = __('messages.ai_daily_revenue_context', [
            'amount' => 'Rp' . number_format($todayRevenue, 0, ',', '.'),
            'count' => $lowStockCount
        ]) . ' ';

        if ($lowStockItems->isNotEmpty()) {
            $context .= __('messages.ai_critical_retail', ['list' => $lowStockItems->map(fn($p) => "{$p->name} ({$p->stock})")->implode(', ')]) . ' ';
        }

        if ($lowStockItemsWithIngredients->isNotEmpty()) {
            $context .= __('messages.ai_critical_products', ['list' => $lowStockItemsWithIngredients->map(fn($p) => "{$p->name} ({$p->stock})")->implode(', ')]) . ' ';
        }

        if ($lowStockIngredients->isNotEmpty()) {
            $context .= __('messages.ai_critical_ingredients', ['list' => $lowStockIngredients->map(fn($p) => "{$p->name} (Sisa {$p->stock})")->implode(', ')]) . '.';
        }

        return $context;
    }
}
