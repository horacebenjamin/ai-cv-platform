<?php

namespace App\Filament\Resources\CVS\Pages;

use App\Filament\Resources\CVS\CVResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCV extends EditRecord
{
    protected static string $resource = CVResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
