<?php

namespace App\Filament\Resources\SavedJobs\Pages;

use App\Filament\Resources\SavedJobs\SavedJobResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSavedJob extends CreateRecord
{
    protected static string $resource = SavedJobResource::class;
}
