<?php

namespace App\Filament\Resources\CashSessions\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'cashExpenses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->required()
                    ->default(now()),
                TextInput::make('reference')
                    ->default(fn () => \App\Models\Expense::generateReference())
                    ->readOnly(),
                Select::make('expense_category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->label('Category')
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                    ]),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Select::make('payment_method_id')
                    ->relationship('paymentMethod', 'name')
                    ->default(fn () => \App\Models\Expense::getCashPaymentMethodId())
                    ->disabled() // Force to Cash since it's Cash Session
                    ->required(),
                TextInput::make('recipient'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                \Filament\Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                    ->default('approved') // Expenses from POS usually approved immediately
                    ->required(),
                \Filament\Forms\Components\Hidden::make('fund_source')
                    ->default('cashier'),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('date')
                    ->date(),
                TextEntry::make('reference'),
                TextEntry::make('category.name')
                    ->label('Category'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('amount')
                    ->money('IDR'),
                TextEntry::make('paymentMethod.name')
                    ->label('Payment Method'),
                TextEntry::make('recipient'),
                TextEntry::make('notes')
                    ->columnSpanFull(),
                TextEntry::make('user.name')
                    ->label('Created By'),
                TextEntry::make('status')
                    ->badge(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('reference')
                    ->searchable()
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('danger'),
                TextColumn::make('recipient')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label('User')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New Cash Expense'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
