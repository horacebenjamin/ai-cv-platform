<?php

namespace App\Filament\Resources\SavedJobs\Pages;

use App\Filament\Resources\SavedJobs\SavedJobResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSavedJob extends ViewRecord
{
    protected static string $resource = SavedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
