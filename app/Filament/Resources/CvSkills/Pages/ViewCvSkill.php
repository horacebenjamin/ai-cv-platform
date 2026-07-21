<?php

namespace App\Filament\Resources\CvSkills\Pages;

use App\Filament\Resources\CvSkills\CvSkillResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCvSkill extends ViewRecord
{
    protected static string $resource = CvSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
