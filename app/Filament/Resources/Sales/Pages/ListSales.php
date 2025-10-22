<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Models\Sale;
use App\Models\PaymentMethod;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Resources\Sales\SaleResource;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make()->label('Tambah Penjualan'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'Semua Penjualan' => Tab::make()->label('Semua Penjualan'),
            'Lunas' => Tab::make()->label('Lunas')->modifyQueryUsing(function ($query) {
                $query->where('status', 'completed');
            }),
            'Belum Lunas' => Tab::make()->label('Belum Lunas')->modifyQueryUsing(function ($query) {
                $query->where('status', 'draft');
            }),
        ];

        // Ambil semua payment method yang aktif
        $paymentMethods = PaymentMethod::active()->get();

        // Buat tab untuk setiap payment method
        foreach ($paymentMethods as $method) {
            $tabs[$method->name] = Tab::make()
                ->label($method->name)
                ->modifyQueryUsing(function ($query) use ($method) {
                    $query->where('payment_method_id', $method->id);
                })
                ->badge(function () use ($method) {
                    return Sale::where('payment_method_id', $method->id)->count();
                })
                ->badgeColor('primary');
        }

        return $tabs;
    }
}
