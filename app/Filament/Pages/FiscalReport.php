<?php

namespace App\Filament\Pages;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;
use App\Settings\GeneralSettings;

class FiscalReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.pages.fiscal-report';

    public static function getNavigationGroup(): ?string
    {
        return 'Laporan';
    }

    public static function getNavigationIcon(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-document-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Laporan Pajak (Fiskal)';
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Laporan Pajak (Fiskal)';
    }

    public ?string $date_start = null;
    public ?string $date_end = null;
    public ?string $target_daily_revenue = null;

    public function mount(): void
    {
        $this->date_start = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->date_end = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->form->fill([
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $settings = app(GeneralSettings::class);

        return $schema
            ->schema([
                Section::make('Filter Laporan')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('date_start')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->live(),
                        DatePicker::make('date_end')
                            ->label('Tanggal Akhir')
                            ->required()
                            ->live(),
                        TextInput::make('target_daily_revenue')
                            ->label('Target Omzet Harian (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('2000000')
                            ->helperText('Sistem akan memilih transaksi secara acak untuk mendekati angka ini per hari.')
                            ->visible($settings->enable_fiscal_planning),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        $settings = app(GeneralSettings::class);

        return $table
            ->query(function () {
                return Sale::query()
                    ->whereBetween('created_at', [
                        Carbon::parse($this->date_start)->startOfDay(),
                        Carbon::parse($this->date_end)->endOfDay()
                    ])
                    ->orderBy('created_at', 'desc');
            })
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime(),
                TextColumn::make('invoice_number')
                    ->label('Invoice'),
                TextColumn::make('final_total')
                    ->label('Total')
                    ->money('IDR'),
                TextColumn::make('is_tax_reported')
                    ->label('Lapor Pajak')
                    ->badge()
                    ->color(fn(bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Ya' : 'Tidak')
                    ->visible($settings->enable_fiscal_planning),
            ])
            ->actions([
                Action::make('toggle_tax')
                    ->icon(fn(Sale $record) => $record->is_tax_reported ? 'heroicon-o-x-mark' : 'heroicon-o-check')
                    ->color(fn(Sale $record) => $record->is_tax_reported ? 'danger' : 'success')
                    ->tooltip(fn(Sale $record) => $record->is_tax_reported ? 'Hapus dari Laporan' : 'Masukkan ke Laporan')
                    ->action(function (Sale $record) {
                        $record->update(['is_tax_reported' => !$record->is_tax_reported]);
                    })
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-arrow-path')
                    ->modalDescription('Apakah anda yakin ingin mengubah status laporan pajak transaksi ini?')
                    ->visible($settings->enable_fiscal_planning),
            ])
            ->filters([
                SelectFilter::make('is_tax_reported')
                    ->label('Status Lapor Pajak')
                    ->options([
                        1 => 'Ya (Dilaporkan)',
                        0 => 'Tidak (Internal)',
                    ])
                    ->default($settings->enable_fiscal_planning ? 1 : null)
                    ->visible($settings->enable_fiscal_planning),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $settings = app(GeneralSettings::class);

        return [
            Action::make('export_recap')
                ->label('Export Rekap Harian (PDF)')
                ->color('warning')
                ->icon('heroicon-o-document-chart-bar')
                ->action(function () use ($settings) {
                    $query = Sale::query()
                        ->whereBetween('created_at', [
                            Carbon::parse($this->date_start)->startOfDay(),
                            Carbon::parse($this->date_end)->endOfDay()
                        ]);

                    // Only filter if module is enabled
                    if ($settings->enable_fiscal_planning) {
                        $query->where('is_tax_reported', true);
                    }

                    $data = $query->selectRaw('DATE(created_at) as date, SUM(final_total) as total_sales, SUM(tax) as total_tax')
                        ->groupBy('date')
                        ->orderBy('date', 'asc')
                        ->get();

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.daily-fiscal-recap', [
                        'data' => $data,
                        'startDate' => Carbon::parse($this->date_start)->format('d F Y'),
                        'endDate' => Carbon::parse($this->date_end)->format('d F Y'),
                    ]);

                    return response()->streamDownload(
                        fn() => print ($pdf->output()),
                        'rekap-pajak-' . now()->timestamp . '.pdf'
                    );
                }),

            Action::make('export_template')
                ->label('Export Excel (Template)')
                ->color('success')
                ->icon('heroicon-o-table-cells')
                ->visible($settings->enable_fiscal_planning)
                ->action(function () use ($settings) {
                    if (!$settings->template_path) {
                        Notification::make()->title('Template belum diupload di Pengaturan Pajak')->danger()->send();
                        return;
                    }

                    $templatePath = storage_path('app/public/' . $settings->template_path);
                    if (!file_exists($templatePath)) {
                        Notification::make()->title('File template tidak ditemukan')->danger()->send();
                        return;
                    }

                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
                    $sheet = $spreadsheet->getActiveSheet();

                    $query = Sale::query()
                        ->whereBetween('created_at', [
                            Carbon::parse($this->date_start)->startOfDay(),
                            Carbon::parse($this->date_end)->endOfDay()
                        ]);

                    if ($settings->enable_fiscal_planning) {
                        $query->where('is_tax_reported', true);
                    }

                    $data = $query->selectRaw('DATE(created_at) as date, SUM(final_total) as total_sales')
                        ->groupBy('date')
                        ->orderBy('date', 'asc')
                        ->get();

                    $row = $settings->start_row;
                    foreach ($data as $record) {
                        $final = $record->total_sales;
                        $tax = $final - ($final / 1.1);

                        $sheet->setCellValue($settings->date_column . $row, Carbon::parse($record->date)->format('d/m/Y'));
                        $sheet->setCellValue($settings->amount_column . $row, $final);
                        $sheet->setCellValue($settings->tax_column . $row, $tax);

                        $row++;
                    }

                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

                    return response()->streamDownload(
                        fn() => $writer->save('php://output'),
                        'laporan-pajak-template-' . now()->timestamp . '.xlsx'
                    );
                }),

            FilamentExportHeaderAction::make('export')
                ->label('Export Proposal (Detail)')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->visible($settings->enable_fiscal_planning),

            Action::make('generate_proposal')
                ->label('Generate Proposal')
                ->color('primary')
                ->icon('heroicon-o-cpu-chip')
                ->requiresConfirmation()
                ->visible(fn() => auth()->user()->role === 'super_admin' && $settings->enable_fiscal_planning)
                ->action(function () {
                    $this->generateProposal();
                }),

            Action::make('reset_all')
                ->label('Reset Semua')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->visible(fn() => auth()->user()->role === 'super_admin' && $settings->enable_fiscal_planning)
                ->action(function () {
                    $this->resetProposal();
                }),
        ];
    }

    public function generateProposal()
    {
        $target = (float) $this->target_daily_revenue;
        if (!$target || $target <= 0) {
            Notification::make()->title('Target omzet harus diisi')->danger()->send();
            return;
        }

        $startDate = Carbon::parse($this->date_start);
        $endDate = Carbon::parse($this->date_end);

        // Reset first for the range
        Sale::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->update(['is_tax_reported' => false]);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            // Randomize target for this specific day (e.g., +/- 15% variance)
            $variance = 0.15;
            $dailyTarget = rand($target * (1 - $variance), $target * (1 + $variance));

            $sales = Sale::whereBetween('created_at', [$dayStart, $dayEnd])
                ->inRandomOrder() // Shuffle
                ->get();

            $currentTotal = 0;
            foreach ($sales as $sale) {
                if ($currentTotal + $sale->final_total <= $dailyTarget * 1.05) {

                    $sale->update(['is_tax_reported' => true]);
                    $currentTotal += $sale->final_total;

                    if ($currentTotal >= $dailyTarget * 0.95) {
                        break;
                    }
                }
            }
        }

        Notification::make()->title('Proposal Laporan Pajak Dibuat (Randomized)')->success()->send();
    }

    public function resetProposal()
    {
        Sale::whereBetween('created_at', [
            Carbon::parse($this->date_start)->startOfDay(),
            Carbon::parse($this->date_end)->endOfDay()
        ])->update(['is_tax_reported' => true]);

        Notification::make()->title('Semua transaksi dikembalikan ke Lapor Pajak')->success()->send();
    }
}
