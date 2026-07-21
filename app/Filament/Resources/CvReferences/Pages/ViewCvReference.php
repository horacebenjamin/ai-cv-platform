<?php

namespace App\Filament\Resources\CvReferences\Pages;

use App\Filament\Resources\CvReferences\CvReferenceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCvReference extends ViewRecord
{
    protected static string $resource = CvReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
