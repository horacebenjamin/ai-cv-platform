<?php

namespace App\Filament\Resources\CvExperiences\Pages;

use App\Filament\Resources\CvExperiences\CvExperienceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCvExperience extends EditRecord
{
    protected static string $resource = CvExperienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
