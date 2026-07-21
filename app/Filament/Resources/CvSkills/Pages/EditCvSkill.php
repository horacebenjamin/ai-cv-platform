<?php

namespace App\Filament\Resources\CvSkills\Pages;

use App\Filament\Resources\CvSkills\CvSkillResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCvSkill extends EditRecord
{
    protected static string $resource = CvSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
