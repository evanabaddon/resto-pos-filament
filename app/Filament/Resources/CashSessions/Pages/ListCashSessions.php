<?php

namespace App\Filament\Resources\CashSessions\Pages;

use App\Filament\Resources\CashSessions\CashSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashSessions extends ListRecords
{
    protected static string $resource = CashSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            
        ];
    }
}
