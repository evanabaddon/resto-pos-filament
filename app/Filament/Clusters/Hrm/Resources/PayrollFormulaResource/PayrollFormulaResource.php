<?php

namespace App\Filament\Clusters\Hrm\Resources\PayrollFormulaResource;

use App\Filament\Clusters\Hrm\HrmCluster;
use App\Filament\Clusters\Hrm\Resources\PayrollFormulaResource\Pages;
use App\Models\PayrollFormula;
use BackedEnum;
use UnitEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PayrollFormulaResource extends Resource
{
    protected static ?string $model = PayrollFormula::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen SDM';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Rumus Gaji';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Rumus')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('script')
                    ->label('Script Rumus (PHP Expression)')
                    ->helperText('Variables: $base_salary, $attendance_days, $overtime_minutes, $late_count, $early_leave_count. Example: ($base_salary / 30 * $attendance_days) + ($overtime_minutes * 500)')
                    ->rows(5)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([

                DeleteBulkAction::make(),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollFormulas::route('/'),
            'create' => Pages\CreatePayrollFormula::route('/create'),
            'edit' => Pages\EditPayrollFormula::route('/{record}/edit'),
        ];
    }
}
