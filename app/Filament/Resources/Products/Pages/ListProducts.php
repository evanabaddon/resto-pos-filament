<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Produk'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Semua Produk' => Tab::make()->label('Semua Produk'),
            'Produk Jadi' => Tab::make()->label('Produk Jadi')->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'retail')),
            'Product Kitchen' => Tab::make()->label('Product Kitchen')->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'produced')),
            'Produk Bar' => Tab::make()->label('Produk Bar')->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'bar')),
            'Bahan Baku' => Tab::make()->label('Bahan Baku')->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'raw')),
        ];
    }
}
