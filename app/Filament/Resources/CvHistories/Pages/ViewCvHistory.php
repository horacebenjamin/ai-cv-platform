<?php

namespace App\Filament\Resources\CvHistories\Pages;

use App\Filament\Resources\CvHistories\CvHistoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCvHistory extends ViewRecord
{
    protected static string $resource = CvHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
