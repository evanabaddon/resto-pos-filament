<?php

namespace App\Filament\Clusters\Crm\Resources\Members\Pages;

use App\Filament\Clusters\Crm\Resources\Members\MemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;
}