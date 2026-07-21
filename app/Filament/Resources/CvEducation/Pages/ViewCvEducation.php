<?php

namespace App\Filament\Resources\CvEducation\Pages;

use App\Filament\Resources\CvEducation\CvEducationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCvEducation extends ViewRecord
{
    protected static string $resource = CvEducationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
