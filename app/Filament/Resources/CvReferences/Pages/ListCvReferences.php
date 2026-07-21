<?php

namespace App\Filament\Resources\CvReferences\Pages;

use App\Filament\Resources\CvReferences\CvReferenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCvReferences extends ListRecords
{
    protected static string $resource = CvReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
