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
            CreateAction::make()->label(__('messages.create_product')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()->label(__('messages.all_products')),
            'retail' => Tab::make()->label(__('messages.retail_products'))->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'retail')),
            'kitchen' => Tab::make()->label(__('messages.kitchen_products'))->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'produced')),
            'bar' => Tab::make()->label(__('messages.bar_products'))->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'bar')),
            'raw' => Tab::make()->label(__('messages.raw_materials'))->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'raw')),
        ];
    }
}
