<?php

namespace App\Filament\Resources\CvExperiences\Pages;

use App\Filament\Resources\CvExperiences\CvExperienceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCvExperiences extends ListRecords
{
    protected static string $resource = CvExperienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
