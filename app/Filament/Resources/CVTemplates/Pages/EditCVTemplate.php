<?php

namespace App\Filament\Resources\CVTemplates\Pages;

use App\Filament\Resources\CVTemplates\CVTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCVTemplate extends EditRecord
{
    protected static string $resource = CVTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
