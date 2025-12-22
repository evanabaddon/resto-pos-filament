<?php

namespace App\Filament\Clusters\Hrm\Resources\Loans;

use App\Filament\Clusters\Hrm\HrmCluster;
use App\Filament\Clusters\Hrm\Resources\Loans\Pages\CreateLoan;
use App\Filament\Clusters\Hrm\Resources\Loans\Pages\EditLoan;
use App\Filament\Clusters\Hrm\Resources\Loans\Pages\ListLoans;
use App\Filament\Clusters\Hrm\Resources\Loans\Schemas\LoanForm;
use App\Filament\Clusters\Hrm\Resources\Loans\Tables\LoansTable;
use App\Models\Loan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use Illuminate\Database\Eloquent\Model;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Pinjaman';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen SDM';

    protected static ?int $navigationSort = 6;

    protected static ?string $cluster = HrmCluster::class;

    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_hrm;
    }

    public static function canDelete(Model $record): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin']);
    }

    public static function form(Schema $schema): Schema
    {
        return LoanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoans::route('/'),
            'create' => CreateLoan::route('/create'),
            'edit' => EditLoan::route('/{record}/edit'),
        ];
    }
}
