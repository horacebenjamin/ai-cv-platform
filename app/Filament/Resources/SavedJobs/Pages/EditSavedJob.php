<?php

namespace App\Filament\Resources\SavedJobs\Pages;

use App\Filament\Resources\SavedJobs\SavedJobResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSavedJob extends EditRecord
{
    protected static string $resource = SavedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
