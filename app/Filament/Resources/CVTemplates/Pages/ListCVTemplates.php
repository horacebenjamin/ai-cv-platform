<?php

namespace App\Filament\Resources\CVTemplates\Pages;

use App\Filament\Resources\CVTemplates\CVTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCVTemplates extends ListRecords
{
    protected static string $resource = CVTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
