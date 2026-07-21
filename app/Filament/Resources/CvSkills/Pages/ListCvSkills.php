<?php

namespace App\Filament\Resources\CvSkills\Pages;

use App\Filament\Resources\CvSkills\CvSkillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCvSkills extends ListRecords
{
    protected static string $resource = CvSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
