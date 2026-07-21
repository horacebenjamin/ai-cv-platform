<?php

namespace App\Filament\Resources\CvHistories\Pages;

use App\Filament\Resources\CvHistories\CvHistoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCvHistory extends EditRecord
{
    protected static string $resource = CvHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
