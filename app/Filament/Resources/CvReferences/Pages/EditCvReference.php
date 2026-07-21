<?php

namespace App\Filament\Resources\CvReferences\Pages;

use App\Filament\Resources\CvReferences\CvReferenceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCvReference extends EditRecord
{
    protected static string $resource = CvReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
