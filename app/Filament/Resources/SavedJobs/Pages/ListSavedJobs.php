<?php

namespace App\Filament\Resources\SavedJobs\Pages;

use App\Filament\Resources\SavedJobs\SavedJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSavedJobs extends ListRecords
{
    protected static string $resource = SavedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
