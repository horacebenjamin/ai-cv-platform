<?php

namespace App\Filament\Resources\CvLanguages\Pages;

use App\Filament\Resources\CvLanguages\CvLanguageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCvLanguage extends ViewRecord
{
    protected static string $resource = CvLanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
