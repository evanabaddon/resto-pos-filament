<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class StockOpnameController extends Controller
{
    public function printForm(Request $request)
    {
        $query = Product::query()
            ->with(['unit', 'category'])
            ->orderBy('category_id')
            ->orderBy('name');

        // Apply filters based on filter_type
        $filterType = $request->filter_type ?? 'all';

        // 1. Base Logic: Which items are "Stockable"?
        // If ALL/Default: Show Raw/Retail OR (Produced+Alert)
        if ($filterType === 'all') {
            $query->where(function ($q) {
                $q->whereIn('type', ['raw', 'retail'])
                    ->orWhere(function ($sub) {
                        $sub->whereIn('type', ['produced', 'bar'])
                            ->where('enable_stock_alert', true);
                    });
            });
        }

        // 2. Specific Category: Show ALL items in that category (regardless of alert setting)
        // User explicitly asked for this category, so we show what's in it.
        if ($filterType === 'category' && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // 3. Specific Type: Apply the type filter
        if ($filterType === 'type' && $request->product_type) {
            $query->where('type', $request->product_type);
            // Optionally enforce stock alert for produced?
            // If user asks for "Prepared (Kitchen)", they expect to see them.
            if (in_array($request->product_type, ['produced', 'bar'])) {
                $query->where('enable_stock_alert', true);
            }
        }

        \Illuminate\Support\Facades\Log::info('StockOpname Print Request:', $request->all());

        $products = $query->get();
        \Illuminate\Support\Facades\Log::info('StockOpname Print Results:', ['count' => $products->count()]);

        $data = [
            'products' => $products,
            'date' => $request->opname_date ?? now()->format('Y-m-d'),
            'shift' => $request->shift ?? 'Closing',
            'filter_info' => $this->getFilterInfo($request),
            'printed_at' => now(),
        ];

        $pdf = Pdf::loadView('pdf.stock-opname-form', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->stream('Stock-Opname-Form-' . now()->format('Y-m-d') . '.pdf');
    }

    private function getFilterInfo(Request $request): string
    {
        $filterType = $request->filter_type ?? 'all';

        if ($filterType === 'category' && $request->category_id) {
            $category = Category::find($request->category_id);
            return 'Category: ' . ($category?->name ?? 'Unknown');
        }

        if ($filterType === 'type' && $request->product_type) {
            $types = [
                'raw' => 'Raw Material (Bahan Baku)',
                'produced' => 'Produced (Kitchen)',
                'bar' => 'Bar/Beverage',
                'retail' => 'Retail',
            ];
            return 'Type: ' . ($types[$request->product_type] ?? $request->product_type);
        }

        return 'All Products';
    }
}
