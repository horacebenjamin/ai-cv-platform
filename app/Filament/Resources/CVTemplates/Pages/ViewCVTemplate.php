<?php

namespace App\Filament\Resources\CVTemplates\Pages;

use App\Filament\Resources\CVTemplates\CVTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCVTemplate extends ViewRecord
{
    protected static string $resource = CVTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
