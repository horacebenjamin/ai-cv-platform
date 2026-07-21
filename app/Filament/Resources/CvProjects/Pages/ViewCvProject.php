<?php

namespace App\Filament\Resources\CvProjects\Pages;

use App\Filament\Resources\CvProjects\CvProjectResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCvProject extends ViewRecord
{
    protected static string $resource = CvProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
