<?php

namespace App\Filament\Resources\CvExperiences\Pages;

use App\Filament\Resources\CvExperiences\CvExperienceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCvExperience extends ViewRecord
{
    protected static string $resource = CvExperienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
