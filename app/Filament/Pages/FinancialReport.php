<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Payroll;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Illuminate\Support\Carbon;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;

class FinancialReport extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $title = null;
    protected static ?string $navigationLabel = null;

    public static function getNavigationGroup(): ?string
    {
        return __('messages.reports_analytics');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.financial_report');
    }

    public function getTitle(): string
    {
        return __('messages.financial_report');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin;
    }
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.financial-report';

    // Disabled auto header widgets to prevent double rendering
    // Widgets are manually rendered in blade view for better positioning
    protected function getHeaderWidgets(): array
    {
        return [];
    }

    // Method to get widgets for manual rendering in blade
    public function getManualHeaderWidgets(): array
    {
        return [
            \App\Filament\Pages\FinancialReport\Widgets\FinancialTrendChart::class,
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
        ];
    }

    public ?string $date_start = null;
    public ?string $date_end = null;

    // Quick Filters
    public ?int $filter_month = null;
    public ?int $filter_year = null;

    // Stats properties
    public float $totalGrossSales = 0;
    public float $totalDiscounts = 0;
    public float $totalTaxes = 0;
    public float $totalRevenue = 0; // Net Sales
    public float $totalCogs = 0; // Estimated based on sales (Accrual HPP)
    public float $totalPurchases = 0; // Informational (Cash Out for Stock)
    public float $totalHpp = 0; // primary HPP for profit
    public float $totalGrossProfit = 0;
    public float $totalExpenses = 0; // Operational only
    public float $totalPayroll = 0; // Salaries
    public float $netProfit = 0;
    public float $grossMargin = 0;
    public float $currentStockValue = 0; // Value of unsold inventory

    // Comparison Stats
    public float $prevTotalRevenue = 0;
    public float $prevTotalHpp = 0;
    public float $prevTotalExpenses = 0;
    public float $prevNetProfit = 0;
    public float $growthRevenue = 0;
    public float $growthHpp = 0;
    public float $growthExpenses = 0;
    public float $growthNetProfit = 0;

    public array $expenseBreakdown = [];
    public array $granularExpenseBreakdown = [];
    public array $purchaseBreakdown = [];
    public array $hppContributors = [];
    public array $profitContributors = [];
    public float $totalWastage = 0;
    public array $topStockAssets = [];

    public function mount()
    {
        $this->date_start = now()->startOfMonth()->format('Y-m-d');
        $this->date_end = now()->endOfMonth()->format('Y-m-d');
        $this->filter_month = date('n');
        $this->filter_year = date('Y');

        $this->form->fill([
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'filter_month' => $this->filter_month,
            'filter_year' => $this->filter_year,
        ]);

        $this->calculateStats();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make(__('messages.filter_report'))
                ->schema([
                    Grid::make(4)
                        ->schema([
                            Select::make('filter_month')
                                ->label(__('messages.month'))
                                ->options([
                                    1 => __('messages.january'),
                                    2 => __('messages.february'),
                                    3 => __('messages.march'),
                                    4 => __('messages.april'),
                                    5 => __('messages.may'),
                                    6 => __('messages.june'),
                                    7 => __('messages.july'),
                                    8 => __('messages.august'),
                                    9 => __('messages.september'),
                                    10 => __('messages.october'),
                                    11 => __('messages.november'),
                                    12 => __('messages.december')
                                ])
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if ($state) {
                                        $year = $get('filter_year') ?: date('Y');
                                        $start = Carbon::createFromDate($year, $state)->startOfMonth()->format('Y-m-d');
                                        $end = Carbon::createFromDate($year, $state)->endOfMonth()->format('Y-m-d');

                                        $set('date_start', $start);
                                        $set('date_end', $end);

                                        $this->date_start = $start;
                                        $this->date_end = $end;
                                        $this->calculateStats();
                                    }
                                }),

                            Select::make('filter_year')
                                ->label(__('messages.year'))
                                ->options(array_combine(range(date('Y') - 5, date('Y') + 1), range(date('Y') - 5, date('Y') + 1)))
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if ($state) {
                                        $month = $get('filter_month') ?: date('n');
                                        $start = Carbon::createFromDate($state, $month)->startOfMonth()->format('Y-m-d');
                                        $end = Carbon::createFromDate($state, $month)->endOfMonth()->format('Y-m-d');

                                        $set('date_start', $start);
                                        $set('date_end', $end);

                                        $this->date_start = $start;
                                        $this->date_end = $end;
                                        $this->calculateStats();
                                    }
                                }),

                            DatePicker::make('date_start')
                                ->label(__('messages.start_date'))
                                ->live()
                                ->afterStateUpdated(function ($state) {
                                    $this->date_start = $state;
                                    $this->calculateStats();
                                }),

                            DatePicker::make('date_end')
                                ->label(__('messages.end_date'))
                                ->live()
                                ->afterStateUpdated(function ($state) {
                                    $this->date_end = $state;
                                    $this->calculateStats();
                                }),
                        ]),
                ])
                ->collapsible(),
        ];
    }

    public array $breakdownCogs = [];
    public array $breakdownExpenses = [];



    public function calculateStats()
    {
        if (!$this->date_start || !$this->date_end)
            return;

        $startDate = Carbon::parse($this->date_start)->startOfDay();
        $endDate = Carbon::parse($this->date_end)->endOfDay();

        // 1. Revenue (Omzet) - Paid Sales only
        $sales = Sale::with(['items.product.recipes.ingredient', 'items.product.unit', 'paymentMethod'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $this->totalGrossSales = $sales->sum('subtotal');
        $this->totalDiscounts = $sales->sum('discount');
        $this->totalTaxes = $sales->sum('tax');

        // Net Revenue = Gross Sales - Discounts (Tax is excluded from Revenue)
        $this->totalRevenue = $this->totalGrossSales - $this->totalDiscounts;

        // 2. COGS (HPP) - Accrual (Modal barang terjual)
        $this->totalCogs = 0;
        $contributors = [];

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $hpp = (float) $item->product->computed_hpp;
                    $totalItemHpp = $hpp * $item->quantity;
                    $this->totalCogs += $totalItemHpp;

                    if (!isset($contributors[$item->product->name])) {
                        $contributors[$item->product->name] = [
                            'name' => $item->product->name,
                            'qty' => 0,
                            'unit_hpp' => $hpp,
                            'total_hpp' => 0,
                            'total_revenue' => 0,
                            'total_profit' => 0,
                        ];
                    }
                    $contributors[$item->product->name]['qty'] += $item->quantity;
                    $contributors[$item->product->name]['total_hpp'] += $totalItemHpp;
                    $contributors[$item->product->name]['total_revenue'] += $item->subtotal;
                    $contributors[$item->product->name]['total_profit'] += ($item->subtotal - $totalItemHpp);
                }
            }
        }

        // Prepare Full Breakdown for Table/PDF
        $this->breakdownCogs = collect($contributors)->sortByDesc('total_hpp')->values()->toArray();

        // Sort Top Lists (Existing logic)
        $sortByHpp = $contributors;
        usort($sortByHpp, fn($a, $b) => $b['total_hpp'] <=> $a['total_hpp']);
        $this->hppContributors = array_slice($sortByHpp, 0, 5);

        $sortByProfit = $contributors;
        usort($sortByProfit, fn($a, $b) => $b['total_profit'] <=> $a['total_profit']);
        $this->profitContributors = array_slice($sortByProfit, 0, 5);

        // 3. Gross Profit (Accrual)
        $this->totalHpp = $this->totalCogs;
        $this->totalGrossProfit = $this->totalRevenue - $this->totalHpp;
        $this->grossMargin = $this->totalRevenue > 0 ? ($this->totalGrossProfit / $this->totalRevenue) * 100 : 0;

        // 4. Operational Expenses (Exclude items tagged as 'Stock Purchase' to prevent double counting with HPP)
        $allExpenses = Expense::with(['category', 'items'])
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalOperationalExpenses = 0;
        $totalStockExpenses = 0;
        $this->breakdownExpenses = [];

        foreach ($allExpenses as $expense) {
            foreach ($expense->items as $item) {
                if ($item->is_stock_purchase) {
                    $totalStockExpenses += (float) $item->amount;
                } else {
                    $totalOperationalExpenses += (float) $item->amount;
                    $this->breakdownExpenses[] = [
                        'date' => $expense->date,
                        'category' => $expense->category ? $expense->category->name : __('messages.general'),
                        'description' => $item->description,
                        'amount' => $item->amount
                    ];
                }
            }
        }

        $this->breakdownExpenses = collect($this->breakdownExpenses)->sortBy('date')->values()->toArray();

        // 5. Payroll Integration
        $payrolls = Payroll::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->get();
        $this->totalPayroll = (float) $payrolls->sum('total_payout');

        // 6. Purchases (Informational)
        $purchases = Purchase::with('items.product')
            ->where('status', 'received')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        $this->totalPurchases = (float) $purchases->sum('total') + $totalStockExpenses;

        $productPurchases = [];
        foreach ($purchases as $purchase) {
            foreach ($purchase->items as $item) {
                $productName = $item->product_name ?? ($item->product->name ?? __('messages.unknown_product'));
                if (!isset($productPurchases[$productName])) {
                    $productPurchases[$productName] = 0;
                }
                $productPurchases[$productName] += ($item->price * $item->quantity);
            }
        }
        arsort($productPurchases);
        $this->purchaseBreakdown = $productPurchases;

        // Final Totals
        // 7. Wastage (Barang Rusak/Basi)
        // Hitung nilai kerugian dari barang yang rusak/basi
        $wastageMovements = \App\Models\StockMovement::where('type', 'decrease')
            ->where(function ($query) {
                $query->where('reason', 'like', '%damage%')
                    ->orWhere('reason', 'like', '%rusak%')
                    ->orWhere('reason', 'like', '%basi%')
                    ->orWhere('reason', 'like', '%expired%');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('product') // Eager load product for base_price
            ->get();

        $this->totalWastage = $wastageMovements->sum(function ($movement) {
            return $movement->quantity * ($movement->product->base_price ?? 0);
        });

        // Add Wastage to Expenses
        $this->totalExpenses = $totalOperationalExpenses + $this->totalPayroll + $this->totalWastage;

        $this->expenseBreakdown = [];
        foreach ($allExpenses as $expense) {
            $catName = $expense->category ? $expense->category->name : __('messages.general');
            if (!isset($this->expenseBreakdown[$catName])) {
                $this->expenseBreakdown[$catName] = 0;
            }
            $this->expenseBreakdown[$catName] += $expense->items->where('is_stock_purchase', false)->sum('amount');
        }

        if ($this->totalPayroll > 0) {
            $this->expenseBreakdown[__('messages.salary_allowance')] = $this->totalPayroll;
        }

        if ($this->totalWastage > 0) {
            $this->expenseBreakdown[__('messages.wastage')] = $this->totalWastage;
        }

        // 8. Current Asset Value (Nilai Aset Stok Saat Ini)
        $allProducts = \App\Models\Product::where('is_sellable', true)
            ->orWhere('type', 'raw')
            ->get();

        $this->currentStockValue = $allProducts->sum(function ($product) {
            return $product->stock * ($product->base_price ?? 0);
        });

        // Top Stock Assets
        $this->topStockAssets = $allProducts->map(function ($product) {
            return [
                'name' => $product->name,
                'stock' => $product->stock,
                'unit' => $product->unit->symbol ?? '',
                'price' => $product->base_price ?? 0,
                'total_value' => $product->stock * ($product->base_price ?? 0),
            ];
        })
            ->sortByDesc('total_value')
            ->take(10)
            ->values()
            ->toArray();

        $this->netProfit = $this->totalGrossProfit - $this->totalExpenses;

        $this->calculatePreviousStats($startDate, $endDate);

        $this->dispatch('updateChart', [
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
        ]);
    }

    public function calculatePreviousStats($currentStart, $currentEnd)
    {
        // Determine previous period
        $daysDiff = $currentStart->diffInDays($currentEnd) + 1;
        $prevStart = $currentStart->copy()->subDays($daysDiff);
        $prevEnd = $currentEnd->copy()->subDays($daysDiff);

        // 1. Previous Revenue
        $prevSales = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->get();

        $prevGrossSales = $prevSales->sum('subtotal');
        $prevDiscounts = $prevSales->sum('discount');
        $this->prevTotalRevenue = $prevGrossSales - $prevDiscounts;

        // 2. Previous HPP (Simplified for performance, assuming same ratio or recalculate if needed)
        // For accuracy we should recalculate exactly like main stats
        $this->prevTotalHpp = 0;
        foreach ($prevSales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $hpp = (float) $item->product->computed_hpp;
                    $this->prevTotalHpp += ($hpp * $item->quantity);
                }
            }
        }

        // 3. Previous Expenses + Payroll + Wastage
        $prevAllExpenses = Expense::with('items')
            ->where('status', 'approved')
            ->whereBetween('date', [$prevStart, $prevEnd])
            ->get();

        $prevExpensesTotal = 0;
        foreach ($prevAllExpenses as $expense) {
            $prevExpensesTotal += $expense->items->where('is_stock_purchase', false)->sum('amount');
        }

        $prevPayroll = Payroll::whereBetween('created_at', [$prevStart, $prevEnd])
            ->where('status', 'paid')
            ->sum('total_payout');

        $prevWastage = \App\Models\StockMovement::where('type', 'decrease')
            ->where(function ($query) {
                $query->where('reason', 'like', '%damage%')
                    ->orWhere('reason', 'like', '%rusak%')
                    ->orWhere('reason', 'like', '%basi%')
                    ->orWhere('reason', 'like', '%expired%');
            })
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->get()
            ->sum(function ($movement) {
                return $movement->quantity * ($movement->product->base_price ?? 0);
            });

        $this->prevTotalExpenses = (float) $prevExpensesTotal + (float) $prevPayroll + $prevWastage;

        // 4. Previous Net Profit
        $this->prevNetProfit = ($this->prevTotalRevenue - $this->prevTotalHpp) - $this->prevTotalExpenses;

        // Calculate Growth %
        $this->growthRevenue = $this->calculateGrowth($this->totalRevenue, $this->prevTotalRevenue);
        $this->growthHpp = $this->calculateGrowth($this->totalHpp, $this->prevTotalHpp);
        $this->growthExpenses = $this->calculateGrowth($this->totalExpenses, $this->prevTotalExpenses);
        $this->growthNetProfit = $this->calculateGrowth($this->netProfit, $this->prevNetProfit);
    }

    protected function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label(__('messages.download_report_pdf'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.financial-report', [
                        'startDate' => $this->date_start,
                        'endDate' => $this->date_end,
                        'totalRevenue' => $this->totalRevenue,
                        'totalHpp' => $this->totalCogs,
                        'grossProfit' => $this->totalRevenue - $this->totalCogs,
                        'totalExpenses' => $this->totalExpenses, // Already includes Payroll & Wastage
                        'totalPurchases' => $this->totalPurchases,
                        'netProfit' => ($this->totalRevenue - $this->totalCogs) - $this->totalExpenses,
                        'breakdownCogs' => $this->breakdownCogs,
                        'breakdownExpenses' => $this->breakdownExpenses,
                        'currentStockValue' => $this->currentStockValue,
                        'topStockAssets' => $this->topStockAssets,
                    ]);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'Laporan-Laba-Rugi-' . date('Ymd-His') . '.pdf');
                }),
            Action::make('refresh')
                ->label(__('messages.refresh_data'))
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->calculateStats()),
        ];
    }
}
