<?php

namespace App\Filament\Resources\CVS\Pages;

use App\Filament\Resources\CVS\CVResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCVS extends ListRecords
{
    protected static string $resource = CVResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
