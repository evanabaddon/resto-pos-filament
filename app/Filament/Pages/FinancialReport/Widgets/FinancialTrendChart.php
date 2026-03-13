<?php

namespace App\Filament\Pages\FinancialReport\Widgets;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\Payroll;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class FinancialTrendChart extends ChartWidget
{
    protected ?string $heading = null;

    public function getHeading(): string
    {
        return __('messages.financial_trend');
    }
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';
    protected ?string $maxHeight = '300px';

    public ?string $filter = 'day';

    public ?string $date_start = null;
    public ?string $date_end = null;

    protected $listeners = ['updateChart' => 'applyFilter'];

    public function applyFilter($data): void
    {
        $this->date_start = $data['date_start'] ?? $this->date_start;
        $this->date_end = $data['date_end'] ?? $this->date_end;
    }

    protected function getData(): array
    {
        $start = $this->date_start ? Carbon::parse($this->date_start) : now()->startOfMonth();
        $end = $this->date_end ? Carbon::parse($this->date_end) : now()->endOfMonth();

        // Generate Date Range Label
        $period = \Carbon\CarbonPeriod::create($start, $end);
        $labels = [];
        foreach ($period as $date) {
            $labels[] = $date->format('d M');
        }

        // 1. Revenue (Completed Sales)
        $sales = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('d M');
            });

        // 2. Expenses (Approved)
        $expenses = Expense::with('items')
            ->where('status', 'approved')
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->date)->format('d M');
            });

        // 3. Payroll (Paid)
        $payrolls = Payroll::where('status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('d M');
            });

        $revenueData = [];
        $expenseData = [];
        $profitData = [];

        foreach ($labels as $dateLabel) {
            // Revenue
            $daySales = $sales->get($dateLabel) ?? collect();
            $dayRevenue = $daySales->sum('subtotal') - $daySales->sum('discount');
            $revenueData[] = $dayRevenue;

            // Expenses (Ops + Payroll) - Sum only non-stock items
            $dayExpenses = $expenses->get($dateLabel) ?? collect();
            $dayOps = 0;
            foreach ($dayExpenses as $exp) {
                $isTopLevelStock = (bool) ($exp->is_stock_purchase ?? false);
                $dayOps += $exp->items->filter(function ($item) use ($isTopLevelStock) {
                    return !($isTopLevelStock || ($item->is_stock_purchase ?? false));
                })->sum('amount');
            }
            $dayPayroll = $payrolls->get($dateLabel) ?? collect();
            $totalDayExpense = $dayOps + $dayPayroll->sum('total_payout');
            $expenseData[] = $totalDayExpense;

            // Profit (Simplified: Revenue - Expenses, ignoring HPP for chart simplicity or we can calculate Gross Profit)
            // Ideally Profit = Revenue - HPP - Expenses. Calculating HPP per day query is heavy.
            // Let's stick to Revenue vs Expenses for now, or approximate HPP if needed.
            // Let's use Cash Flow Profit (Revenue - Expense) for this trend chart.
            $profitData[] = $dayRevenue - $totalDayExpense;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Net Sales (Omzet)',
                    'data' => $revenueData,
                    'borderColor' => '#16a34a', // Green
                    'backgroundColor' => 'rgba(22, 163, 74, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Total Expenses',
                    'data' => $expenseData,
                    'borderColor' => '#dc2626', // Red
                    'backgroundColor' => 'rgba(220, 38, 38, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
