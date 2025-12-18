<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use App\Models\Sale;
use App\Models\Expense;
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
    protected static string|UnitEnum|null $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Keuangan (Laba/Rugi)';
    protected static ?string $navigationLabel = 'Laba/Rugi (Profit & Loss)';
    protected static ?int $navigationSort = 10;
    protected string $view = 'filament.pages.financial-report';

    public ?string $date_start = null;
    public ?string $date_end = null;

    // Quick Filters
    public ?int $filter_month = null;
    public ?int $filter_year = null;

    // Stats properties
    public float $totalRevenue = 0;
    public float $totalCogs = 0;
    public float $totalGrossProfit = 0;
    public float $totalExpenses = 0;
    public float $netProfit = 0;

    public array $expenseBreakdown = [];

    public function mount(): void
    {
        $this->date_start = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->date_end = Carbon::now()->endOfMonth()->format('Y-m-d');

        $this->filter_month = Carbon::now()->month;
        $this->filter_year = Carbon::now()->year;

        $this->form->fill([
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'filter_month' => $this->filter_month,
            'filter_year' => $this->filter_year,
        ]);

        $this->calculateStats();
    }

    public function updateDatesByMonth()
    {
        if ($this->filter_month && $this->filter_year) {
            $date = Carbon::createFromDate($this->filter_year, $this->filter_month, 1);

            $this->date_start = $date->startOfMonth()->format('Y-m-d');
            $this->date_end = $date->endOfMonth()->format('Y-m-d');

            $this->form->fill([
                'date_start' => $this->date_start,
                'date_end' => $this->date_end,
                'filter_month' => $this->filter_month,
                'filter_year' => $this->filter_year,
            ]);

            $this->calculateStats();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Filter Periode')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Select::make('filter_month')
                                    ->label('Bulan')
                                    ->options([
                                        1 => 'Januari',
                                        2 => 'Februari',
                                        3 => 'Maret',
                                        4 => 'April',
                                        5 => 'Mei',
                                        6 => 'Juni',
                                        7 => 'Juli',
                                        8 => 'Agustus',
                                        9 => 'September',
                                        10 => 'Oktober',
                                        11 => 'November',
                                        12 => 'Desember'
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->updateDatesByMonth()),

                                Select::make('filter_year')
                                    ->label('Tahun')
                                    ->options(function () {
                                        $years = [];
                                        for ($i = date('Y'); $i >= date('Y') - 5; $i--) {
                                            $years[$i] = $i;
                                        }
                                        return $years;
                                    })
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->updateDatesByMonth()),

                                DatePicker::make('date_start')
                                    ->label('Tanggal Mulai')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->calculateStats()),

                                DatePicker::make('date_end')
                                    ->label('Tanggal Akhir')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->calculateStats()),
                            ]),
                    ]),
            ]);
    }

    public function calculateStats()
    {
        $startDate = Carbon::parse($this->date_start)->startOfDay();
        $endDate = Carbon::parse($this->date_end)->endOfDay();

        // 1. Revenue (Omzet) - Paid Sales only
        $sales = Sale::with(['items.product.recipes.ingredient', 'items.product.unit'])
            ->where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $this->totalRevenue = $sales->sum('final_total');

        // 2. COGS (HPP) - Estimated based on current Product HPP
        // Note: Ideally, HPP should be stored in sale_items at transaction time.
        // For now, we calculate dynamically.
        $this->totalCogs = 0;
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $hpp = $item->product->computed_hpp ?? 0;
                    $this->totalCogs += ($hpp * $item->quantity);
                }
            }
        }

        // 3. Gross Profit
        $this->totalGrossProfit = $this->totalRevenue - $this->totalCogs;

        // 4. Expenses (Biaya Operasional) - Approved only
        $expenses = Expense::with('category')
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $this->totalExpenses = $expenses->sum('amount');

        // Breakdown expenses by category
        $this->expenseBreakdown = $expenses->groupBy('category.name')
            ->map(fn($group) => $group->sum('amount'))
            ->toArray();

        // 5. Net Profit (Laba Bersih)
        $this->netProfit = $this->totalGrossProfit - $this->totalExpenses;
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->calculateStats()),
        ];
    }
}
