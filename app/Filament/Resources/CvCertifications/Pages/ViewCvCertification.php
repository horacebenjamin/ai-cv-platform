<?php

namespace App\Filament\Resources\CvCertifications\Pages;

use App\Filament\Resources\CvCertifications\CvCertificationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCvCertification extends ViewRecord
{
    protected static string $resource = CvCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
