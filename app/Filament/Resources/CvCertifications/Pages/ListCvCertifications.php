<?php

namespace App\Filament\Resources\CvCertifications\Pages;

use App\Filament\Resources\CvCertifications\CvCertificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCvCertifications extends ListRecords
{
    protected static string $resource = CvCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
