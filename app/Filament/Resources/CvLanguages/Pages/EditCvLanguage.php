<?php

namespace App\Filament\Resources\CvLanguages\Pages;

use App\Filament\Resources\CvLanguages\CvLanguageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCvLanguage extends EditRecord
{
    protected static string $resource = CvLanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
