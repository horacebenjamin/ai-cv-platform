<?php

namespace App\Filament\Resources\CvHistories\Pages;

use App\Filament\Resources\CvHistories\CvHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCvHistories extends ListRecords
{
    protected static string $resource = CvHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
