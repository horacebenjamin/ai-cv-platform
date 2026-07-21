<?php

namespace App\Filament\Resources\CvLanguages\Pages;

use App\Filament\Resources\CvLanguages\CvLanguageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCvLanguages extends ListRecords
{
    protected static string $resource = CvLanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
