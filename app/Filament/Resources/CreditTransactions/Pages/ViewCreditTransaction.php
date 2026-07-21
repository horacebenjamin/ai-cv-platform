<?php

namespace App\Filament\Resources\CreditTransactions\Pages;

use App\Filament\Resources\CreditTransactions\CreditTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCreditTransaction extends ViewRecord
{
    protected static string $resource = CreditTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
