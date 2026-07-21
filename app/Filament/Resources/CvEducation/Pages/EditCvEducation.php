<?php

namespace App\Filament\Resources\CvEducation\Pages;

use App\Filament\Resources\CvEducation\CvEducationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCvEducation extends EditRecord
{
    protected static string $resource = CvEducationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
