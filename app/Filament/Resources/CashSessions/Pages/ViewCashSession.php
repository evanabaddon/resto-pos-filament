<?php

namespace App\Filament\Resources\CashSessions\Pages;

use App\Filament\Resources\CashSessions\CashSessionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;

class ViewCashSession extends ViewRecord
{
    protected static string $resource = CashSessionResource::class;

    public function getTitle(): string
    {
        return __('messages.cash_session_detail');
    }

    public function getHeaderActions(): array
    {
        return [
            // Tombol kembali
            Action::make('back')
                ->label(__('messages.back'))
                ->icon('heroicon-o-arrow-left')
                ->url(ListCashSessions::getUrl()),
        ];
    }

    // sumarize penjualan cash session
    protected function getViewData(): array
    {
        $data = parent::getViewData();

        // Tambahkan data summary berdasarkan accessor di model
        $data['summary'] = [
            'total_cash_sales' => $this->record->total_cash_sales,
            'total_non_cash_sales' => $this->record->total_non_cash_sales,
            'total_completed_sales' => $this->record->total_completed_sales,
            'expected_cash' => $this->record->expected_cash,
            'cash_profit' => $this->record->cash_profit,
            'transaction_count' => $this->record->sales()->where('status', 'completed')->count(),
        ];

        return $data;
    }
}
