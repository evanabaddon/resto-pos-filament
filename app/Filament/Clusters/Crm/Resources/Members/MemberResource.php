<?php

namespace App\Filament\Clusters\Crm\Resources\Members;

use App\Filament\Clusters\Crm\CrmCluster;
use App\Filament\Clusters\Crm\Resources\Members\Pages;
use App\Filament\Clusters\Crm\Resources\Members\Pages\CreateMember;
use App\Filament\Clusters\Crm\Resources\Members\Pages\EditMember;
use App\Filament\Clusters\Crm\Resources\Members\Pages\ListMembers;
use App\Filament\Clusters\Crm\Resources\Members\RelationManagers\SalesRelationManager;
use App\Filament\Clusters\Crm\Resources\Members\Schemas\MemberForm;
use App\Filament\Clusters\Crm\Resources\Members\Tables\MembersTable;
use App\Models\Member;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use BackedEnum;
use Filament\Tables\Table;
use UnitEnum;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|UnitEnum|null $navigationGroup = 'Kemitraan (CRM)';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Member';

    protected static ?string $navigationLabel = 'Member / Pelanggan';

    protected static ?string $cluster = CrmCluster::class;

    // RBAC: super_admin, admin, cashier
    public static function canViewAny(): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin', 'cashier']);
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin', 'cashier']);
    }

    public static function canEdit(Model $record): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin', 'cashier']);
    }

    public static function canDelete(Model $record): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_crm;
    }

    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SalesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }
}