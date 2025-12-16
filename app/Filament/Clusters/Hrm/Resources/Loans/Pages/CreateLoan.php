<?php

namespace App\Filament\Clusters\Hrm\Resources\Loans\Pages;

use App\Filament\Clusters\Hrm\Resources\Loans\LoanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;
}
