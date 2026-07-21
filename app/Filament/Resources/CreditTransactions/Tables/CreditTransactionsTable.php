<?php

namespace App\Filament\Resources\CreditTransactions\Tables;

use App\Filament\Resources\Support\AiAdmin;
use Filament\Tables\Table;

class CreditTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return AiAdmin::creditTable($table);
    }
}
