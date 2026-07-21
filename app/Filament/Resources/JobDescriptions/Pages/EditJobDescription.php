<?php

namespace App\Filament\Resources\JobDescriptions\Pages;

use App\Filament\Resources\JobDescriptions\JobDescriptionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditJobDescription extends EditRecord
{
    protected static string $resource = JobDescriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
