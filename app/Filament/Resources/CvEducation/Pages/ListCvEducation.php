<?php

namespace App\Filament\Resources\CvEducation\Pages;

use App\Filament\Resources\CvEducation\CvEducationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCvEducation extends ListRecords
{
    protected static string $resource = CvEducationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
