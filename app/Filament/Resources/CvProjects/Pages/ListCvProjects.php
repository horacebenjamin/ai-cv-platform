<?php

namespace App\Filament\Resources\CvProjects\Pages;

use App\Filament\Resources\CvProjects\CvProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCvProjects extends ListRecords
{
    protected static string $resource = CvProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
