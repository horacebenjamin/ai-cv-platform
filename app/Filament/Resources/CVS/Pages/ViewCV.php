<?php

namespace App\Filament\Resources\CVS\Pages;

use App\Filament\Resources\CVS\CVResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCV extends ViewRecord
{
    protected static string $resource = CVResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
