<?php

namespace App\Filament\Resources\CvProjects\Pages;

use App\Filament\Resources\CvProjects\CvProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCvProject extends EditRecord
{
    protected static string $resource = CvProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
