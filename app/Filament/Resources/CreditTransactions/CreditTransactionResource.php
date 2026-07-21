<?php

namespace App\Filament\Resources\CreditTransactions;

use App\Filament\Resources\CreditTransactions\Pages\CreateCreditTransaction;
use App\Filament\Resources\CreditTransactions\Pages\EditCreditTransaction;
use App\Filament\Resources\CreditTransactions\Pages\ListCreditTransactions;
use App\Filament\Resources\CreditTransactions\Pages\ViewCreditTransaction;
use App\Filament\Resources\CreditTransactions\Schemas\CreditTransactionForm;
use App\Filament\Resources\CreditTransactions\Schemas\CreditTransactionInfolist;
use App\Filament\Resources\CreditTransactions\Tables\CreditTransactionsTable;
use App\Models\CreditTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CreditTransactionResource extends Resource
{
    protected static ?string $model = CreditTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'AI Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'type';

    public static function form(Schema $schema): Schema
    {
        return CreditTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CreditTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditTransactionsTable::configure($table);
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
            'index' => ListCreditTransactions::route('/'),
            'create' => CreateCreditTransaction::route('/create'),
            'view' => ViewCreditTransaction::route('/{record}'),
            'edit' => EditCreditTransaction::route('/{record}/edit'),
        ];
    }
}
