<?php

namespace App\Filament\Resources\CvCertifications\Pages;

use App\Filament\Resources\CvCertifications\CvCertificationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCvCertification extends EditRecord
{
    protected static string $resource = CvCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
