<?php

namespace App\Filament\Resources\CreditTransactions\Schemas;

use App\Filament\Resources\Support\AiAdmin;
use Filament\Schemas\Schema;

class CreditTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return AiAdmin::creditForm($schema);
    }
}
