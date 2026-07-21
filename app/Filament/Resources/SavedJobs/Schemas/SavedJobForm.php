<?php

namespace App\Filament\Resources\SavedJobs\Schemas;

use App\Filament\Resources\Support\JobAdmin;
use Filament\Schemas\Schema;

class SavedJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return JobAdmin::savedJobForm($schema);
    }
}
