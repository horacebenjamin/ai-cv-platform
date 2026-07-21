<?php

namespace App\Filament\Resources\AiRequests\Pages;

use App\Filament\Resources\AiRequests\AiRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAiRequest extends ViewRecord
{
    protected static string $resource = AiRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
